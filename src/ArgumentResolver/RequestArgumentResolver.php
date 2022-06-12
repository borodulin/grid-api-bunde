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
                $normalData = $request->toArray();
            } elseif ('form' === $format) {
                $normalData = $request->request->all();
            }
        } else {
            $normalData = $request->query->all();
        }
        $violations = $this->validateProperties($argument->getType(), (array) $normalData, ['Default', $request->getMethod()]);
        if (\count($violations)) {
            throw new ValidationException($violations);
        }
        $instance = $this->serializer->denormalize(
            $normalData,
            $argument->getType(),
            'xml'
        );
        $violations = [];
        $errors = $this->validator->validate($instance, null, ['Default', $request->getMethod()]);
        if ($errors->count()) {
            foreach ($errors as $error) {
                $this->putErrorAtPropertyPath($violations, $error->getPropertyPath(), $error->getMessage());
            }
        }
        if (\count($violations)) {
            throw new ValidationException($violations);
        }

        yield $instance;
    }

    private function putErrorAtPropertyPath(array &$violations, string $propertyPath, string $errorMessage): void
    {
        $pointer = &$violations;
        foreach (explode('.', $propertyPath) as $item) {
            $index = null;
            if (preg_match('/(\w+)\[(\d+)]/', $item, $matches)) {
                $item = $matches[1];
                $index = (int) $matches[2];
            }
            if (!isset($pointer[$item])) {
                $pointer[$item] = [];
            }
            if (null !== $index && !isset($pointer[$item][$index])) {
                $pointer[$item][$index] = [];
            }
            $pointer = &$pointer[$item];
            if (null !== $index) {
                $pointer = &$pointer[$index];
            }
        }
        $pointer[] = $errorMessage;
    }

    private function validateProperties(string $class, array $normalData, array $groups): array
    {
        $violations = [];
        $metadata = $this->validator->getMetadataFor($class);
        $reflection = new \ReflectionClass($class);
        $instance = $reflection->newInstanceWithoutConstructor();
        if ($metadata instanceof ClassMetadata) {
            foreach ($metadata->getConstrainedProperties() as $property) {
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
                } else {
                    $propertyReflection = $reflection->getProperty($property);
                    $propertyType = $propertyReflection->getType();
                    if (null !== $propertyType) {
                        $typeName = $propertyType->getName();
                        if (isset($normalData[$property]) && \is_array($normalData[$property])
                            && class_exists($typeName) && $this->validator->hasMetadataFor($typeName)
                        ) {
                            $propertyViolations = $this->validateProperties($propertyType->getName(), $normalData[$property], $groups);
                            if (\count($propertyViolations)) {
                                $violations[$property] = $propertyViolations;
                            }
                        }
                    }
                }
            }
        }

        return $violations;
    }
}
