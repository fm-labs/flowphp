<?php

namespace Flow\Http\Message\Response;

use Psr\Http\Message\UriInterface;
use Flow\Http\Message\Response;

class RedirectResponse extends Response
{
    public function __construct(UriInterface $uri, $status = 302)
    {
        $this->headers['Location'] = (string) $uri;

        parent::__construct($status);
    }
}
