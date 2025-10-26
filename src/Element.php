<?php

namespace alcamo\dom;

use alcamo\exception\AbsoluteUriNeeded;
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
    \IteratorAggregate,
    HavingBaseUriInterface,
    HavingXNameInterface,
    NamespaceConstantsInterface,
    Rfc5147Interface,
    XPathQueryableInterface
{
    use HavingBaseUriTrait;
    use HavingXNameTrait;
    use Rfc5147Trait;

    /// Return [textContent](https://www.php.net/manual/en/class.domnode#domnode.props.textcontent)
    public function __toString(): string
    {
        return $this->textContent;
    }

    /// Return a ChildElementsIterator on this element
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

    /**
     * @brief Get the first element which is semantically the same as a given
     * URI
     *
     * I.e. return the first element that declares itself to be the same as
     * $uri. The result may be the context element itself. Return `null` if no
     * such element is found.
     *
     * @param $uri Asbolute URI which is compared to `owl:sameAs` attributes,
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
