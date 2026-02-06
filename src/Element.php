<?php

namespace alcamo\dom;

use alcamo\exception\AbsoluteUriNeeded;
use alcamo\rdfa\Lang;
use alcamo\xml\HavingXNameInterface;
use alcamo\uri\{Uri, UriNormalizer};
use Psr\Http\Message\UriInterface;

/**
 * @brief Element class for use in DOMDocument::registerNodeClass()
 *
 * The IteratorAggregate interface is served with iteration over child
 * elements.
 *
 * @date Last reviewed 2025-10-23
 */
class Element extends \DOMElement implements
    DomNodeInterface,
    HavingXNameInterface,
    \IteratorAggregate,
    XPathQueryableInterface
{
    use DomNodeTrait;
    use HavingXNameTrait;

    /// Return [textContent](https://www.php.net/manual/en/class.domnode#domnode.props.textcontent)
    public function __toString(): string
    {
        return $this->textContent;
    }

    /**
     * @brief Return the element value appropriately converted
     *
     * This implementation simply returns the text content. It is meant to
     * be overriden by more sophisticated methods in derived classes.
     */
    public function getValue()
    {
        return $this->textContent;
    }

    /// Return an alcamo::dom::ChildElementsIterator on this element
    public function getIterator()
    {
        return new ChildElementsIterator($this);
    }

    /// Run XPath query with this node as the context node
    public function query(string $expr)
    {
        return $this->ownerDocument->getXPath()->query($expr, $this);
    }

    /// Run and evaluate XPath query with this node as the context node
    public function evaluate(string $expr)
    {
        return $this->ownerDocument->getXPath()->evaluate($expr, $this);
    }

    /// Return xml:lang of element or closest ancestor
    public function getLang(): ?Lang
    {
        /* For efficiency, first check if the element itself has an
         * xml:lang attribute since this is a frequent case in
         * practice. */
        if ($this->hasAttributeNS(Document::XML_NS, 'lang')) {
            return Lang::newFromString(
                $this->getAttributeNS(Document::XML_NS, 'lang')
            );
        } else {
            /* If it does not, look for the first ancestor having such an
             * attribute. */
            $langAttr = $this->query('ancestor::*[@xml:lang][1]/@xml:lang')[0];

            if (isset($langAttr)) {
                return Lang::newFromString($langAttr->value);
            } else {
                return null;
            }
        }
    }

    /**
     * @brief Get the first element along the `descendant-or-self` axis which
     * is semantically the same as a given URI
     *
     * I.e. return the first element that declares itself to be the same as
     * $uri. The result may be the context element itself. Return `null` if no
     * such element is found.
     *
     * @param $uri Absolute URI which is compared to `owl:sameAs` attributes,
     * the latter resolved to absolute URIs.
     *
     * @param $normalizations For comparison, both $uri and the values of
     * `owl:sameAs` attributes are resolved to absolute URIs and normalized
     * according to $normalizations and then compared literally. The default
     * set of normalizations applied by
     * alcamo::uri::UriNormalizer::normalize() includes realpath(), coherently
     * with the fact that DOMDocument::baseURI may resolve symbolic links.
     */
    public function getFirstSameAs(
        $uri,
        ?int $normalizations = null
    ): ?self {
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        if (!Uri::isAbsolute($uri)) {
            /** @throw alcamo::exception::AbsoluteUriNeeded if $uri does not
             *  represent an absolut URI. */
            throw (new AbsoluteUriNeeded())
                ->setMessageContext([ 'uri' => $uri ]);
        }

        $uri = (string)UriNormalizer::normalize($uri, $normalizations);

        foreach ($this->query('descendant-or-self::*[@owl:sameAs]') as $element) {
            if (
                UriNormalizer::normalize(
                    $element->resolveUri(
                        $element->getAttributeNS(Document::OWL_NS, 'sameAs')
                    ),
                    $normalizations
                )
                == $uri
            ) {
                return $element;
            }
        }

        return null;
    }
}
