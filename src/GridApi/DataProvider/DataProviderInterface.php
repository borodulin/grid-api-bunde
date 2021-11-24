<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\DataProvider;

use Doctrine\ORM\QueryBuilder;

interface DataProviderInterface
{
    public function getQueryBuilder(): QueryBuilder;
}
