<?php

namespace Flow\Http\Message\Response;

use Psr\Http\Message\UriInterface;
use Flow\Http\Message\Response;

class ErrorResponse extends Response
{
    public function __construct($message, $status = 500, array $headers = [])
    {
        if ($message instanceof \Exception) {
            $message = $message->getMessage();
        }

        if (!is_string($message)) {
            $message = 'An error occured, but the error message could not be serialized';
            $status = 500;
        }

        parent::__construct($message, $status, $headers);
    }
}
