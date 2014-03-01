<?php

namespace Autodo\Http;

use Illuminate\Http\Request as BaseRequest;
use Symfony\Component\HttpFoundation\ParameterBag;

class Request extends BaseRequest
{

    /**
     * Get the XML payload for the request.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function xml($key = null, $default = null)
    {
        if ( ! isset($this->xml))
        {
            $xml = new \SimpleXMLElement($this->getContent(), true);
            $this->xml = new ParameterBag(json_decode(json_encode($xml), TRUE));
        }

        if (is_null($key)) return $this->xml;

        return array_get($this->xml->all(), $key, $default);
    }

    /**
     * Get the input source for the request.
     *
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    protected function getInputSource()
    {
        if ($this->isJson()) return $this->json();
        if ($this->isXml()) return $this->xml();

        return $this->getMethod() == 'GET' ? $this->query : $this->request;
    }

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