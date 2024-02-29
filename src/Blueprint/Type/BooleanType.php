<?php

namespace Walnut\Lang\Blueprint\Type;

use InvalidArgumentException;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Value\BooleanValue;

interface BooleanType extends EnumerationType {
    /** @return array<string, BooleanValue> */
    public function values(): array;

	/**
	* @param list<EnumValueIdentifier> $values
	* @throws InvalidArgumentException
	**/
	public function subsetType(array $values): TrueType|FalseType|BooleanType;

	/** @throws InvalidArgumentException **/
	public function value(EnumValueIdentifier $valueIdentifier): BooleanValue;
}