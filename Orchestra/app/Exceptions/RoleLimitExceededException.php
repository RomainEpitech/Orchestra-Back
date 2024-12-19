<?php

namespace App\Exceptions;

use Exception;

class RoleLimitExceededException extends Exception
{
    public function __construct(
        private int $currentCount,
        private int $limit
    ) {
        parent::__construct('Roles limit reached. Please upgrade your subscription.');
    }

    public function getCurrentCount(): int 
    {
        return $this->currentCount;
    }

    public function getLimit(): int 
    {
        return $this->limit;
    }
}