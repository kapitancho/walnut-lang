<?php

namespace Walnut\Lang\Implementation\Type;

use JsonSerializable;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\AnyType as AnyTypeInterface;
use Walnut\Lang\Blueprint\Type\NothingType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\MapType;
use Walnut\Lang\Blueprint\Type\OptionalKeyType;
use Walnut\Lang\Blueprint\Type\RecordType as RecordTypeInterface;
use Walnut\Lang\Blueprint\Type\UnknownProperty;

final readonly class RecordType implements RecordTypeInterface, JsonSerializable {
	/** @param array<string, Type> $types */
	public function __construct(
		private TypeRegistry $typeRegistry,
		private array $types,
		private Type $restType
	) {}

    /**
     * @return array<string, Type>
     */
    public function types(): array {
        return $this->types;
    }

	public function restType(): Type {
		return $this->restType;
	}

	public function asMapType(): MapType {
		$l = count($this->types());
		$min = count(array_filter($this->types(), fn($type) => !($type instanceof OptionalKeyType)));
		$types = array_map(
			fn(Type $type): Type => $type instanceof OptionalKeyType ? $type->valueType() : $type,
			$this->types()
		);
		return $this->typeRegistry->map(
			$this->typeRegistry->union(array_values([... $types, $this->restType])),
			$min,
			$this->restType instanceof NothingType ? $l : PlusInfinity::value,
		);
	}

	/** @throws UnknownProperty */
	public function typeOf(PropertyNameIdentifier $propertyName): Type {
		return $this->types()[$propertyName->identifier] ??
			throw new UnknownProperty($propertyName, (string)$this);
	}

    public function isSubtypeOf(Type $ofType): bool {
		return match(true) {
			$ofType instanceof RecordTypeInterface => $this->isSubtypeOfRecord($ofType),
			$ofType instanceof MapType => $this->isSubtypeOfMap($ofType),
			$ofType instanceof SupertypeChecker => $ofType->isSupertypeOf($this),
			default => false
		};
	}

	private function isSubtypeOfRecord(RecordTypeInterface $ofType): bool {
		if (!$this->restType()->isSubtypeOf($ofType->restType())) {
			return false;
		}
		$ofTypes = $ofType->types();
		$usedKeys = [];
		foreach($this->types() as $key => $type) {
			if (!$type->isSubtypeOf($ofTypes[$key] ?? $ofType->restType())) {
				return false;
			}
			$usedKeys[$key] = true;
		}
		foreach($ofTypes as $key => $type) {
			if (!($type instanceof OptionalKeyType) && !isset($usedKeys[$key]) &&
				(!isset($this->types()[$key]) || $this->types()[$key]->isSubtypeOf($type))) {
				return false;
			}
		}
		return true;
	}

	private function isSubtypeOfMap(MapType $ofType): bool {
		$itemType = $ofType->itemType();
		if (!$this->restType()->isSubtypeOf($itemType)) {
			return false;
		}
		foreach($this->types() as $type) {
			$t = $type instanceof OptionalKeyType ? $type->valueType() : $type;
			if (!$t->isSubtypeOf($itemType)) {
				return false;
			}
		}
		$min = count(array_filter($this->types(), fn($type) => !($type instanceof OptionalKeyType)));
		$cnt = count($this->types());
		if ($min < $ofType->range()->minLength()) {
			return false;
		}
		$max = $ofType->range()->maxLength();
		return $max === PlusInfinity::value || ($this->restType() instanceof NothingType && $cnt <= $max);
	}

	public function __toString(): string {
		$types = [];
		$typeX = '';
		if (count($this->types())) {
			foreach($this->types() as $key => $type) {
				$typeStr = (string)$type;
				$types[] = lcfirst($typeStr) === $key ? "~$typeStr" : "$key: $typeStr";
			}
		} else {
			$typeX = ':';
		}
		if ($this->restType() instanceof AnyTypeInterface) {
			$types[] = "...";
			if ($typeX === ':') {
				$typeX = ': ';
			}
		} elseif (!$this->restType() instanceof NothingType) {
			$types[] = "... " . $this->restType;
			if ($typeX === ':') {
				$typeX = ': ';
			}
		}
		return sprintf("[%s%s]", $typeX, implode(', ', $types));
	}


	public function jsonSerialize(): array {
		return ['type' => 'Record', 'types' => $this->types, 'restType' => $this->restType];
	}
}