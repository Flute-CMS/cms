<?php

namespace Flute\Core\Exceptions;

use Exception;

class ForcedRedirectException extends Exception
{
    protected $url;

    protected $statusCode;

    public function __construct($url, $statusCode = 302)
    {
        $this->url = $url;
        $this->statusCode = $statusCode;
        parent::__construct("Forced redirect to {$url}");
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
