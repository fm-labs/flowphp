<?php
namespace Flow\Http\Message\Stream;

use Flow\Http\Message\Stream;

class FileStream extends Stream
{
    /**
     * @var string Absolute path to file
     */
    protected $path;

    /**
     * @var string File access mode
     */
    protected $mode;

    /**
     * @var false|resource
     */
    protected $handle;

    /**
     * @var array Options
     */
    protected $options = [
        'read_length' => 4096 // Default length for the read() instruction
    ];

    /**
     * FileStream constructor.
     * @param string $path
     * @param string $mode
     * @param array $options
     */
    public function __construct(string $path, string $mode = 'r', array $options = [])
    {
        $this->path = $path;
        $this->mode = $mode;
        $this->options = array_merge($this->options, $options);

        $this->handle = fopen($path, $mode);
        if (!$this->handle || !is_resource($this->handle)) {
            throw new \RuntimeException("Stream OPEN operation failed");
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        if ($this->handle) {
            fclose($this->handle);
        }

        $this->handle = null;
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        // @TODO Implement me
        if ($this->handle) {
            fclose($this->handle);
        }

        $this->handle = null;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        // @TODO Implement me
        //return $this->getMetadata('unread_bytes');
        return null;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        if (($pos = ftell($this->handle)) === false) {
            throw new \RuntimeException("Stream TELL operation failed");
        }

        return $pos;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return feof($this->handle);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return (bool)$this->getMetadata('seekable');
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (($pos = fseek($this->handle, $offset, $whence)) === -1) {
            throw new \RuntimeException("Stream SEEK operation failed");
        }

        return $pos;
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @throws \RuntimeException on failure.
     * @link http://www.php.net/manual/en/function.fseek.php
     * @see seek()
     */
    public function rewind()
    {
        //$this->seek(0, SEEK_SET);
        rewind($this->handle);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        return in_array($this->mode, ['a', 'a+', 'w', 'w+']);
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (($written = fwrite($this->handle, $string)) === false) {
            throw new \RuntimeException("Stream WRITE operation failed");
        }

        return $written;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        return in_array($this->mode, ['a', 'a+', 'r', 'r+']);
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (($content = fread($this->handle, $length)) === false) {
            throw new \RuntimeException("Stream READ operation failed");
        }

        return $content;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        $contents = "";
        while(!$this->eof()) {
            $contents .= $this->read($this->options['read_length']);
        }

        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if ($this->handle) {
            $this->meta = stream_get_meta_data($this->handle);
        }

        if ($key === null) {
            return $this->meta;
        }

        return $this->meta[$key] ?? null;
    }
}
