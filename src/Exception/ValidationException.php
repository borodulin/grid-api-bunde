<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ValidationException extends HttpException
{
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct(422, 'Validation error');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
