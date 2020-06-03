<?php declare(strict_types=1);

namespace Burst\BurstPayment\Resources\snippet\de_DE;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFileDeDe implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'burst-payment.de-DE';
    }

    public function getPath(): string
    {
        return __DIR__ . '/burst-payment.de-DE.json';
    }

    public function getIso(): string
    {
        return 'de-DE';
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
