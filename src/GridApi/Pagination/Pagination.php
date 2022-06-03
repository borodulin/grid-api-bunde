<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\Pagination;

class Pagination implements PaginationInterface
{
    private int $page;
    private int $pageSize;

    public function __construct(
        int $page,
        int $pageSize
    ) {
        $this->page = $page;
        $this->pageSize = $pageSize;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }
}
