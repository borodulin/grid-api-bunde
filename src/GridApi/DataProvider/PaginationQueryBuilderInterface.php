<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\DataProvider;

interface PaginationQueryBuilderInterface
{
    public function setLimit(int $limit): void;

    public function setOffset(int $offset): void;

    public function queryCount(): int;

    public function fetchAll(): array;
}
