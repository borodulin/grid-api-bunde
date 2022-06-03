<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\Pagination;

use Symfony\Component\HttpFoundation\InputBag;

class PaginationFactory
{
    private string $pageKey;
    private string $pageSizeKey;
    private int $defaultPageSize;

    public function __construct(
        string $pageKey,
        string $pageSizeKey,
        int $defaultPageSize
    ) {
        $this->pageKey = $pageKey;
        $this->pageSizeKey = $pageSizeKey;
        $this->defaultPageSize = $defaultPageSize;
    }

    public function createFromInputBug(InputBag $inputBag): Pagination
    {
        return new Pagination(
            $this->getIntegerQueryParam($inputBag, $this->pageKey, 0),
            $this->getIntegerQueryParam($inputBag, $this->pageSizeKey, $this->defaultPageSize)
        );
    }

    public function createDefault(): Pagination
    {
        return new Pagination(0, $this->defaultPageSize);
    }

    private function getIntegerQueryParam(InputBag $query, string $name, int $default): int
    {
        $value = $query->get($name);
        if ($value && is_numeric($value)) {
            $value = (int) $value;

            return $value <= 0 ? $default : $value;
        }

        return $default;
    }
}
