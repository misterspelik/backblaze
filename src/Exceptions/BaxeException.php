<?php

namespace Backblaze\Exceptions;

class BaseException extends \Exception
{

    protected $message;
    protected $default_message = 'Backblaze B2 base exception';

    /**
     * Constructor.
     *
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        $this->message = !empty($message) ? $message : $this->default_message;
    }
}
