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

    public static function notFound($message = "")
    {
        return new static($message, StatusCode::NOT_FOUND);
    }

    public static function badRequest($message = "")
    {
        return new static($message, StatusCode::BAD_REQUEST);
    }

    public static function serverError($message = "")
    {
        return new static($message, StatusCode::INTERNAL_SERVER_ERROR);
    }
}
