<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\ArgumentResolver;

use Borodulin\Bundle\GridApiBundle\GridApi\EntityApi;
use Borodulin\Bundle\GridApiBundle\GridApi\EntityApiInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\EntityExpand;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandRequestFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class EntityApiResolver implements ArgumentValueResolverInterface
{
    private string $expandKey;
    private EntityExpand $entityExpand;

    public function __construct(
        EntityExpand $entityExpand,
        string $expandKey
    ) {
        $this->expandKey = $expandKey;
        $this->entityExpand = $entityExpand;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $type = $argument->getType();

        if (!$type || !interface_exists($type)) {
            return false;
        }

        $reflection = new \ReflectionClass($type);

        return $reflection->isInterface()
            && $reflection->implementsInterface(EntityApiInterface::class);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $expandRequest = ExpandRequestFactory::tryCreateFromInputBug($request->query, $this->expandKey);

        yield (new EntityApi($this->entityExpand))
            ->setExpandRequest($expandRequest)
        ;
    }
}
