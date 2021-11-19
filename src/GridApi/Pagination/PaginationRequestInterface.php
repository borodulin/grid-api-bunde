<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Pagination;

interface PaginationRequestInterface
{
    public function getPage(): int;

    public function getPageSize(): int;
}
