<?php declare(strict_types=1);

namespace Burst\BurstPayment\Payment;

use Shopware\Core\Framework\Struct\Struct;

class BurstPageExtension extends Struct
{
    public const PAGE_EXTENSION_NAME = 'burstPaymentData';

    public $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
