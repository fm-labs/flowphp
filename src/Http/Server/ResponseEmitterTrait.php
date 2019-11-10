<?php
declare(strict_types=1);

namespace Flow\Http\Server;

use Psr\Http\Message\ResponseInterface;

/**
 * Trait ResponseEmitterTrait
 *
 * Default implementation of the `ResponseEmitterInterface`.
 *
 * By default the contents will be emitted to STDOUT.
 * Override the `emitter()` method in order to provide a custom emitter (closure).
 *
 * @package Flow\Http\Server
 */
trait ResponseEmitterTrait
{
    /**
     * Emit response to output stream.
     *
     * @param ResponseInterface $response
     * @return int Number of written bytes (only body, without header)
     */
    public function sendResponse(ResponseInterface $response): int
    {
        $this->emitHeader($response);
        // do not send body for redirects
        //if (MessageInfo::isRedirect($response)) {
        //    $response = $response->withBody(new StringStream(""));
        //}
        return $this->emitBody($response);
    }

    /**
     * @param ResponseInterface $response
     * @return void
     */
    private function emitHeader(ResponseInterface $response)/*: void 7.1+*/
    {
        // send headers, if not sent yet
        if (!headers_sent()) {
            // send status header
            $statusHeader = sprintf("HTTP/%s %s %s",
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            );
            header($statusHeader);

            // send response headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }
    }

    /**
     * @param ResponseInterface $response
     * @return int
     */
    private function emitBody(ResponseInterface $response): int
    {
        // send body
        $body = $response->getBody();
        // @TODO Implement support for seekable stream
        // @TODO Remove additional newline(?) (but makes console output much nicer)
        $contents = $body->getContents() . "\n";
        $this->emitter()($contents);

        return strlen($contents);
    }

    /**
     * Returns the default emitter, which emits to STDOUT.
     *
     * The return value MUST be a \Closure, which when invoked
     * SHOULD emit the content to the output stream.
     *
     * @return \Closure
     */
    private function emitter(): callable
    {
        return function ($content) {
            echo $content;
        };
    }
}