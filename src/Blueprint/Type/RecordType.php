<?php

namespace Walnut\Lang\Blueprint\Type;

use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;

interface RecordType extends Type {
    /**
     * @return non-empty-list<Type>
     */
    public function types(): array;

	public function restType(): Type;

	public function asMapType(): MapType;

	/** @throws UnknownProperty */
	public function typeOf(PropertyNameIdentifier $propertyName): Type;
}