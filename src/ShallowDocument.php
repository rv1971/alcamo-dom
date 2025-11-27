<?php

namespace alcamo\dom;

use alcamo\exception\{FileLoadFailed, SyntaxError};

/**
 * @brief DOM Document consisting in the document element without any content
 *
 * This is useful to rapidly inspect a document without parsing it
 * completely. For instance, the tag name or some attribute of the document
 * element can be used to choose an appropriate document class in which to
 * load the document.
 *
 * @date Last reviewed 2025-10-25
 */
class ShallowDocument extends Document
{
    /// Maximum number of bytes to read from the document
    public const MAX_LENGH = 4096;

    /**
     * @copydoc alcamo::dom::Document::loadUri()
     *
     * See loadXmlText() for details.
     */
    public function loadUri(string $uri): void
    {
        $errorLevel = error_reporting(E_ERROR);

        $xmlText = file_get_contents($uri, false, null, 0, static::MAX_LENGH);

        error_reporting($errorLevel);

        if ($xmlText === false) {
            /** @throw alcamo::exception::FileLoadFailed if
             *  file_get_contents() fails. */
            throw (new FileLoadFailed())
                ->setMessageContext([ 'filename' => $uri ]);
        }

        $this->loadXmlText($xmlText);
    }

    /**
     * @copydoc alcamo::dom::Document::loadXmlText()
     *
     * @warning The first tag must end within the first @ref MAX_LENGH bytes
     * of the data.
     */
    public function loadXmlText(string $xmlText, ?string $uri = null): void
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
                $xmlText,
                $matches,
                PREG_OFFSET_CAPTURE
            )
        ) {
            /** @throw alcamo::exception::SyntaxError if no complete opening
             *  tag is found. */
            throw (new SyntaxError())->setMessageContext(
                [
                    'inData' => $xmlText,
                    'extraMessage' => 'no complete opening tag found'
                ]
            );
        }

        $bracketPos = $matches[0][1] + strlen($matches[0][0]) - 1;

        $firstTagText = substr($xmlText, 0, $bracketPos)
            . (($xmlText[$bracketPos - 1] == '/') ? '>' : '/>');

        parent::loadXmlText($firstTagText, $uri);
    }

    /// Do nothing after load
    protected function afterLoad(): void
    {
    }
}
