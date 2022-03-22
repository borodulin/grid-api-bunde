<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\ArgumentResolver;

use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\DataProviderDecorator;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\DataProviderInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandFactory;
use Borodulin\Bundle\GridApiBundle\GridApi\Filter\FilterFactory;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationFactory;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\SortFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\ServiceValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class DataProviderResolver implements ArgumentValueResolverInterface
{
    private SortFactory $sortRequestFactory;
    private ExpandFactory $expandRequestFactory;
    private FilterFactory $filterRequestFactory;
    private PaginationFactory $paginationRequestFactory;
    private ScenarioInterface $scenario;
    private ServiceValueResolver $serviceValueResolver;

    public function __construct(
        ScenarioInterface $scenario,
        SortFactory $sortRequestFactory,
        ExpandFactory $expandRequestFactory,
        FilterFactory $filterRequestFactory,
        PaginationFactory $paginationRequestFactory,
        ServiceValueResolver $serviceValueResolver
    ) {
        $this->sortRequestFactory = $sortRequestFactory;
        $this->expandRequestFactory = $expandRequestFactory;
        $this->filterRequestFactory = $filterRequestFactory;
        $this->paginationRequestFactory = $paginationRequestFactory;
        $this->scenario = $scenario;
        $this->serviceValueResolver = $serviceValueResolver;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $type = $argument->getType();

        if (!$type || !class_exists($type)) {
            return false;
        }

        $reflection = new \ReflectionClass($type);

        return $this->serviceValueResolver->supports($request, $argument)
            && $reflection->implementsInterface(DataProviderInterface::class);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $service = null;
        foreach ($this->serviceValueResolver->resolve($request, $argument) as $item) {
            $service = $item;
        }
        if ($service instanceof DataProviderDecorator) {
            yield $service;
        } elseif ($service instanceof DataProviderInterface) {
            $sort = $this->sortRequestFactory->tryCreateFromInputBug($request->query);
            $expand = $this->expandRequestFactory->tryCreateFromInputBug($request->query);
            $pagination = $this->paginationRequestFactory->createFromInputBug($request->query);
            $filter = $this->filterRequestFactory->tryCreateFromInputBug($request->query);

            yield (new DataProviderDecorator($service))
                ->setScenario($this->scenario)
                ->setExpand($expand)
                ->setSort($sort)
                ->setFilter($filter)
                ->setPagination($pagination)
            ;
        } else {
            throw new \InvalidArgumentException();
        }
    }
}
