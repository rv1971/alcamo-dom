<?php

namespace alcamo\dom\psvi;

use alcamo\dom\{ConverterPool as CP, DocumentFactoryInterface, ValidationTrait};
use alcamo\dom\extended\Document as BaseDocument;
use alcamo\dom\schema\{Schema, TypeMap};
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\DataValidationFailed;

class Document extends BaseDocument
{
    use ValidationTrait;

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

    public const NODE_CLASSES =
        [
            'DOMAttr'    => Attr::class,
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASSES;

    private $schema_;         ///< Schema object.
    private $attrConverters_; ///< TypeMap

    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return new DocumentFactory();
    }

    public function getSchema(): Schema
    {
        if (!isset($this->schema_)) {
            $this->schema_ = Schema::newFromDocument($this);
        }

        return $this->schema_;
    }

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

    public function validateIdrefs()
    {
        static $idrefName  = self::XSD_NS . ' IDREF';
        static $idrefsName = self::XSD_NS . ' IDREFS';

        foreach ($this->query('//@*') as $attr) {
            switch ((string)$attr->getType()->getXName()) {
                case $idrefsName:
                    foreach ($attr->getValue() as $idref) {
                        if (!isset($this[$idref])) {
                            throw new DataValidationFailed(
                                $this->saveXML(),
                                $this->documentURI,
                                $attr->getLineNo(),
                                "; no ID found for IDREF \"$idref\""
                            );
                        }
                    }

                    break;

                case $idrefName:
                    if (!isset($this[(string)$attr])) {
                        throw new DataValidationFailed(
                            $this->saveXML(),
                            $this->documentURI,
                            $attr->getLineNo(),
                            "; no ID found for IDREF \"$attr\""
                        );
                    }

                    break;
            }
        }
    }
}
