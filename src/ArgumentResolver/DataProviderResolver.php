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
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class DataProviderResolver implements ArgumentValueResolverInterface
{
    private ContainerInterface $container;
    private SortFactory $sortRequestFactory;
    private ExpandFactory $expandRequestFactory;
    private FilterFactory $filterRequestFactory;
    private PaginationFactory $paginationRequestFactory;
    private ScenarioInterface $scenario;

    public function __construct(
        ContainerInterface $container,
        ScenarioInterface $scenario,
        SortFactory $sortRequestFactory,
        ExpandFactory $expandRequestFactory,
        FilterFactory $filterRequestFactory,
        PaginationFactory $paginationRequestFactory
    ) {
        $this->container = $container;
        $this->sortRequestFactory = $sortRequestFactory;
        $this->expandRequestFactory = $expandRequestFactory;
        $this->filterRequestFactory = $filterRequestFactory;
        $this->paginationRequestFactory = $paginationRequestFactory;
        $this->scenario = $scenario;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $type = $argument->getType();

        if (!$type || !class_exists($type)) {
            return false;
        }

        $reflection = new \ReflectionClass($type);

        return $reflection->isInstantiable()
            && $reflection->implementsInterface(DataProviderInterface::class);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $service = $this->container->get($argument->getType());
        if ($service instanceof DataProviderDecorator) {
            yield $service;
        } else {
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
        }
    }
}
