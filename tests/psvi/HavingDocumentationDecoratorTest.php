<?php

namespace alcamo\dom\psvi;

use alcamo\dom\psvi\HavingDocumentationDecorator as HDD;
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class HavingDocumentationDecoratorTest extends TestCase
{
    public const BAR_PATH = __DIR__ . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR
        . 'schema' . DIRECTORY_SEPARATOR
        . 'bar.xml';

    /**
     * @dataProvider getLabelProvider
     */
    public function testGetLabel(
        $xPath,
        $expectedLocalName,
        $lang,
        $fallbackFlags,
        $expectedLabel
    ): void {
        $doc = (new DocumentFactory())->createFromUri(
            (new FileUriFactory())->create(self::BAR_PATH)
        );

        $element = $doc->query($xPath)[0];

        $this->assertSame($expectedLocalName, $element->localName);

        $this->assertSame(
            $expectedLabel,
            $element->getLabel($lang, $fallbackFlags)
        );
    }

    public function getLabelProvider(): array
    {
        return [
            [ '*/*[local-name() = "baz"]', 'baz', null, 0, null ],
            [
                '*/*[local-name() = "baz"]',
                'baz',
                null,
                HDD::FALLBACK_TO_NAME,
                'baz'
            ],
            [
                '*/*[local-name() = "baz"]',
                'baz',
                'pt',
                HDD::FALLBACK_TO_TYPE_NAME,
                'Baz'
            ],
            [
                '*/*[local-name() = "baz"]',
                'baz',
                null,
                HDD::FALLBACK_TO_NAME | HDD::FALLBACK_TO_TYPE_NAME,
                'Baz'
            ],
            [
                '*//*[local-name() = "quux"]',
                'quux',
                'fr',
                HDD::FALLBACK_TO_TYPE_NAME,
                'Quux'
            ],
            [
                '*//*[local-name() = "quux"]',
                'quux',
                null,
                HDD::FALLBACK_TO_TYPE_NAME,
                'Quux element'
            ],
            [
                '*//*[local-name() = "quux"]',
                'quux',
                'en',
                HDD::FALLBACK_TO_NAME | HDD::FALLBACK_TO_TYPE_NAME,
                'Quux element'
            ]
        ];
    }
}
