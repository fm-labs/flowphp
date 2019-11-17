<?php

namespace Flow\Http;

use Psr\Http\Message\MessageInterface;

class MessageInfo
{
    /**
     * Get length of body contents
     *
     * @return int
     */
    public static function getLength(MessageInterface $message)
    {
        return strlen((string)$message->getBody());
    }

    /**
     * Is Successful
     *
     * @return bool
     */
    public static function isSuccessful(MessageInterface $message)
    {
        return $message->getStatusCode() >= 200 && $message->getStatusCode() < 300;
    }

    /**
     * Is Redirect
     *
     * @return bool
     */
    public static function isRedirect(MessageInterface $message)
    {
        return in_array($message->getStatusCode(), array(301, 302, 303, 307));
    }

    /**
     * Is Redirection
     *
     * @return bool
     */
    public static function isRedirection(MessageInterface $message)
    {
        return $message->getStatusCode() >= 300 && $message->getStatusCode() < 400;
    }

    /**
     * Is Forbidden
     *
     * @return bool
     */
    public static function isForbidden(MessageInterface $message)
    {
        return $message->getStatusCode() === 403;
    }

    /**
     * Is Not Found
     *
     * @return bool
     */
    public static function isNotFound(MessageInterface $message)
    {
        return $message->getStatusCode() === 404;
    }

    /**
     * Is Client error
     *
     * @return bool
     */
    public static function isClientError(MessageInterface $message)
    {
        return $message->getStatusCode() >= 400 && $message->getStatusCode() < 500;
    }

    /**
     * Is Server Error
     *
     * @return bool
     */
    public static function isServerError(MessageInterface $message)
    {
        return $message->getStatusCode() >= 500 && $message->getStatusCode() < 600;
    }

    public static function isJson(MessageInterface $message)
    {
        $contentType = $message->getHeader("Content-Type") ?? [];
        return preg_match("/^(application\/json|text\/json)/", $contentType[0] ?? "");
    }

    public static function isFormUrlencoded(MessageInterface $message)
    {
        $contentType = $message->getHeader("Content-Type") ?? [];
        return preg_match("/^(application\/x-www-form-urlencoded)/", $contentType[0] ?? "");
    }
}
