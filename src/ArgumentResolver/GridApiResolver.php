<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\ArgumentResolver;

use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\EntityRecursiveExpander;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandRequestFactory;
use Borodulin\Bundle\GridApiBundle\GridApi\Filter\FilterRequestFactory;
use Borodulin\Bundle\GridApiBundle\GridApi\GridApi;
use Borodulin\Bundle\GridApiBundle\GridApi\GridApiInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationRequestFactory;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\SortRequestFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class GridApiResolver implements ArgumentValueResolverInterface
{
    private EntityConverterRegistry $entityConverterRegistry;
    private string $expandKey;
    private string $pageKey;
    private string $pageSizeKey;
    private string $sortKey;
    private int $defaultPageSize;
    private EntityRecursiveExpander $entityExpand;
    private ScenarioInterface $scenario;

    public function __construct(
        EntityConverterRegistry $entityConverterRegistry,
        EntityRecursiveExpander $entityExpand,
        ScenarioInterface $scenario,
        string $expandKey,
        string $pageKey,
        string $pageSizeKey,
        string $sortKey,
        int $defaultPageSize
    ) {
        $this->entityConverterRegistry = $entityConverterRegistry;
        $this->entityExpand = $entityExpand;
        $this->scenario = $scenario;
        $this->expandKey = $expandKey;
        $this->pageKey = $pageKey;
        $this->pageSizeKey = $pageSizeKey;
        $this->sortKey = $sortKey;
        $this->defaultPageSize = $defaultPageSize;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $type = $argument->getType();

        if (!$type || !interface_exists($type)) {
            return false;
        }

        $reflection = new \ReflectionClass($type);

        return $reflection->isInterface()
            && $reflection->implementsInterface(GridApiInterface::class);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $sortRequest = SortRequestFactory::tryCreateFromInputBug($request->query, $this->sortKey);

        $expandRequest = ExpandRequestFactory::tryCreateFromInputBug($request->query, $this->expandKey);

        $paginationRequest = PaginationRequestFactory::createFromInputBug(
            $request->query,
            $this->pageKey,
            $this->pageSizeKey,
            $this->defaultPageSize
        );

        $filterRequest = FilterRequestFactory::tryCreateFromInputBug($request->query, [
            $this->expandKey,
            $this->sortKey,
            $this->pageKey,
            $this->pageSizeKey,
        ]);

        yield (new GridApi(
            $this->entityConverterRegistry,
            $this->entityExpand,
            $this->scenario,
            $this->defaultPageSize
        ))
            ->setExpandRequest($expandRequest)
            ->setSortRequest($sortRequest)
            ->setFilterRequest($filterRequest)
            ->setPaginationRequest($paginationRequest)
        ;
    }
}
