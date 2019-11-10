<?php
namespace Flow\Http\Message;

use Psr\Http\Message\StreamInterface;
use Flow\Http\Message\Stream\FileStream;
use Flow\Http\Message\Stream\StringStream;
use Flow\Http\Message\Stream\SocketStream;

abstract class Stream implements StreamInterface
{
    /**
     * @var array Stream meta data in the format of `stream_get_meta_data()` result
     */
    protected $meta = [
        'wrapper_type' => null,
        'stream_type' => null,
        'mode' => null,
        'unread_bytes' => 0,
        'seekable' => null,
        'uri' => null,
        'timed_out' => null,
        'eof' => null
    ];

    /**
     * Factory method for string streams
     *
     * @param $str
     * @return StringStream
     * @TODO Refactor with StreamFactory
     */
    static public function fromString($str)
    {
        return new StringStream($str);
    }

    /**
     * Factory method for file streams
     * @param $path
     * @return FileStream
     * @TODO Refactor with StreamFactory
     */
    static public function fromFile($path)
    {
        return new FileStream($path);
    }


    /**
     * Factory method for file streams
     * @param $path
     * @return SocketStream
     * @TODO Refactor with StreamFactory
     */
    static public function fromSocket($uri)
    {
        return new SocketStream($uri);
    }


    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }
}