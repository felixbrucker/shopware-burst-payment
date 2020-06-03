<?php declare(strict_types=1);

namespace Burst\BurstPayment\Resources\snippet\en_GB;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFileEnGb implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'burst-payment.en-GB';
    }

    public function getPath(): string
    {
        return __DIR__ . '/burst-payment.en-GB.json';
    }

    public function getIso(): string
    {
        return 'en-GB';
    }

    public function getAuthor(): string
    {
        return 'Felix Brucker';
    }

    public function isBase(): bool
    {
        return false;
    }
}
