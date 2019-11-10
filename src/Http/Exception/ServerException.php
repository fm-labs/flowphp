<?php


namespace Flow\Http\Exception;


use Throwable;

class ServerException extends HttpException
{
    public function __construct($message = "", $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}