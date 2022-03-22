<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\ArgumentResolver;

use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\EntityApi;
use Borodulin\Bundle\GridApiBundle\GridApi\EntityApiInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityApiResolver implements ArgumentValueResolverInterface
{
    private ScenarioInterface $scenario;
    private NormalizerInterface $normalizer;
    private ExpandFactory $expandFactory;

    public function __construct(
        ScenarioInterface $scenario,
        NormalizerInterface $normalizer,
        ExpandFactory $expandFactory
    ) {
        $this->scenario = $scenario;
        $this->normalizer = $normalizer;
        $this->expandFactory = $expandFactory;
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
        $expand = $this->expandFactory->tryCreateFromInputBug($request->query);

        yield (new EntityApi($this->normalizer, $this->scenario))
            ->setExpand($expand)
        ;
    }
}
