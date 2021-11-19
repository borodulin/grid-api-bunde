<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Pagination;

use Doctrine\ORM\QueryBuilder;

class Pagination
{
    public function paginate(
        PaginationRequestInterface $paginationRequest,
        QueryBuilder $queryBuilder,
        callable $converter = null
    ): PaginationResponseInterface {
        $pageSize = $paginationRequest->getPageSize();
        $page = $paginationRequest->getPage();
        $offset = $page * $pageSize;
        $limit = $pageSize;

        $qbResults = (clone $queryBuilder)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $items = $qbResults->getQuery()->getResult();

        if (null !== $converter) {
            $items = array_map($converter, $items);
        }

        $qbCount = (clone $queryBuilder)
            ->resetDQLPart('orderBy')
        ;
        $alias = $qbCount->getRootAliases()[0];
        $totalCount = (int) $qbCount->select("count($alias)")->getQuery()->getSingleScalarResult();

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
