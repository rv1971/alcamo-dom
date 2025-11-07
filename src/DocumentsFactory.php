<?php

namespace alcamo\dom;

use alcamo\uri\FileUriFactory;

class DocumentsFactory implements HavingDocumentFactoryInterface
{
    /// Default class for new documents objects
    public const DEFAULT_DOCUMENTS_CLASS = Documents::class;

    /// Default glob() flags for createFromGlob()
    public const DEFAULT_GLOB_FLAGS = GLOB_NOSORT | GLOB_BRACE;

    protected $documentFactory_; ///< DocumentFactoryInterface

    public function __construct(
        ?DocumentFactoryInterface $documentFactory = null
    ) {
        if (isset($documentFactory)) {
            $this->documentFactory_ = $documentFactory;
        } else {
            $class = static::DEFAULT_DOCUMENT_FACTORY_CLASS;

            $this->documentFactory_ = new $class();
        }
    }

    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return $this->documentFactory_;
    }

    /**
     * @brief Create documents from a collection of URLs
     *
     * @param $urls Collection of URLs.
     *
     * If the keys of $urls are strings, they are used as key in the resulting
     * Documents object. Otherwise the keys are created as explained in
     * Documents::__construct().
     */
    public function createFromUrls(iterable $urls): Documents
    {
        $docs = [];

        foreach ($urls as $key => $url) {
            $docs[$key] = $this->documentFactory_->createFromUrl($url);
        }

        $class = static::DEFAULT_DOCUMENTS_CLASS;

        return new $class($docs);
    }

    /**
     * @brief Create documents from a glob() pattern
     *
     * @param $pattern Pattern for
     * [glob()](https://www.php.net/manual/en/function.glob)
     *
     * @param $globFlags Flags for glob().
     */
    public function createFromGlob(
        string $pattern,
        ?int $globFlags = null
    ): Documents {
        $docs = [];

        $fileUriFactory = new FileUriFactory();

        foreach (
            glob($pattern, $globFlags ?? static::DEFAULT_GLOB_FLAGS) as $path
        ) {
            $docs[] = $this->documentFactory_->createFromUrl(
                $fileUriFactory->create($path)
            );
        }

        $class = static::DEFAULT_DOCUMENTS_CLASS;

        return new $class($docs);
    }
}
