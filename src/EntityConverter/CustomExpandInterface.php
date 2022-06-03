<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\EntityConverter;

interface CustomExpandInterface
{
    /**
     * Association Names.
     *
     * @example ['customer' => fn ($entity) => $entity->getCustomer(), 'company']
     */
    public function getExpandFields(): array;
}
