<?php

namespace alcamo\dom;

use alcamo\exception\SyntaxError;

/**
 * @brief DOM Document consisting in the document element without content
 *
 * This is useful to inspect a document without parsing it completeley. For
 * instance, the name of the document element tag can be used to choose an
 * appropriate document class.
 */
class ShallowDocument extends Document
{
    /** Never use the cache for shallow documents. */
    public static function newFromUrl(
        string $url,
        ?int $libXmlOptions = null
    ): Document {
        return parent::newFromUrl($url, $libXmlOptions);
    }

    /** @warning The first tag must end within the first 4kiB of the data. */
    public function loadUrl(string $url, ?int $libXmlOptions = null)
    {
        return $this->loadXmlText(
            file_get_contents($url, false, null, 0, 4096)
        );
    }

    public function loadXmlText(string $xml, ?int $libXmlOptions = null)
    {
        /** This uses a regular expression to find the first string in angular
         *  brackets which is neither an xml declaration or processing
         *  instruction nor a comment, and which contains quotes only in
         *  pairs.
         *
         *  @warning This fails if the document element is preceded by a
         *  comment containing such a string. */
        if (
            !preg_match(
                '/<[^!\?]([^"\'>]+=("[^"]*"|\'[^\']*\'))*[^"\'>]*>/',
                $xml,
                $matches,
                PREG_OFFSET_CAPTURE
            )
        ) {
            throw new SyntaxError($xml, null, '; no end of tag found');
        }

        $bracketPos = $matches[0][1] + strlen($matches[0][0]) - 1;

        $firstTagText = substr($xml, 0, $bracketPos)
            . (($xml[$bracketPos - 1] == '/') ? '>' : '/>');

        return parent::loadXmlText($firstTagText, $libXmlOptions);
    }
}
