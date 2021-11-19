<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Pagination;

interface PaginationResponseInterface
{
    public function getTotalCount(): int;

    public function getPage(): int;

    public function getPageCount(): int;

    public function getPageSize(): int;

    public function getItems(): array;
}
