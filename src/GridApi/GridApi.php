<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi;

use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\CustomFilterInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\CustomSortInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\DataProviderInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\QueryBuilderInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Filter\FilterBuilder;
use Borodulin\Bundle\GridApiBundle\GridApi\Filter\FilterInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationFactory;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationResponseInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\Paginator;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\SortBuilder;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\SortInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class GridApi implements GridApiInterface
{
    private ?FilterInterface $filterRequest = null;
    private ?SortInterface $sort = null;
    private ?PaginationInterface $pagination = null;
    private ?ExpandInterface $expand = null;
    private PaginationFactory $paginationRequestFactory;
    private NormalizerInterface $normalizer;
    private array $context = [];

    public function __construct(
        NormalizerInterface $normalizer,
        PaginationFactory $paginationRequestFactory
    ) {
        $this->paginationRequestFactory = $paginationRequestFactory;
        $this->normalizer = $normalizer;
    }

    public function setFilter(?FilterInterface $filterRequest): GridApiInterface
    {
        $this->filterRequest = $filterRequest;

        return $this;
    }

    public function setPagination(PaginationInterface $pagination): GridApiInterface
    {
        $this->pagination = $pagination;

        return $this;
    }

    public function setSort(?SortInterface $sort): GridApiInterface
    {
        $this->sort = $sort;

        return $this;
    }

    public function setExpand(?ExpandInterface $expand): GridApiInterface
    {
        $this->expand = $expand;

        return $this;
    }

    public function setContext(array $context): GridApiInterface
    {
        $this->context = $context;

        return $this;
    }

    private function prepareQueryBuilder(DataProviderInterface $dataProvider): QueryBuilderInterface
    {
        $queryBuilder = $dataProvider->getQueryBuilder();
        if (null !== $this->sort) {
            (new SortBuilder())->sort(
                $this->sort,
                $queryBuilder,
                $dataProvider instanceof CustomSortInterface ? $dataProvider : null
            );
        }
        if (null !== $this->filterRequest) {
            (new FilterBuilder())->filter(
                $this->filterRequest,
                $queryBuilder,
                $dataProvider instanceof CustomFilterInterface ? $dataProvider : null
            );
        }

        return $queryBuilder;
    }

    public function paginate(
        DataProviderInterface $dataProvider
    ): PaginationResponseInterface {
        $pagination = $this->pagination ?? $this->paginationRequestFactory->createDefault();

        $queryBuilder = $this->prepareQueryBuilder($dataProvider);

        $context = $this->context;
        $context['expand'] = null !== $this->expand ? $this->expand->getExpand() : [];

        return (new Paginator())
            ->paginate(
                $pagination,
                $queryBuilder,
                fn ($entity) => $this->normalizer->normalize($entity, null, $context)
            );
    }

    public function listAll(DataProviderInterface $dataProvider): array
    {
        $queryBuilder = $this->prepareQueryBuilder($dataProvider);

        $context = $this->context;
        $context['expand'] = null !== $this->expand ? $this->expand->getExpand() : [];

        return array_map(
            fn ($entity) => $this->normalizer->normalize($entity, null, $context),
            $queryBuilder->fetchAll()
        );
    }
}
