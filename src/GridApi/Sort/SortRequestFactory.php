<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Sort;

use Symfony\Component\HttpFoundation\InputBag;

class SortRequestFactory
{
    public static function tryCreateFromInputBug(InputBag $inputBag, string $sortKey): ?SortRequest
    {
        $sortQuery = $inputBag->get($sortKey);
        if ($sortQuery) {
            $sortOrders = self::getSortOrders((string) $sortQuery);
            if ($sortOrders) {
                return new SortRequest($sortOrders);
            }
        }

        return null;
    }

    private static function getSortOrders(string $sortQuery): array
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
