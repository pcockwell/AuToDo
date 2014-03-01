<?php namespace Autodo\Support;

use Illuminate\Support\Contracts\XmlableInterface;
use Illuminate\Support\MessageBag as BaseMessageBag;
use Autodo\Support\XmlWriter;

class MessageBag extends BaseMessageBag implements XmlableInterface {

    /**
     * Convert the model instance to XML.
     *
     * @param  int  $options
     * @return string
     */
    public function toXml($rootNode = 'messagebag')
    {
        return XmlWriter::arrayToXml($this->toArray(), $rootNode);
    }

}
