<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\ArgumentResolver;

use Borodulin\GridApiBundle\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestArgumentResolver implements ArgumentValueResolverInterface
{
    private ValidatorInterface $validator;
    private SerializerInterface $serializer;

    /**
     * @param SerializerInterface|Serializer $serializer
     */
    public function __construct(
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ) {
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $type = $argument->getType();
        if (!$type || !class_exists($type)) {
            return false;
        }

        $reflection = new \ReflectionClass($type);
        if ($this->validator->hasMetadataFor($type)) {
            $metadata = $this->validator->getMetadataFor($type);
        } else {
            $metadata = null;
        }

        return $reflection->implementsInterface(RequestInterface::class)
            && $metadata instanceof ClassMetadata;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $hasBody = \in_array(
            $request->getMethod(),
            [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_PATCH],
            true
        );

        $normalData = [];
        if ($hasBody) {
            $format = $request->getContentType();
            if ('json' === $format) {
                $normalData = json_decode($request->getContent(), true);
            } elseif ('form' === $format) {
                $normalData = $request->request->all();
            }
        } else {
            $normalData = $request->query->all();
        }

        $violations = $this->validateProperties($argument->getType(), ['Default', $request->getMethod()]);
        if (\count($violations)) {
            throw new ValidationException($violations);
        }

        yield $this->serializer->denormalize(
            $normalData,
            $argument->getType(),
            'xml'
        );
    }

    private function validateProperties(string $class, array $groups): array
    {
        $violations = [];
        $metadata = $this->validator->getMetadataFor($class);
        $reflection = new \ReflectionClass($class);
        $instance = $reflection->newInstanceWithoutConstructor();
        if ($metadata instanceof ClassMetadata) {
            foreach ($metadata->getConstrainedProperties() as $property) {
                $propertyMetadata = $metadata->getPropertyMetadata($property);
                if ($propertyMetadata instanceof ClassMetadata) {
                    $violations[$property] = $this->validateProperties($propertyMetadata->getClassName(), $groups);
                }
                $errors = $this->validator->validatePropertyValue(
                    $instance,
                    $property,
                    $normalData[$property] ?? null,
                    $groups
                );
                if ($errors->count()) {
                    foreach ($errors as $error) {
                        $violations[$property][] = $error->getMessage();
                    }
                }

            }
        }

        return $violations;
    }
}
