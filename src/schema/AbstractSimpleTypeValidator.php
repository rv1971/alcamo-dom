<?php

namespace alcamo\dom\schema;

use alcamo\dom\Document;

/**
 * @brief Base for classes that validate data of some some XSD simple type
 * against a schema.
 *
 * @date Last reviewed 2021-07-11
 */
abstract class AbstractSimpleTypeValidator
{
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
    private $nsDeclText_ = 'xmlns:xsi="' . Document::XSI_NS . '"';

    /**
     * @brief Create XSD text suitable to validate a sequence of simple data
     * items
     *
     * @param $nsNameSchemaLocationPairs Pairs of NS name and schema location
     */
    public function createXsdText(iterable $nsNameSchemaLocationPairs): string
    {
        $xsdText = self::XSD_TEXT_1;

        foreach ($nsNameSchemaLocationPairs as $pair) {
            $xsdText .=
                "<import namespace='{$pair[0]}' schemaLocation='{$pair[1]}'/>";
        }

        return $xsdText .= self::XSD_TEXT_2;
    }

    /**
     * @brief Create an instance document suitable for validation against the
     * XSD created by createXsdText()
     *
     * @param $valueTypeXNamePairs Nonempty list of pairs consisting
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
     * @brief Validate data against a schema
     *
     * @param $valueTypeXNamePairs Pairs of value and type XName
     *
     * @param $xsdText XSD document text to use for validation
     *
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
