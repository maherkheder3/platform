<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\Snippet\_fixtures;

use Shopware\Core\Framework\Snippet\Files\LanguageFileInterface;

class LaguageFileMock implements LanguageFileInterface
{
    public function getName(): string
    {
        return 'only for unit tests';
    }

    public function getPath(): string
    {
        return __DIR__ . '/test_Unit_TEST.json';
    }

    public function getIso(): string
    {
        return 'unit_TEST';
    }

    public function isBase(): bool
    {
        return $this::BASE_LANGUAGE_FILE;
    }
}