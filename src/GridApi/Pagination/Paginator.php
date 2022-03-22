<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Pagination;

use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\PaginationQueryBuilderInterface;

class Paginator
{
    public function paginate(
        PaginationInterface $paginationRequest,
        PaginationQueryBuilderInterface $paginationQueryBuilder,
        callable $converter = null
    ): PaginationResponseInterface {
        $pageSize = $paginationRequest->getPageSize();
        $page = $paginationRequest->getPage();
        $offset = $page * $pageSize;
        $limit = $pageSize;

        $paginationQueryBuilder->setLimit($limit);
        $paginationQueryBuilder->setOffset($offset);

        $items = $paginationQueryBuilder->fetchAll();

        if (null !== $converter) {
            $items = array_map($converter, $items);
        }
        $totalCount = $paginationQueryBuilder->queryCount();

        $pageCount = (int) ceil($totalCount / $pageSize);

        return new PaginationResponse(
            $totalCount,
            $page,
            $pageCount,
            $pageSize,
            $items
        );
    }
}
