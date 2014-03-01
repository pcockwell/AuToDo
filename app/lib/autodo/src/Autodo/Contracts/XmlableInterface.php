<?php namespace Autodo\Contracts;

interface XmlableInterface {

    /**
     * Convert the object to its XML representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toXml($rootNode = 'document');

}