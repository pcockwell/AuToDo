<?php namespace Autodo\Pagination;

use Illuminate\Support\Contracts\XmlableInterface;
use Illuminate\Pagination\Paginator as BasePaginator;
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
