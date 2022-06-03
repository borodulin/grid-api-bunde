<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\Pagination;

class PaginationResponse implements PaginationResponseInterface
{
    private int $totalCount;
    private int $page;
    private int $pageCount;
    private int $pageSize;
    private array $items;

    public function __construct(
        int $totalCount,
        int $page,
        int $pageCount,
        int $pageSize,
        array $items
    ) {
        $this->totalCount = $totalCount;
        $this->page = $page;
        $this->pageCount = $pageCount;
        $this->pageSize = $pageSize;
        $this->items = $items;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
