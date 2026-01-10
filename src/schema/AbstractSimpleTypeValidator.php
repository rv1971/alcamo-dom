<?php

namespace alcamo\dom\schema;

use alcamo\dom\{Document, NamespaceConstantsInterface};
use alcamo\xml\XName;

/**
 * @brief Base for classes that validate data of some some XSD simple type
 * against a schema.
 *
 * @date Last reviewed 2025-11-07
 */
abstract class AbstractSimpleTypeValidator implements
    NamespaceConstantsInterface
{
    private const XSD_TEXT_1 = "<?xml version='1.0' encoding='UTF-8'?>"
        . "<schema xmlns='http://www.w3.org/2001/XMLSchema' elementFormDefault='qualified'>";

    private const XSD_TEXT_2 = "<element name='x'>"
        . '<complexType>'
        . '<sequence>'
        . "<element name='y' maxOccurs='unbounded'/>"
        . '</sequence>'
        . '</complexType>'
        . '</element>'
        . '</schema>';

    /// Prefix to strip from error messages
    private const ERR_PREFIX = "Element 'y': ";

    /**
     * @brief Create XSD text suitable to validate a sequence of simple data
     * items
     *
     * @param $nsNameToSchemaLocation Map of namespace names to schema
     * locations.
     */
    protected function createXsdText(iterable $nsNameToSchemaLocation): string
    {
        $xsdText = self::XSD_TEXT_1;

        foreach ($nsNameToSchemaLocation as $nsName => $schemaLocation) {
            $xsdText .=
                "<import namespace='{$nsName}' schemaLocation='{$schemaLocation}'/>";
        }

        return $xsdText .= self::XSD_TEXT_2;
    }

    /**
     * @brief Create an instance document suitable for validation against the
     * XSD created by createXsdText()
     *
     * @param $valueTypeXNamePairs Nonempty iterable of pairs consisting of a
     * value and the extended name of a type. The latter may be an XName
     * object or an array consisting of namespace name and local name.
     *
     * @param $keys Will be filled with a nNumerically-indexed array of the
     * keys of $valueTypeXNamePairs, with indexes starting at 0.
     */
    protected function createInstanceDoc(
        iterable $valueTypeXNamePairs,
        ?array &$keys
    ): Document {
        $instanceText = '';

        $nsNameToPrefix = [];

        $nsDeclText = 'xmlns:xsi="' . self::XSI_NS . '"';

        $keys = [];

        $i = 0;
        foreach ($valueTypeXNamePairs as $key => $valueTypeXNamePair) {
            [ $value, $typeXName ] = $valueTypeXNamePair;

            [ $nsName, $localName ] = $typeXName instanceof XName
                ? $typeXName->getPair()
                : $typeXName;

            if (isset($nsName)) {
                $nsPrefix = $nsNameToPrefix[$nsName] ?? null;

                if (!isset($nsPrefix)) {
                    $nsPrefix = 'n' . ++$i;

                    $nsNameToPrefix[$nsName] = $nsPrefix;

                    $nsDeclText .= " xmlns:$nsPrefix='$nsName'";
                }

                $instanceText .=
                    "<y xsi:type='$nsPrefix:$localName'>$value</y>\n";
            } else {
                $instanceText .= "<y xsi:type='$localName'>$value</y>\n";
            }

            $keys[] = $key;
        }

        /* Line breaks must not be changed here because the line number where
         * an errors occurs is used to identify the data pair. */
        return
            Document::newFromXmlText(
                "<?xml version='1.0' encoding='UTF-8'?><x $nsDeclText>\n"
                . "$instanceText\n</x>"
            );
    }

    /**
     * @brief Validate data against a schema
     *
     * @param $valueTypeXNamePairs Nonempty iterable of pairs consisting of a
     * value and the extended name of a type. The latter may be an XName
     * object or an array consisting of namespace name and local name.
     *
     * @param $xsdText XSD document text to use for validation
     *
     * @return Array mapping keys of items in $valueTypeXNamePairs to
     * (potentially multi-line) error messages. Empty array if no errors
     * occurred.
     */
    protected function validateAux(
        iterable $valueTypeXNamePairs,
        string $xsdText
    ): array {
        $doc = $this->createInstanceDoc($valueTypeXNamePairs, $keys);

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $doc->schemaValidateSource($xsdText);

        $prefixLen = strlen(self::ERR_PREFIX);

        $errorMsgs = [];

        foreach (libxml_get_errors() as $libXmlError) {
            /* Warnings are ignored, only errors are reported. In particular,
             * this ignores warning about double import of the same document,
             * which are unavoidable in a complex schema made of many XSDs. */
            if ($libXmlError->level == LIBXML_ERR_WARNING) {
                continue;
            }

            $key = $keys[$libXmlError->line - 2];

            $message = $libXmlError->message;

            if (substr($message, 0, $prefixLen) == self::ERR_PREFIX) {
                $message = rtrim(substr($message, $prefixLen), "\n");
            }

            if (isset($errorMsgs[$key])) {
                $errorMsgs[$key] .= "\n$message";
            } else {
                $errorMsgs[$key] = $message;
            }
        }

        return $errorMsgs;
    }
}
