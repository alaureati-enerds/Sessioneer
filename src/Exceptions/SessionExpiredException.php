<?php

namespace Sessioneer\Exceptions;

class SessionExpiredException extends \Exception
{
    protected $message = 'Session expired.';

    public function __construct($message = null)
    {
        if ($message) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
