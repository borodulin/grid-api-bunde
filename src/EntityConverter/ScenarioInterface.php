<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\EntityConverter;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

interface ScenarioInterface
{
    public function getName(): string;

    public function getNameConverter(): NameConverterInterface;
}
