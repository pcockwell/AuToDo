<?php namespace Autodo\Database\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Autodo\Contracts\XmlableInterface;
use Autodo\Support\XmlWriter;

abstract class Model extends BaseModel implements XmlableInterface {

    /**
     * Convert the model instance to XML.
     *
     * @param  int  $options
     * @return string
     */
    public function toXml($rootNode = 'model')
    {
        $rootNode = get_class($this) ?: 'model';

        return XmlWriter::arrayToXml($this->toArray(), $rootNode);
    }
}
