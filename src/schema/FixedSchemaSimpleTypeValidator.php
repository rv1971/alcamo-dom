<?php

namespace alcamo\dom\schema;

/**
 * @brief Class that validates data of some XSD simple type against a fixed
 * schema.
 *
 * @date Last reviewed 2021-07-11
 */
class FixedSchemaSimpleTypeValidator extends AbstractSimpleTypeValidator
{
    private $xsdText_; ///< XSD document as string

    public static function newFromSchema(Schema $schema): self
    {
        return static::newFromXsds($schema->getXsds());
    }

    /**
     * @param $xsds Collection of XSDs as DOMDocument objects
     *
     * @warning The XSDs must have distinct target namespaces.
     */
    public static function newFromXsds(iterable $xsds): self
    {
        $nsNameToSchemaLocation = [];

        foreach ($xsds as $xsd) {
            $nsNameToSchemaLocation[
                $xsd->documentElement->getAttribute('targetNamespace')
            ] = $xsd->documentURI;
        }

        return new self($nsNameToSchemaLocation);
    }

    /**
     * @param $nsNameToSchemaLocation Map of namespace names to schema locations
     */
    public function __construct(iterable $nsNameToSchemaLocation)
    {
        $this->xsdText_ = $this->createXsdText($nsNameToSchemaLocation);
    }

    public function getXsdText(): string
    {
        return $this->xsdText_;
    }

    /**
     * @brief Validate data
     *
     * @param $valueTypeXNamePairs Pairs of value and type XName
     *
     * @return Array mapping indexes of items in $valueTypeXNamePairs to
     * (potentially multi-line) error messages. Empty array if no errors
     * occurred.
     */
    public function validate($valueTypeXNamePairs): array
    {
        return $this->validateAux($valueTypeXNamePairs, $this->xsdText_);
    }
}
