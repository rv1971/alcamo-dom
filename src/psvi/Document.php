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
        self::NS['xh11d'] . ' CURIE'          => CP::class . '::curieToUri',
        self::NS['xh11d'] . ' SafeCURIE'      => CP::class . '::safeCurieToUri',
        self::NS['xh11d'] . ' URIorSafeCURIE' => CP::class . '::uriOrSafeCurieToUri',

        self::NS['xsd'] . ' anyURI'       => CP::class . '::toUri',
        self::NS['xsd'] . ' base64Binary' => CP::class . '::base64ToBinary',
        self::NS['xsd'] . ' boolean'      => CP::class . '::toBool',
        self::NS['xsd'] . ' date'         => CP::class . '::toDateTime',
        self::NS['xsd'] . ' dateTime'     => CP::class . '::toDateTime',
        self::NS['xsd'] . ' decimal'      => CP::class . '::toFloat',
        self::NS['xsd'] . ' double'       => CP::class . '::toFloat',
        self::NS['xsd'] . ' duration'     => CP::class . '::toDuration',
        self::NS['xsd'] . ' float'        => CP::class . '::toFloat',
        self::NS['xsd'] . ' hexBinary'    => CP::class . '::hexToBinary',
        self::NS['xsd'] . ' integer'      => CP::class . '::toInt',
        self::NS['xsd'] . ' language'     => CP::class . '::toLang',
        self::NS['xsd'] . ' QName'        => CP::class . '::toXName'
    ];

    public const NODE_CLASS =
        [
            'DOMAttr'    => Attr::class,
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASS;

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
        static $idrefName  = self::NS['xsd'] . ' IDREF';
        static $idrefsName = self::NS['xsd'] . ' IDREFS';

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
