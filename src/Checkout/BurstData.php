<?php

namespace Burst\BurstPayment\Checkout;

use Shopware\Core\Framework\Struct\Struct;

class BurstData extends Struct
{
    public $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
