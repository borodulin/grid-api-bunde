<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\DataProvider;

interface QueryBuilderInterface extends FilterQueryBuilderInterface, PaginationQueryBuilderInterface, SortQueryBuilderInterface
{
}
