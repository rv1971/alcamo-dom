<?php

namespace alcamo\dom;

use alcamo\exception\AbsoluteUriNeeded;
use alcamo\xml\HasXNameInterface;
use alcamo\uri\{Uri, UriNormalizer};
use Psr\Http\Message\UriInterface;

/**
 * @brief Element class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2021-06-30
 */
class Element extends \DOMElement implements
    \IteratorAggregate,
    HasXNameInterface,
    BaseUriInterface
{
    use HasXNameTrait;
    use Rfc5147Trait;
    use BaseUriTrait;

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
     * @brief Return the first node having an equivalent `owl:sameAs`
     * attribute
     *
     * This may be the context node itself. Return `null` if no such node is
     * found.
     *
     * For comparison, the values of `owl:sameAs` attributes are resolved to
     * absolute URIs and compared literally. The default set of normalizations
     * applied by alcamo::uri::UriNormalizer::normalize() includes realpath(),
     * coherently with the fact that DOMDocument::baseURI may resolve symbolic
     * links.
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

        foreach ($this->query('descendant-or-self::*[@owl:sameAs]') as $node) {
            if (
                UriNormalizer::normalize(
                    $node->resolveUri(
                        $node->getAttributeNS(Document::OWL_NS, 'sameAs')
                    ),
                    $normalizations
                )
                == $uri
            ) {
                return $node;
            }
        }

        return null;
    }
}
