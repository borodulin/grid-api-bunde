<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Pagination;

interface PaginationInterface
{
    public function getPage(): int;

    public function getPageSize(): int;
}
