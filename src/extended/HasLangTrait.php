<?php

namespace alcamo\dom\extended;

use alcamo\ietf\Lang;

/**
 * @brief Provides language of a node
 *
 * When calling getLang() a second time, the result is taken from the cache.
 *
 * @warning The cached result is never updated, not even when the language of
 * the node or one of its ancestors is changed.
 *
 * @date Last reviewed 2021-07-01
 */
trait HasLangTrait
{
    use RegisteredNodeTrait;

    private $lang_ = false; ///< ?Lang

    /// Return xml:lang of element or closest ancestor
    public function getLang(): ?Lang
    {
        if ($this->lang_ === false) {
            // Ensure conservation of the derived object.
            $this->register();

            /* For efficiency, first check if the element itself has an
             * xml:lang attribute since this is a frequent case in
             * practice. */
            if ($this->hasAttributeNS(Document::XML_NS, 'lang')) {
                $this->lang_ = Lang::newFromString(
                    $this->getAttributeNS(Document::XML_NS, 'lang')
                );
            } else {
                /* Then look for the first ancestor having such an
                 * attribute. */
                $langAttr =
                    $this->query('ancestor::*[@xml:lang][1]/@xml:lang')[0];

                if (isset($langAttr)) {
                    $this->lang_ = Lang::newFromString($langAttr->value);
                } else {
                    $this->lang_ = null;
                }
            }
        }

        return $this->lang_;
    }
}
