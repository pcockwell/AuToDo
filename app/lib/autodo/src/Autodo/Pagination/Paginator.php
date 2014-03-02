<?php namespace Autodo\Pagination;

use Illuminate\Pagination\Paginator as BasePaginator;
use Autodo\Contracts\XmlableInterface;
use Autodo\Support\XmlWriter;

class Paginator extends BasePaginator implements XmlableInterface {

    /**
     * Convert the model instance to XML.
     *
     * @param  int  $options
     * @return string
     */
    public function toXml($rootNode = 'paginator')
    {
        return XmlWriter::arrayToXml($this->toArray(), $rootNode);
    }

}
