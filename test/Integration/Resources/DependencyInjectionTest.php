<?php declare(strict_types=1);

namespace Burst\BurstPayment\Test\Integration\Resources;

use Burst\BurstPayment\Resources\snippet\de_DE\SnippetFileDeDe;
use Burst\BurstPayment\Resources\snippet\en_GB\SnippetFileEnGb;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @testdox ResourcesDependencyInjection
 */
class DependencyInjectionTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @testdox can load all services
     */
    public function test_canLoadServices(): void
    {
        $this->addToAssertionCount(1);

        $this->getContainer()->get(SnippetFileDeDe::class);
        $this->getContainer()->get(SnippetFileEnGb::class);
    }
}
