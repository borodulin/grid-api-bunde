<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\DataProvider;

use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Filter\FilterBuilder;
use Borodulin\Bundle\GridApiBundle\GridApi\Filter\FilterInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\SortBuilder;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\SortInterface;

class DataProviderDecorator implements DataProviderInterface
{
    private ?FilterInterface $filter = null;
    private ?SortInterface $sort = null;
    private ?PaginationInterface $pagination = null;
    private ?ExpandInterface $expand = null;
    private ?ScenarioInterface $scenario = null;

    private DataProviderInterface $dataProvider;

    public function __construct(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function getFilter(): ?FilterInterface
    {
        return $this->filter;
    }

    public function setFilter(?FilterInterface $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function getSort(): ?SortInterface
    {
        return $this->sort;
    }

    public function setSort(?SortInterface $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function getPagination(): ?PaginationInterface
    {
        return $this->pagination;
    }

    public function setPagination(?PaginationInterface $pagination): self
    {
        $this->pagination = $pagination;

        return $this;
    }

    public function getExpand(): ?ExpandInterface
    {
        return $this->expand;
    }

    public function setExpand(?ExpandInterface $expand): self
    {
        $this->expand = $expand;

        return $this;
    }

    public function getScenario(): ?ScenarioInterface
    {
        return $this->scenario;
    }

    public function setScenario(?ScenarioInterface $scenario): self
    {
        $this->scenario = $scenario;

        return $this;
    }

    public function getQueryBuilder(): QueryBuilderInterface
    {
        $queryBuilder = $this->dataProvider->getQueryBuilder();
        if (null !== $this->sort) {
            (new SortBuilder())->sort(
                $this->sort,
                $queryBuilder,
                $this->dataProvider instanceof CustomSortInterface ? $this->dataProvider : null
            );
        }
        if (null !== $this->filter) {
            (new FilterBuilder())->filter(
                $this->filter,
                $queryBuilder,
                $this->dataProvider instanceof CustomFilterInterface ? $this->dataProvider : null
            );
        }

        return $queryBuilder;
    }
}
