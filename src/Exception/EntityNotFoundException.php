<?php

namespace App\Exception;

use Exception;

class EntityNotFoundException extends Exception
{
    public function __construct(int $id, int $code = 0, Exception $previous = null)
    {
        $message = sprintf('Entity with id "%s" could not be found.', $id);
        parent::__construct($message, $code, $previous);
    }
}