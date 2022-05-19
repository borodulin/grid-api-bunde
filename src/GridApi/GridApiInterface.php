<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi;

use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\DataProviderInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Filter\FilterInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\PaginationResponseInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\SortInterface;

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
