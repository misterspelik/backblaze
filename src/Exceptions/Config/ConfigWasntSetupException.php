<?php

namespace Backblaze\Exceptions\Config;

use Backblaze\Exceptions\BaseException;

class ConfigWasntSetupException extends BaseException
{
    protected $default_message = 'Configuration was not set Up. Please specify KeyID, Key and Bucket Name.';
}