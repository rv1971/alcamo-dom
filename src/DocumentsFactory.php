<?php

namespace alcamo\dom;

use alcamo\uri\FileUriFactory;

/**
 * @brief Factory for collections of DOM documents
 */
class DocumentsFactory implements HavingDocumentFactoryInterface
{
    /// Default class for new documents objects
    public const DEFAULT_DOCUMENTS_CLASS = Documents::class;

    /// Default glob() flags for createFromGlob()
    public const DEFAULT_GLOB_FLAGS = GLOB_NOSORT | GLOB_BRACE;

    protected $documentFactory_; ///< DocumentFactoryInterface

    /**
     * @param $documentFactory Factory used to create documents.
     */
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

    /// Get the factory used to create documents
    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return $this->documentFactory_;
    }

    /**
     * @brief Create documents from a collection of URIs
     *
     * @param $uris Collection of URIs.
     *
     * If the keys of $uris are strings, they are used as key in the resulting
     * Documents object. Otherwise the keys are created as explained in
     * Documents::__construct().
     */
    public function createFromUris(iterable $uris): Documents
    {
        $docs = [];

        foreach ($uris as $key => $uri) {
            $docs[$key] = $this->documentFactory_->createFromUri($uri);
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
            $docs[] = $this->documentFactory_->createFromUri(
                $fileUriFactory->create($path)
            );
        }

        $class = static::DEFAULT_DOCUMENTS_CLASS;

        return new $class($docs);
    }
}
