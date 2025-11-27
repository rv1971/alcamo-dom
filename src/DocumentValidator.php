<?php

namespace alcamo\dom;

use alcamo\exception\DataValidationFailed;

/**
 * @brief Checks whether XML documents are valid
 */
class DocumentValidator implements NamespaceConstantsInterface
{
    private $libxmlFlags_; ///< int

    /**
     * @param $libxmlFlags See $flags in
     * [DOMDocument::schemaValidate()](https://www.php.net/manual/en/domdocument.schemavalidate)
     */
    public function __construct(?int $libxmlFlags = null)
    {
        $this->libxmlFlags_ = (int)$libxmlFlags;
    }

    public function getLibxmlFlags(): int
    {
        return $this->libxmlFlags_;
    }

    /** @return Map of namespace to absolute Uri. Empty if there is no
     *  `schemaLocation` attribute. */
    public function createSchemaLocationsMap(\DOMDocument $document): ?array
    {
        $documentElement = $document->documentElement;

        if (
            !$documentElement->hasAttributeNS(self::XSI_NS, 'schemaLocation')
        ) {
            return null;
        }

        $items = preg_split(
            '/\s+/',
            $documentElement->getAttributeNS(self::XSI_NS, 'schemaLocation')
        );

        $schemaLocationsMap = [];

        for ($i = 0; isset($items[$i]); $i += 2) {
            $schemaLocationsMap[$items[$i]] = $items[$i + 1];
        }

        return $schemaLocationsMap;
    }

    /// Validate against an XSD given by its URI
    public function validateAgainstXsdUri(
        \DOMDocument $document,
        string $xsdUri
    ): \DOMDocument {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            if (!$document->schemaValidate($xsdUri, $this->libxmlFlags_)) {
                $this->processLibxmlErrors($document);
            }
        } catch (\Throwable $e) {
            /** @throw alcamo::exception::DataValidationFailed if validation
             *  fails. */
            throw DataValidationFailed::newFromPrevious(
                $e,
                [
                    'inData' => $document->saveXML(),
                    'atUri' => $document->documentURI
                ]
            );
        }

        return $document;
    }

    /// Validate against an XSD  supplied as text
    public function validateAgainstXsdText(
        \DOMDocument $document,
        string $xsdText
    ): \DOMDocument {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            if (
                !$document
                    ->schemaValidateSource($xsdText, $this->libxmlFlags_)
            ) {
                $this->processLibxmlErrors($document);
            }
        } catch (\Throwable $e) {
            /** @throw alcamo::exception::DataValidationFailed if validation
             *  fails. */
            throw DataValidationFailed::newFromPrevious(
                $e,
                [
                    'inData' => $document->saveXML(),
                    'atUri' => $document->documentURI
                ]
            );
        }

        return $document;
    }

    /**
     * @brief Validate with schemas given in xsi:schemaLocation or
     * xsi:noNamespaceSchemaLocation.
     *
     * Silently do nothing if none of the two is present.
     */
    public function validate(Document $document): Document
    {
        $documentElement = $document->documentElement;

        if (
            $documentElement
                ->hasAttributeNS(self::XSI_NS, 'noNamespaceSchemaLocation')
        ) {
            $noNamespaceSchemaLocation = $documentElement->getAttributeNS(
                self::XSI_NS,
                'noNamespaceSchemaLocation'
            );

            return $this->validateAgainstXsdUri(
                $document,
                $documentElement->resolveUri($noNamespaceSchemaLocation)
                    ?? $noNamespaceSchemaLocation
            );
        }

        if (!$documentElement->hasAttributeNS(self::XSI_NS, 'schemaLocation')) {
            return $document;
        }

        /**
         * In the case of `schemaLocation`, create an XSD importing all
         *  mentioned schemas and validate against it.
         */

        $xmlBaseAttr = isset($documentElement->baseURI)
            ? "xml:base=\"{$documentElement->baseURI}\""
            : '';

        $xsdText =
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<schema xmlns="http://www.w3.org/2001/XMLSchema" '
            . 'targetNamespace="' . self::ALCAMO_DOM_NS . 'validate#" '
            . "$xmlBaseAttr>";

        foreach (
            $this->createSchemaLocationsMap($document) as $nsName => $schemaUri
        ) {
            $xsdText .=
                "<import namespace='$nsName' schemaLocation='$schemaUri'/>";
        }

        $xsdText .= '</schema>';

        return $this->validateAgainstXsdText($document, $xsdText);
    }

    private function processLibxmlErrors(\DOMDocument $document): void
    {
        $messages = [];

        foreach (libxml_get_errors() as $error) {
            /** Suppress warning "namespace was already imported". */
            if (
                strpos($error->message, 'namespace was already imported')
                !== false
            ) {
                continue;
            }

            $messages[] = "$error->file:$error->line $error->message";
        }

        /** @throw alcamo::exception::DataValidationFailed when
         *  encountering validation errors. */
        throw (new DataValidationFailed())->setMessageContext(
            [
                'inData' => $document->saveXML(),
                'atUri' => $document->documentURI,
                'extraMessage' => implode("\n", $messages)
            ]
        );
    }
}
