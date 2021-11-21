<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\EntityConverter;

use Borodulin\Bundle\GridApiBundle\Serializer\DummyNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class Scenario implements ScenarioInterface
{
    private string $name;

    private NameConverterInterface $nameConverter;

    public function __construct(
        string $name,
        ?NameConverterInterface $nameConverter = null
    ) {
        $this->name = $name;
        $this->nameConverter = $nameConverter ?? new DummyNameConverter();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameConverter(): NameConverterInterface
    {
        return $this->nameConverter;
    }
}
