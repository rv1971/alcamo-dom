<?php

namespace alcamo\dom;

/**
 * @brief Modify XML documents in various ways
 */
class DocumentModifier implements NamespaceConstantsInterface
{
    /// Validate document after modification
    public const VALIDATE = 1;

    /**
     * @brief Pretty-format and re-parse the document
     *
     * This is useful to get reasonable line numbers after changes (for
     * instance, after xinclude()) because otherwise all nodes keep their line
     * numbers.
     */
    public const FORMAT_AND_REPARSE = 2;

    /// XPath to find all \<xsd:documentation> elements
    private const ALL_DOCUMENTATION_XPATH = '//xsd:documentation';

    /**
     * @brief Reparse - useful to get line numbers right after changes, for
     * instance after xinclude()
     *
     * @param $document The document object that will be modified.
     *
     * @return The $document object after modification. The document is
     * modified itself, no new document is created.
     */
    public function reparse(Document $document): Document
    {
        $uri = $document->documentURI;

        $document->formatOutput = true;

        $document->loadXML($document->saveXML(), $document->getLibxmlOptions());

        $document->documentURI = $uri;

        $document->clearCache();

        return $document;
    }

    /**
     * @brief Remove all \<xsd:documentation> elements
     *
     * @param $document The document object that will be modified.
     *
     * @param $flags OR-Combination of the above class constant flags.
     *
     * @return The $document object after modification. The document is
     * modified itself, no new document is created.
     *
     * Also remove any \<xsd:annotation> elements that have become empty
     * document way.
     */
    public function stripXsdDocumentation(
        Document $document,
        ?int $flags = null
    ): Document {
        foreach (
            $document->query(self::ALL_DOCUMENTATION_XPATH) as $xsdElement
        ) {
            $parent = $xsdElement->parentNode;

            $parent->removeChild($xsdElement);

            if (
                !isset($parent->firstChild)
                && $parent->namespaceURI == self::XSD_NS
                && $parent->localName == 'annotation'
            ) {
                $parent->parentNode->removeChild($parent);
            }
        }

        if ($flags & self::FORMAT_AND_REPARSE) {
            $this->reparse($document);
        }

        if ($flags & self::VALIDATE) {
            (new DocumentValidator())->validate($document);
        }

        return $document;
    }
}
