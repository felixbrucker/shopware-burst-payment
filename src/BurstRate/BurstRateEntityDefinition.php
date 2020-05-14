<?php declare(strict_types=1);

namespace Burst\BurstPayment\BurstRate;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class BurstRateEntityDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'burst_rate';
    }

    public function getCollectionClass(): string
    {
        return BurstRateEntityCollection::class;
    }

    public function getEntityClass(): string
    {
        return BurstRateEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new StringField('currency', 'currency'),
            new FloatField('rate', 'rate'),
        ]);
    }
}
