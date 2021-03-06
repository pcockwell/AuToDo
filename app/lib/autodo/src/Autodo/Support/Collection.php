<?php namespace Autodo\Support;

use Illuminate\Support\Collection as BaseCollection;
use Autodo\Contracts\XmlableInterface;
use Autodo\Support\XmlWriter;

class Collection extends BaseCollection implements XmlableInterface {

    /**
     * Convert the model instance to XML.
     *
     * @param  int  $options
     * @return string
     */
    public function toXml($rootNode = 'collection')
    {
        return XmlWriter::arrayToXml($this->toArray(), $rootNode);
    }

}
