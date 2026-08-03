<?php

declare(strict_types=1);

namespace Chansig\DirectoryIndex\Tests;

use Chansig\DirectoryIndex\Translator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    public function testKnownTranslationIsReturned(): void
    {
        self::assertSame('Name', (new Translator())->translate('en', 'name'));
        self::assertSame('Nom', (new Translator())->translate('fr', 'name'));
    }

    public function testUnknownTranslationIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown translation');

        (new Translator())->translate('de', 'name');
    }
}
