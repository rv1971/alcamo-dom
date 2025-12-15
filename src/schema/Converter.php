<?php

namespace alcamo\dom\schema;

use alcamo\dom\{
    ConverterPool as CP,
    DomNodeInterface,
    NamespaceConstantsInterface
};
use alcamo\dom\schema\component\{
    EnumerationTypeInterface,
    ListType,
    SimpleTypeInterface
};
use alcamo\exception\DataNotFound;

/**
 * @brief Convert a string to a PHP item based on a given type
 */
class Converter implements NamespaceConstantsInterface
{
    /// Map of XSD type XNames to conversion functions
    public const TYPE_CONVERTER_MAP = [
        self::XH11D_NS . ' CURIE'          => CP::class . '::curieToUri',
        self::XH11D_NS . ' SafeCURIE'      => CP::class . '::safeCurieToUri',
        self::XH11D_NS . ' URIorSafeCURIE' => CP::class . '::uriOrSafeCurieToUri',

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

    private static $builtinConverter_; ///< Converter

    private $schema_;                  ///< Schema
    private $typeConverters_;          ///< TypeMap

    public static function getBuiltinConverter(): self
    {
        if (!isset(self::$builtinConverter_)) {
            self::$builtinConverter_ = new Converter(
                (new SchemaFactory())->getBuiltinSchema(),
                new TypeMap(static::TYPE_CONVERTER_MAP)
            );
        }

        return self::$builtinConverter_;
    }

    public function __construct(Schema $schema, TypeMap $typeConverters)
    {
        $this->schema_ = $schema;
        $this->typeConverters_ = $typeConverters;
    }

    public function getSchema(): Schema
    {
        return $this->schema_;
    }

    /**
     * @brief Convert a string to an PHP representation of the given type
     *
     * @param $type SimpleTypeInterface|string Data type, or type XName as
     * string
     */
    public function convert(
        string $value,
        DomNodeInterface $context,
        $type
    ) {
        if (!($type instanceof SimpleTypeInterface)) {
            $typeXName = $type;
            $type = $this->schema_->getGlobalType($typeXName);

            if (!isset($type)) {
                throw (new DataNotFound())->setMessageContext(
                    [
                        'inData' => $typeXName,
                        'extraMessage' => "Type $typeXName not found"
                    ]
                );
            }
        }

        $converter = $this->typeConverters_->lookup($type);

        /** - If there is a specific converter for the type or one of its base
         *  types, use it. */
        if (isset($converter)) {
            return $converter($value, $context);
        }

        /**- Otherwise, if the type is an enumeration, convert to the
         * enumerator object. */
        if ($type instanceof EnumerationTypeInterface) {
            return $type->getEnumerators()[$value];
        }

        /** - Otherwise, if the type is a list type, convert the value to
         * an array by splitting at whitespace. */
        if ($type instanceof ListType) {
            $value = preg_split('/\s+/', $value);

            $itemType = $type->getItemType();
            $converter = $converters->lookup($itemType);

            /** - If the type is a list type and there is a converter for the
             * item type, replace the value by an associative array, mapping
             * each item literal to its conversion result. */
            if (isset($converter)) {
                $convertedValue = [];

                foreach ($value as $item) {
                    $convertedValue[$item] = $converter($item, $this);
                }

                return $convertedValue;
            }

            /** - Otherwise, if the type is a list type and the items are
             * enumerators, replace the value by an associative array,
             * mapping each item literal to its enumerator object.
             *
             * @attention The last two alternatives implies that repeated
             * items will silently be dropped in the last two cases. To model
             * such cases with possible repetition, an explicit converter for
             * the list type is necessary. */
            if ($itemType instanceof EnumerationTypeInterface) {
                $convertedValue = [];

                $enumerators = $itemType->getEnumerators();

                foreach ($value as $item) {
                    $convertedValue[$item] = $enumerators[$item];
                }

                return $convertedValue;
            }
        }

        return $value;
    }
}
