<?php


namespace Flow\Http\Exception;


use Flow\Http\StatusCode;
use Throwable;

class HttpException extends \Exception
{
    public function __construct($message = "", $code = 400, Throwable $previous = null)
    {
        $message = ($message) ?: StatusCode::statusCodeToReason($code);

        parent::__construct($message, $code, $previous);
    }

    static public function notFound($message = "")
    {
        return new static($message, 404);
    }

    static public function serverError($message = "")
    {
        return new static($message, 500);
    }
}