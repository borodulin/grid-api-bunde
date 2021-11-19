<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\EntityConverter;

interface CustomExpandInterface
{
    /**
     * Association Names.
     *
     * @example ['customer' => fn ($entity) => $entity->getCustomer(), 'company']
     */
    public function getExpandFields(): array;
}
