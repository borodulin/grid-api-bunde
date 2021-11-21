<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\ArgumentResolver;

use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\EntityApi;
use Borodulin\Bundle\GridApiBundle\GridApi\EntityApiInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\EntityRecursiveExpander;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandRequestFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class EntityApiResolver implements ArgumentValueResolverInterface
{
    private string $expandKey;
    private EntityRecursiveExpander $entityExpand;
    private ScenarioInterface $scenario;

    public function __construct(
        EntityRecursiveExpander $entityExpand,
        ScenarioInterface $scenario,
        string $expandKey
    ) {
        $this->expandKey = $expandKey;
        $this->entityExpand = $entityExpand;
        $this->scenario = $scenario;
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

        yield (new EntityApi($this->entityExpand, $this->scenario))
            ->setExpandRequest($expandRequest)
        ;
    }
}
