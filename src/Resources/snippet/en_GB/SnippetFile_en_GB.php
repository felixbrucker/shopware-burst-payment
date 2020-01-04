<?php

namespace Burst\BurstPayment\Resources\snippet\en_GB;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_en_GB implements SnippetFileInterface
{

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'burst-payment.en-GB';
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return __DIR__ . '/burst-payment.en-GB.json';
    }

    /**
     * @inheritDoc
     */
    public function getIso(): string
    {
        return 'en-GB';
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
