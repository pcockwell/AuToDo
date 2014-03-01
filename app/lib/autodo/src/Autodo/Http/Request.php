<?php

namespace Autodo\Http;

use Illuminate\Http\Request as BaseRequest;

class Request extends BaseRequest
{

    /**
     * Determine if the request is sending JSON.
     *
     * @return bool
     */
    public function isXml()
    {
        return str_contains($this->header('CONTENT_TYPE'), '/xml');
    }

    /**
     * Determine if the current request is asking for JSON in return.
     *
     * @return bool
     */
    public function wantsXml()
    {
        $acceptable = $this->getAcceptableContentTypes();

        return isset($acceptable[0]) and $acceptable[0] == 'application/xml';
    }

}