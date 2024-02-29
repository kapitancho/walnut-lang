<?php

namespace Walnut\Lang\Blueprint\Type;

use InvalidArgumentException;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Value\EnumerationValue;

interface EnumerationType extends EnumerationSubsetType, NamedType {
    /** @return array<string, EnumerationValue> */
    public function values(): array;

    /**
     * @param list<EnumValueIdentifier> $values
     * @throws InvalidArgumentException
     **/
    public function subsetType(array $values): EnumerationSubsetType;

	/** @throws InvalidArgumentException **/
	public function value(EnumValueIdentifier $valueIdentifier): EnumerationValue;
}