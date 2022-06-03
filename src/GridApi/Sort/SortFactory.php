<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\Sort;

use Symfony\Component\HttpFoundation\InputBag;

class SortFactory
{
    private string $sortKey;

    public function __construct(
        string $sortKey
    ) {
        $this->sortKey = $sortKey;
    }

    public function tryCreateFromInputBug(InputBag $inputBag): ?Sort
    {
        $sortQuery = $inputBag->get($this->sortKey);
        if ($sortQuery) {
            $sortOrders = $this->getSortOrders((string) $sortQuery);
            if ($sortOrders) {
                return new Sort($sortOrders);
            }
        }

        return null;
    }

    private function getSortOrders(string $sortQuery): array
    {
        $sortOrders = array_filter(array_map('trim', explode(',', $sortQuery)));
        $result = [];
        foreach ($sortOrders as $sortOrder) {
            if (preg_match('/^([\w.]+)([+-])?$/', $sortOrder, $matches)) {
                if (isset($matches[2]) && ('-' === $matches[2])) {
                    $result[$matches[1]] = 'DESC';
                } else {
                    $result[$matches[1]] = 'ASC';
                }
            }
        }

        return $result;
    }
}
