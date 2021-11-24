<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi;

use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\CustomFilterInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\CustomSortInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\DataProviderInterface;
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

    public function __construct(
        ScenarioInterface $scenario,
        NormalizerInterface $normalizer,
        int $defaultPageSize
    ) {
        $this->defaultPageSize = $defaultPageSize;
        $this->entityApi = new EntityApi($normalizer, $scenario);
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

    private function prepareQueryBuilder(DataProviderInterface $dataProvider): QueryBuilder
    {
        $qbClone = clone $dataProvider->getQueryBuilder();

        if (null !== $this->sortRequest) {
            (new Sorter())->sort(
                $this->sortRequest,
                $qbClone,
                $dataProvider instanceof CustomSortInterface ? $dataProvider : null
            );
        }
        if (null !== $this->filterRequest) {
            (new Filter())->filter(
                $this->filterRequest,
                $qbClone,
                $dataProvider instanceof CustomFilterInterface ? $dataProvider : null
            );
        }

        return $qbClone;
    }

    public function paginate(
        DataProviderInterface $dataProvider
    ): PaginationResponseInterface {
        $paginationRequest = $this->paginationRequest ?? new PaginationRequest(0, $this->defaultPageSize);

        $queryBuilder = $this->prepareQueryBuilder($dataProvider);

        return (new Paginator())
            ->paginate(
                $paginationRequest,
                $queryBuilder,
                [$this->entityApi, 'show']
            );
    }

    public function listAll(DataProviderInterface $dataProvider): array
    {
        $queryBuilder = $this->prepareQueryBuilder($dataProvider);

        return array_map(
            [$this->entityApi, 'show'],
            $queryBuilder->getQuery()->getResult()
        );
    }
}
