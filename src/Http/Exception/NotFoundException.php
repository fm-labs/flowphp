<?php


namespace Flow\Http\Exception;

/**
 * Class NotFoundException
 * @package Flow\Http\Exception
 * @deprecated Use HttpException::notFound() instead
 */
class NotFoundException extends HttpException
{
    public function __construct($message = "", $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}