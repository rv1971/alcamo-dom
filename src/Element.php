<?php

namespace alcamo\dom;

use alcamo\exception\AbsoluteUriNeeded;
use alcamo\rdf_literal\{HavingLangInterface, Lang};
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
    HavingLangInterface,
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

    /**
     * @brief Return language of element or closest ancestor
     *
     * Look for `xml:lang` attributes in all elements. In xhtml elements, also
     * look for `lang` attributes. The relevant attribute is that of the
     * closest ancestor. If the closest ancestor is an xhtml element and has
     * both attributes and they have different values (which is certainly bad
     * document design), `xml:lang` takes precedence.
     */
    public function getLang(): ?Lang
    {
        /* For efficiency, first check if the element itself has an xml:lang
         * (or lang) attribute since this is a frequent case in practice. */
        if ($this->hasAttributeNS(self::XML_NS, 'lang')) {
            return Lang::newFromString(
                $this->getAttributeNS(self::XML_NS, 'lang')
            );
        }

        if (
            $this->namespaceURI == self::XH_NS && $this->hasAttribute('lang')
        ) {
            return Lang::newFromString($this->getAttribute('lang'));
        }

        /* If it does not, look for the first ancestor having such an
         * attribute. */
        $ancestor = $this->query(
            'ancestor::*[@xml:lang or (self::xh:* and @lang)][1]'
        )[0];

        if (isset($ancestor)) {
            if ($ancestor->hasAttributeNS(self::XML_NS, 'lang')) {
                return Lang::newFromString(
                    $ancestor->getAttributeNS(self::XML_NS, 'lang')
                );
            } else {
                return Lang::newFromString($ancestor->getAttribute('lang'));
            }
        }

        return null;
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

        foreach (
            $this->query('descendant-or-self::*[@owl:sameAs]') as $element
        ) {
            if (
                UriNormalizer::normalize(
                    $element->resolveUri(
                        $element->getAttributeNS(self::OWL_NS, 'sameAs')
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

    /**
     * @brief Get the first element along the `descendant-or-self` axis which
     * is semantically the same as a URI having the given suffix.
     *
     * I.e. return the first element that declares itself to be the same as a
     * URI ending in $suffix. The result may be the context element
     * itself. Return `null` if no such element is found.
     *
     * This is useful when the task is to pick up one out of a set of known
     * `owl:sameAs` targets.
     *
     * @param $suffix Suffix to look for.
     *
     * @param $normalizations to apply to each `owl:sameAs` attribute before
     * comparison. If `null`, no normalizations take place, thus accelerating
     * the process. In particular, unlike getFirstSameAsSuffix(), no
     * realpath() is needed for `file://` URIs, thus saving the effort to
     * access the file system.
     */
    public function getFirstSameAsSuffix(
        string $suffix,
        ?int $normalizations = null
    ): ?self {
        foreach (
            $this->query('descendant-or-self::*[@owl:sameAs]') as $element
        ) {
            $sameAs = $element->resolveUri(
                $element->getAttributeNS(self::OWL_NS, 'sameAs')
            );

            if (isset($normalizations)) {
                $sameAs = UriNormalizer::normalize($sameAs, $normalizations);
            }

            if (substr($sameAs, -strlen($suffix)) == $suffix) {
                return $element;
            }
        }

        return null;
    }
}
