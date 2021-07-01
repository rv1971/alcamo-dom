<?php

namespace alcamo\dom;

use alcamo\exception\SyntaxError;

/**
 * @brief DOM Document consisting in the document element without any content
 *
 * This is useful to inspect a document without parsing it completeley. For
 * instance, the tag name or some attribute of the document element can be
 * used to choose an appropriate document class.
 *
 * @date Last reviewed 2021-07-01
 */
class ShallowDocument extends Document
{
    /// Maximum number of bytes to read from the document
    public const MAX_LENGH = 4096;

    /**
     * @copydoc Document::loadUrl()
     *
     * @warning The first tag must end within the first @ref MAX_LENGH of the
     * data.
     */
    public function loadUrl(string $url, ?int $libXmlOptions = null)
    {
        return $this->loadXmlText(
            file_get_contents($url, false, null, 0, static::MAX_LENGH)
        );
    }

    /// @copydoc Document::loadXmlText()
    public function loadXmlText(string $xml, ?int $libXmlOptions = null)
    {
        /** Use a regular expression to find the first string in angular
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
            /** @throw alcamo::exception::SyntaxError if no complete opening
             *  tag is found. */
            throw new
                SyntaxError($xml, null, '; no complete opening tag found');
        }

        $bracketPos = $matches[0][1] + strlen($matches[0][0]) - 1;

        $firstTagText = substr($xml, 0, $bracketPos)
            . (($xml[$bracketPos - 1] == '/') ? '>' : '/>');

        return parent::loadXmlText($firstTagText, $libXmlOptions);
    }
}
