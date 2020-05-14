<?php declare(strict_types=1);

namespace Burst\BurstPayment\Resources\snippet\de_DE;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_de_DE implements SnippetFileInterface
{

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'burst-payment.de-DE';
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return __DIR__ . '/burst-payment.de-DE.json';
    }

    /**
     * @inheritDoc
     */
    public function getIso(): string
    {
        return 'de-DE';
    }

    /**
     * @inheritDoc
     */
    public function getAuthor(): string
    {
        return 'Felix Brucker';
    }

    /**
     * @inheritDoc
     */
    public function isBase(): bool
    {
        return false;
    }
}
