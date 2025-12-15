<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\Document as BaseDocument;
use alcamo\dom\schema\{Converter, Schema, SchemaFactory, TypeMap};
use alcamo\exception\DataValidationFailed;

/**
 * @namespace alcamo::dom::psvi
 *
 * @brief DOM classes that make the Post-Schema-Validation Infoset available
 */

/**
 * @brief DOM class for Documents that makes the Post-Schema-Validation
 * Infoset available
 *
 * @date Last reviewed 2025-11-25
 */
class Document extends BaseDocument
{
    /// @copybrief alcamo::dom::Document::LOAD_FLAFS
    public const LOAD_FLAGS = self::VALIDATE_AFTER_LOAD;

    /// @copybrief alcamo::dom::Document::NODE_CLASSES
    public const NODE_CLASSES =
        [
            'DOMAttr'    => Attr::class,
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASSES;

    /** @copybrief alcamo::dom::Document::DEFAULT_DOCUMENT_FACTORY_CLASS */
    public const DEFAULT_DOCUMENT_FACTORY_CLASS = DocumentFactory::class;

    public const SCHEMA_FACTORY_CLASS = SchemaFactory::class;

    /// Map of XSD type XNames to conversion functions
    public const TYPE_CONVERTER_MAP = Converter::TYPE_CONVERTER_MAP;

    /**
     * @brief Map of XSD type XNames to decorator classes for elements
     *
     * To be overridden in derived classes.
     */
    public const ELEMENT_DECORATOR_MAP = [];

    public const IDREF_XNAME  = self::XSD_NS . ' IDREF';
    public const IDREFS_XNAME = self::XSD_NS . ' IDREFS';

    private $schema_;              ///< Schema
    private $converter_;           ///< Converter
    private $elementDecoratorMap_; ///< TypeMap

    /// Get schema obtained from `xsi:schemaLocation`
    public function getSchema(): Schema
    {
        if (!isset($this->schema_)) {
            $class = static::SCHEMA_FACTORY_CLASS;

            $schemaFactory = new $class($this->getDocumentFactory());

            $this->schema_ = $schemaFactory->createFromDocument($this);
        }

        return $this->schema_;
    }

    /// Get type map used to convert node values
    public function getConverter(): Converter
    {
        if (!isset($this->converter_)) {
            $this->converter_ = new Converter(
                $this->getSchema(),
                new TypeMap(static::TYPE_CONVERTER_MAP)
            );
        }

        return $this->converter_;
    }

    /// Get map of XSD element types to decorator classes
    public function getElementDecoratorMap(): TypeMap
    {
        if (!isset($this->elementDecoratorMap_)) {
            $this->elementDecoratorMap_ =
                new TypeMap(static::ELEMENT_DECORATOR_MAP);
        }

        return $this->elementDecoratorMap_;
    }

    /// Validate that IDREF[S] refer to existing IDs
    public function validateIdrefs()
    {
        /**
         * @attention This method may be expensive because it iterates over
         * *all* attributes in the document.
         */
        foreach ($this->query('//@*') as $attr) {
            switch ((string)$attr->getType()->getXName()) {
                case self::IDREF_XNAME:
                    if (!isset($this[$attr->value])) {
                        /** @throw alcamo::exception::DataValidationFailed
                         *  when encountering an IDREF the refers to a
                         *  non-existing ID. */
                        throw (new DataValidationFailed())->setMessageContext(
                            [
                                'inData' => $this->saveXML(),
                                'atUri' => $this->documentURI,
                                'atLine' => $attr->getLineNo(),
                                'extraMessage' => "no ID found for IDREF \"$attr\""
                            ]
                        );
                    }

                    break;

                case self::IDREFS_XNAME:
                    foreach ($attr->getValue() as $idref) {
                        if (!isset($this[$idref])) {
                            /** @throw alcamo::exception::DataValidationFailed
                             *  when encountering an IDREFS the refers to a
                             *  non-existing ID. */
                            throw (new DataValidationFailed())
                                ->setMessageContext(
                                    [
                                        'inData' => $this->saveXML(),
                                        'atUri' => $this->documentURI,
                                        'atLine' => $attr->getLineNo(),
                                        'extraMessage' => "no ID found for IDREFS item \"$idref\""
                                    ]
                                );
                        }
                    }

                    break;
            }
        }
    }

    public function clearCache(): void
    {
        parent::clearCache();

        $this->schema_ = null;
        $this->converter_ = null;
        $this->elementDecoratorMap_ = null;
    }
}
