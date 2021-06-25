<?php

namespace alcamo\dom\extended;

use alcamo\ietf\Lang;

trait HasLangTrait
{
    use RegisteredNodeTrait;

    private $lang_ = false; ///< ?Lang

    /// Return xml:lang of element or closest ancestor, or false.
    public function getLang(): ?Lang
    {
        if ($this->lang_ === false) {
            // Ensure conservation of the derived object.
            $this->register();

            /* For efficiency, first check if the element itself has an
             * xml:lang attribute since this is a frequent case in
             * practice. */
            if ($this->hasAttributeNS(Document::NS['xml'], 'lang')) {
                $this->lang_ = Lang::newFromString(
                    $this->getAttributeNS(Document::NS['xml'], 'lang')
                );
            } else {
                $langAttr = $this->query('ancestor::*[@xml:lang]/@xml:lang')[0];

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
