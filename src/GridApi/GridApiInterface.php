<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi;

use Borodulin\GridApiBundle\GridApi\DataProvider\DataProviderInterface;
use Borodulin\GridApiBundle\GridApi\Expand\ExpandInterface;
use Borodulin\GridApiBundle\GridApi\Filter\FilterInterface;
use Borodulin\GridApiBundle\GridApi\Pagination\PaginationInterface;
use Borodulin\GridApiBundle\GridApi\Pagination\PaginationResponseInterface;
use Borodulin\GridApiBundle\GridApi\Sort\SortInterface;

interface GridApiInterface
{
    public function setFilter(?FilterInterface $filterRequest): self;

    public function setSort(?SortInterface $sort): self;

    public function setPagination(PaginationInterface $pagination): self;

    public function setExpand(?ExpandInterface $expand): self;

    public function setContext(array $context): self;

    public function paginate(
        DataProviderInterface $dataProvider
    ): PaginationResponseInterface;

    public function listAll(DataProviderInterface $dataProvider): array;
}
