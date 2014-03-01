<?php

namespace Autodo\Support;

class XmlWriter
{
    public static function arrayToXml($array, $rootName = 'document')
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";

        $xml .= '<' . $rootName . '>' . "\n";
        $xml .= XmlWriter::xmlNodeFromArray($array, $rootName);
        $xml .= '</' . $rootName . '>' . "\n";

        return $xml;
    }

    private static function xmlNodeFromArray($array, $currentNode)
    {
        $xml = '';

        if (is_array($array) || is_object($array))
        {

            foreach ($array as $key => $value)
            {
                if (is_numeric($key))
                {
                    $key = $currentNode;
                }

                $xml .= '<' . $key . '>' . "\n" . XmlWriter::xmlNodeFromArray($value, $currentNode) . '</' . $key . '>' . "\n";
            }
        }
        else
        {
            $xml = htmlspecialchars($array, ENT_QUOTES) . "\n";
        }

        return $xml;
    }
}