<?php

namespace Autodo\Http;

use Illuminate\Http\Response as BaseResponse;
use Illuminate\Support\Facades\Input;
use Autodo\Contracts\XmlableInterface;
use Autodo\Support\XmlWriter;

class Response extends BaseResponse
{
    /**
     * Set the content on the response.
     *
     * @param  mixed  $content
     * @return void
     */
    public function setContent($content)
    {
        $this->original = $content;

        $outputFormat = $this->getOutputFormat($content);

        if ($outputFormat)
        {
            $this->headers->set('Content-Type', 'application/' . $outputFormat);

            $morphFunction = 'morphTo' . ucwords($outputFormat);

            $content = $this->{$morphFunction}($content);
        }

        // If the content is "JSONable" we will set the appropriate header and convert
        // the content to JSON. This is useful when returning something like models
        // from routes that will be automatically transformed to their JSON form.
        else if ($this->shouldBeJson($content))
        {
            $this->headers->set('Content-Type', 'application/json');

            $content = $this->morphToJson($content);
        }

        // If this content implements the "RenderableInterface", then we will call the
        // render method on the object so we will avoid any "__toString" exceptions
        // that might be thrown and have their errors obscured by PHP's handling.
        elseif ($content instanceof RenderableInterface)
        {
            $content = $content->render();
        }

        return parent::setContent($content);
    }

    public function getOutputFormat($content)
    {
        if (Input::wantsJson() && $this->shouldBeJson($content)) return 'json';
        if (Input::wantsXml() && $this->shouldBeXml($content)) return 'xml';
        if (Input::isJson() && $this->shouldBeJson($content)) return 'json';
        if (Input::isXml() && $this->shouldBeXml($content)) return 'xml';

        return false;
    }

    /**
     * Morph the given content into Xml.
     *
     * @param  mixed   $content
     * @return string
     */
    protected function morphToXml($content)
    {
        if ($content instanceof XmlableInterface) return $content->toXml();

        return XmlWriter::arrayToXml((array) $content);
    }

    /**
     * Determine if the given content should be turned into JSON.
     *
     * @param  mixed  $content
     * @return bool
     */
    protected function shouldBeXml($content)
    {
        return ($content instanceof XmlableInterface or
                $content instanceof ArrayObject or
                is_array($content));
    }
}