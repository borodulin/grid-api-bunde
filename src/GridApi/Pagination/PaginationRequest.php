<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Pagination;

class PaginationRequest implements PaginationRequestInterface
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
