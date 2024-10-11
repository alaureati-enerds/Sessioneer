<?php

namespace Sessioneer\Exceptions;

class KeyNotFoundException extends \Exception
{
    protected $message = 'The requested key was not found.';

    public function __construct($message = null)
    {
        if ($message) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
