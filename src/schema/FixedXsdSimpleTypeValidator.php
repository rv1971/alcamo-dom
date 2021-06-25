<?php

namespace alcamo\dom\schema;

class FixedXsdSimpleTypeValidator extends AbstractSimpleTypeValidator
{
    private $xsdText_; ///< XSD document as string

    public static function newFromSchema(Schema $schema): self
    {
        return static::newFromXsds($schema->getXsds());
    }

    /**
     * @param $xsds iterable List of XSDs as DOMDocuments
     */
    public static function newFromXsds(iterable $xsds): self
    {
        $nsName2schemaLocation = [];

        foreach ($xsds as $xsd) {
            $nsName2schemaLocation[
                $xsd->documentElement->getAttribute('targetNamespace')
            ] = $xsd->documentURI;
        }

        return new self($nsName2schemaLocation);
    }

    public function __construct(iterable $nsName2schemaLocation)
    {
        $this->xsdText_ = $this->createXsdText($nsName2schemaLocation);
    }

    public function getXsdText(): string
    {
        return $this->xsdText_;
    }

    public function validate($valueTypeXNamePairs): array
    {
        return $this->validateAux($valueTypeXNamePairs, $this->xsdText_);
    }
}
