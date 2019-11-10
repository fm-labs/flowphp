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
    static public function getLength(MessageInterface $message)
    {
        return strlen((string)$message->getBody());
    }

    /**
     * Is Successful
     *
     * @return bool
     */
    static public function isSuccessful(MessageInterface $message)
    {
        return $message->getStatusCode() >= 200 && $message->getStatusCode() < 300;
    }

    /**
     * Is Redirect
     *
     * @return bool
     */
    static public function isRedirect(MessageInterface $message)
    {
        return in_array($message->getStatusCode(), array(301, 302, 303, 307));
    }

    /**
     * Is Redirection
     *
     * @return bool
     */
    static public function isRedirection(MessageInterface $message)
    {
        return $message->getStatusCode() >= 300 && $message->getStatusCode() < 400;
    }

    /**
     * Is Forbidden
     *
     * @return bool
     */
    static public function isForbidden(MessageInterface $message)
    {
        return $message->getStatusCode() === 403;
    }

    /**
     * Is Not Found
     *
     * @return bool
     */
    static public function isNotFound(MessageInterface $message)
    {
        return $message->getStatusCode() === 404;
    }

    /**
     * Is Client error
     *
     * @return bool
     */
    static public function isClientError(MessageInterface $message)
    {
        return $message->getStatusCode() >= 400 && $message->getStatusCode() < 500;
    }

    /**
     * Is Server Error
     *
     * @return bool
     */
    static public function isServerError(MessageInterface $message)
    {
        return $message->getStatusCode() >= 500 && $message->getStatusCode() < 600;
    }

}