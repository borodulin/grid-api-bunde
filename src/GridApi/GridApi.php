<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi;

use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandRequestInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Filter\Filter;
use Borodulin\Bundle\GridApiBundle\GridApi\Filter\FilterRequestInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationRequest;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationRequestInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationResponseInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\Paginator;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\Sorter;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\SortRequestInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class GridApi implements GridApiInterface
{
    private EntityApiInterface $entityApi;

    private ?FilterRequestInterface $filterRequest = null;
    private ?SortRequestInterface $sortRequest = null;
    private ?PaginationRequestInterface $paginationRequest = null;

    private int $defaultPageSize;
    private EntityConverterRegistry $entityConverterRegistry;

    public function __construct(
        EntityConverterRegistry $entityConverterRegistry,
        ScenarioInterface $scenario,
        NormalizerInterface $normalizer,
        int $defaultPageSize
    ) {
        $this->defaultPageSize = $defaultPageSize;
        $this->entityApi = new EntityApi($normalizer, $scenario);
        $this->entityConverterRegistry = $entityConverterRegistry;
    }

    public function setFilterRequest(?FilterRequestInterface $filterRequest): GridApiInterface
    {
        $this->filterRequest = $filterRequest;

        return $this;
    }

    public function setPaginationRequest(PaginationRequestInterface $paginationRequest): GridApiInterface
    {
        $this->paginationRequest = $paginationRequest;

        return $this;
    }

    public function setSortRequest(?SortRequestInterface $sortRequest): GridApiInterface
    {
        $this->sortRequest = $sortRequest;

        return $this;
    }

    public function setExpandRequest(?ExpandRequestInterface $expandRequest): GridApiInterface
    {
        $this->entityApi->setExpandRequest($expandRequest);

        return $this;
    }

    public function setScenario(?ScenarioInterface $scenario): GridApiInterface
    {
        $this->entityApi->setScenario($scenario);

        return $this;
    }

    private function prepareQuery(QueryBuilder $queryBuilder): QueryBuilder
    {
        $qbClone = clone $queryBuilder;

        if (null !== $this->sortRequest) {
            $qbClone = (new Sorter($this->entityConverterRegistry))
                ->sort($this->sortRequest, $qbClone);
        }
        if (null !== $this->filterRequest) {
            $qbClone = (new Filter($this->entityConverterRegistry))
                ->filter($this->filterRequest, $qbClone);
        }

        return $qbClone;
    }

    public function paginate(
        QueryBuilder $queryBuilder
    ): PaginationResponseInterface {
        $paginationRequest = $this->paginationRequest ?? new PaginationRequest(0, $this->defaultPageSize);

        $queryBuilder = $this->prepareQuery($queryBuilder);

        return (new Paginator())
            ->paginate(
                $paginationRequest,
                $queryBuilder,
                [$this->entityApi, 'show']
            );
    }

    public function listAll(QueryBuilder $queryBuilder): array
    {
        $queryBuilder = $this->prepareQuery($queryBuilder);

        return array_map(
            [$this->entityApi, 'show'],
            $queryBuilder->getQuery()->getResult()
        );
    }
}
