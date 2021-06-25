<?php

namespace alcamo\dom\schema;

use alcamo\dom\Document;

abstract class AbstractSimpleTypeValidator
{
    public const XSD_NS = Document::NS['xsd'];

    private const XSD_TEXT_1 = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<schema xmlns="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified">';

    private const XSD_TEXT_2 = '<element name="x">'
        . '<complexType>'
        . '<sequence>'
        . '<element name="y" maxOccurs="unbounded"/>'
        . '</sequence>'
        . '</complexType>'
        . '</element>'
        . '</schema>';

    /// Prefix to strip from error messages
    private const ERR_PREFIX = "Element 'y': ";

    private $nsMap_ = []; ///< Map of namespace names to prefixes

    /// String of namespace declarations
    private $nsDeclText_ = 'xmlns:xsi="' . Document::NS['xsi'] . '"';

    /**
     * @param $nsName2schemaLocation iterable Map of NS names schema locations
     */
    public function createXsdText(iterable $nsName2schemaLocation)
    {
        $xsdText = self::XSD_TEXT_1;

        foreach ($nsName2schemaLocation as $nsName => $schemaLocation) {
            $xsdText .=
                "<import namespace='$nsName' schemaLocation='$schemaLocation'/>";
        }

        return $xsdText .= self::XSD_TEXT_2;
    }

    /**
     * @param $valueTypeXNamePairs iterable Nonempty list of pairs consisting
     * of a value and the XName of a type.
     */
    public function createInstanceDoc(iterable $valueTypeXNamePairs): Document
    {
        $dataText = '';

        foreach ($valueTypeXNamePairs as $valueTypeXNamePair) {
            [ $value, $typeXName ] = $valueTypeXNamePair;

            $nsName = $typeXName->getNsName();

            $nsPrefix = $this->nsMap_[$nsName] ?? null;

            if (!isset($nsPrefix)) {
                $nsPrefix = 'n' . count($this->nsMap_);

                $this->nsMap_[$nsName] = $nsPrefix;

                $this->nsDeclText_ .= " xmlns:$nsPrefix='$nsName'";
            }

            $dataText .=
                "<y xsi:type='$nsPrefix:{$typeXName->getLocalName()}'>$value</y>\n";
        }

        return
            Document::newFromXmlText(
                "<?xml version='1.0' encoding='UTF-8'?><x $this->nsDeclText_>\n"
                . "$dataText\n</x>"
            );
    }

    /**
     * @return Array mapping indexes of items in $valueTypeXNamePairs to
     * (potentially multi-line) error messages. Empty array if no errors
     * occurred.
     */
    protected function validateAux(
        iterable $valueTypeXNamePairs,
        string $xsdText
    ): array {
        $doc = $this->createInstanceDoc($valueTypeXNamePairs);

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $doc->schemaValidateSource($xsdText);

        $prefixLen = strlen(self::ERR_PREFIX);

        $errorMsgs = [];

        foreach (libxml_get_errors() as $libXmlError) {
            $index = $libXmlError->line - 2;

            $message = $libXmlError->message;

            if (substr($message, 0, $prefixLen) == self::ERR_PREFIX) {
                $message = rtrim(substr($message, $prefixLen), "\n");
            }

            if (isset($errorMsgs[$index])) {
                $errorMsgs[$index] .= "\n$message";
            } else {
                $errorMsgs[$index] = $message;
            }
        }

        return $errorMsgs;
    }
}
