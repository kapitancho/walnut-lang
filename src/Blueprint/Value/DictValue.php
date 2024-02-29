<?php

namespace Walnut\Lang\Blueprint\Value;

use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\UnknownProperty;

interface DictValue extends Value {
    /** @return list<Value> */
    public function values(): array;
    public function type(): RecordType;
	/** @throws UnknownProperty */
	public function valueOf(PropertyNameIdentifier $propertyName): Value;
}