<?php

namespace alcamo\dom\psvi;

use alcamo\dom\{ConverterPool as CP, DocumentFactoryInterface, ValidationTrait};
use alcamo\dom\decorated\Document as BaseDocument;
use alcamo\dom\schema\{Schema, TypeMap};
use alcamo\exception\DataValidationFailed;

/**
 * @namespace alcamo::dom::psvi
 *
 * @brief DOM classes that make the Post-Schema-Validation Infoset available
 */

/**
 * @brief DOM class for %Documents that make the Post-Schema-Validation
 * Infoset available
 *
 * @date Last reviewed 2021-07-11
 */
class Document extends BaseDocument
{
    public const LOAD_FLAGS = self::VALIDATE_AFTER_LOAD;

    /// @copybrief alcamo::dom::Document::NODE_CLASSES
    public const NODE_CLASSES =
        [
            'DOMAttr'    => Attr::class,
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASSES;

    /// Map of XSD type XNames to conversion functions for attribute values
    public const ATTR_TYPE_MAP = [
        self::NSS['xh11d'] . ' CURIE'          => CP::class . '::curieToUri',
        self::NSS['xh11d'] . ' SafeCURIE'      => CP::class . '::safeCurieToUri',
        self::NSS['xh11d'] . ' URIorSafeCURIE' => CP::class . '::uriOrSafeCurieToUri',

        self::XSD_NS . ' anyURI'       => CP::class . '::toUri',
        self::XSD_NS . ' base64Binary' => CP::class . '::base64ToBinary',
        self::XSD_NS . ' boolean'      => CP::class . '::toBool',
        self::XSD_NS . ' date'         => CP::class . '::toDateTime',
        self::XSD_NS . ' dateTime'     => CP::class . '::toDateTime',
        self::XSD_NS . ' decimal'      => CP::class . '::toFloat',
        self::XSD_NS . ' double'       => CP::class . '::toFloat',
        self::XSD_NS . ' duration'     => CP::class . '::toDuration',
        self::XSD_NS . ' float'        => CP::class . '::toFloat',
        self::XSD_NS . ' hexBinary'    => CP::class . '::hexToBinary',
        self::XSD_NS . ' integer'      => CP::class . '::toInt',
        self::XSD_NS . ' language'     => CP::class . '::toLang',
        self::XSD_NS . ' QName'        => CP::class . '::toXName'
    ];

    /**
     * @brief Map of XSD type XNames to decorator classes for elements
     *
     * To be overridden in derived classes.
     */
    public const ELEMENT_DECORATOR_MAP = [];

    public const IDREF_XNAME  = self::XSD_NS . ' IDREF';
    public const IDREFS_XNAME = self::XSD_NS . ' IDREFS';

    private $schema_;              ///< Schema
    private $attrConverters_;      ///< TypeMap
    private $elementDecoratorMap_; ///< TypeMap

    /// @copybrief alcamo::dom::Document::getDocumentFactory()
    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return new DocumentFactory();
    }

    /// Schema obtained from `xsi:schemaLocation`
    public function getSchema(): Schema
    {
        if (!isset($this->schema_)) {
            $this->schema_ = Schema::newFromDocument($this);
        }

        return $this->schema_;
    }

    /// Type map used to convert attribute values
    public function getAttrConverters(): TypeMap
    {
        if (!isset($this->attrConverters_)) {
            $this->attrConverters_ = TypeMap::newFromSchemaAndXNameMap(
                $this->getSchema(),
                static::ATTR_TYPE_MAP
            );
        }

        return $this->attrConverters_;
    }

    /// Map of XSD element types to decorator classes
    public function getElementDecoratorMap(): TypeMap
    {
        if (!isset($this->elementDecoratorMap_)) {
            $this->elementDecoratorMap_ = TypeMap::newFromSchemaAndXNameMap(
                $this->getSchema(),
                static::ELEMENT_DECORATOR_MAP
            );
        }

        return $this->elementDecoratorMap_;
    }

    /// Validate that IDREF[S] refer to existing IDs
    public function validateIdrefs()
    {
        /**
         * @note This method may be expensive because it iterates over *all*
         * attributes in the document.
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
}
