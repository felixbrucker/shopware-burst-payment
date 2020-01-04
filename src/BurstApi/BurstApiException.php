<?php

namespace Burst\BurstPayment\BurstApi;

use Exception;
use Throwable;

class BurstApiException extends Exception
{
    /** @var array */
    private $result;

    public function __construct(array $result, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->result = $result;

        parent::__construct($message, $code, $previous);
    }

    public function getResult(): array
    {
        return $this->result;
    }
}