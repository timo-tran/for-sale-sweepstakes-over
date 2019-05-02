<?php

namespace Restomods\ListingBundle\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class SweepstakesProductEnumType extends Type
{
	const ENUM_PRODUCT_TYPE = 'enumproducttype';

	const PRODUCT_TYPE_SUBSCRIPTION = 'subscription';
	const PRODUCT_TYPE_BUMP_OFFER = 'bump_offer';
	const PRODUCT_TYPE_UPSELL = 'upsell';
	const PRODUCT_TYPE_UPSELL_PRODUCT = 'product';
	const PRODUCT_TYPE_DOWNSELL = 'downsell';
	public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return "ENUM('subscription', 'bump_offer', 'upsell', 'downsell', 'product')";
    }

	public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!in_array($value, array(self::PRODUCT_TYPE_SUBSCRIPTION, self::PRODUCT_TYPE_BUMP_OFFER, self::PRODUCT_TYPE_UPSELL, self::PRODUCT_TYPE_UPSELL, self::PRODUCT_TYPE_UPSELL_PRODUCT))) {
            throw new \InvalidArgumentException("Invalid Type");
        }
        return $value;
    }

	public function getName()
    {
        return self::ENUM_PRODUCT_TYPE;
    }

	public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
