<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\DataProvider;

interface DataProviderInterface
{
    public function getQueryBuilder(): QueryBuilderInterface;
}
