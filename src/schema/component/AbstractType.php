<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\Schema;
use alcamo\rdfa\{LangStringLiteral, RdfaData, RdfsLabel};

/**
 * @brief Type definition
 */
abstract class AbstractType extends AbstractXsdComponent implements
    TypeInterface
{
    /// RDFa properties not to inherit to derived types
    public const NO_INHERIT_PROPS = [ 'dc:title', 'rdfs:label' ];

    /**
     * @brief Factory method creating the most specific type that it can
     * recognize
     */
    public static function newFromSchemaAndXsdElement(
        Schema $schema,
        XsdElement $xsdElement
    ): self {
        return $xsdElement->localName == 'simpleType'
            ? AbstractSimpleType::newFromSchemaAndXsdElement(
                $schema,
                $xsdElement
            )
            : new ComplexType($schema, $xsdElement);
    }

    protected $baseType_; ///< ?TypeInterface

    private $rdfaData_ = false; ///< RdfData

    /**
     * The $baseType parameter has no type declaration because ComplexType
     * initializes it with `false` to mark it as uninitialized.
     */
    public function __construct(
        Schema $schema,
        XsdElement $xsdElement,
        $baseType = null
    ) {
        parent::__construct($schema, $xsdElement);

        $this->baseType_ = $baseType;
    }

    public function getBaseType(): ?TypeInterface
    {
        return $this->baseType_;
    }

    /**
     * @copydoc alcamo::dom::schema::component::TypeInterface::getRdfaData()
     *
     * Any statement in a type replaces all statements about the same
     * property in its base type.
     */
    public function getRdfaData(): ?RdfaData
    {
        if ($this->rdfaData_ === false) {
            $baseType = $this->getBaseType();

            if ($baseType instanceof self) {
                $baseRdfaData = clone $baseType->getRdfaData();

                foreach (static::NO_INHERIT_PROPS as $prop) {
                    unset($baseRdfaData[$prop]);
                }
            }

            if (isset($baseRdfaData)) {
                $this->rdfaData_ = $baseRdfaData->replace(
                    $this->getXsdElement()->getRdfaData()
                );
            } else {
                $this->rdfaData_ = clone $this->getXsdElement()->getRdfaData();
            }

            /** If there is no explicit language-agnostic `rdfs:label`, use
             *  the type name as a fallback, considered as
             *  language-agnostic. */
            if (
                !$this->rdfaData_->findStmtWithLang('rdfs:label', '-', true)
                && $this->getXName()
            ) {
                $this->rdfaData_->addStmt(
                    new RdfsLabel(
                        new LangStringLiteral(
                            $this->getXName()->getLocalName()
                        )
                    )
                );
            }
        }

        return $this->rdfaData_;
    }
}
