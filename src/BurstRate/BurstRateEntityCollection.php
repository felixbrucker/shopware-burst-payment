<?php

namespace Burst\BurstPayment\BurstRate;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                 add(BurstRateEntity $entity)
 * @method void                 set(string $key, BurstRateEntity $entity)
 * @method BurstRateEntity[]    getIterator()
 * @method BurstRateEntity[]    getElements()
 * @method BurstRateEntity|null get(string $key)
 * @method BurstRateEntity|null first()
 * @method BurstRateEntity|null last()
 */
class BurstRateEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return BurstRateEntity::class;
    }
}
