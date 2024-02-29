<?php

namespace Walnut\Lang\Implementation\Type;

use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\AnyType;
use Walnut\Lang\Blueprint\Type\ArrayType as ArrayTypeInterface;
use Walnut\Lang\Blueprint\Type\NothingType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\TupleType as TupleTypeInterface;
use Walnut\Lang\Blueprint\Type\UnknownProperty;

final readonly class TupleType implements TupleTypeInterface {
	/** @param list<Type> $types */
	public function __construct(
		private TypeRegistry $typeRegistry,
		private array $types,
		private Type $restType
	) {}

    /**
     * @return list<Type>
     */
    public function types(): array {
        return $this->types;
    }

	public function restType(): Type {
		return $this->restType;
	}

	public function asArrayType(): ArrayType {
		$l = count($this->types());
		return $this->typeRegistry->array(
			$this->typeRegistry->union($this->types()),
			$l,
			$l,
		);
	}

	/** @throws UnknownProperty */
	public function typeOf(int $index): Type {
		return $this->types()[$index] ??
			throw new UnknownProperty(new PropertyNameIdentifier($index), (string)$this);
	}

    public function isSubtypeOf(Type $ofType): bool {
		return match(true) {
			$ofType instanceof TupleTypeInterface => $this->isSubtypeOfTuple($ofType),
			$ofType instanceof ArrayTypeInterface => $this->isSubtypeOfArray($ofType),
			$ofType instanceof SupertypeChecker => $ofType->isSupertypeOf($this),
			default => false
		};
	}

	private function isSubtypeOfTuple(TupleTypeInterface $ofType): bool {
		if (!$this->restType()->isSubtypeOf($ofType->restType())) {
			return false;
		}
		$ofTypes = $ofType->types();
		$usedIndices = [];
		foreach($this->types() as $index => $type) {
			if (!$type->isSubtypeOf($ofTypes[$index] ?? $ofType->restType())) {
				return false;
			}
			$usedIndices[$index] = true;
		}
		foreach($ofTypes as $index => $type) {
			if (!isset($usedIndices[$index]) && (!isset($this->types()[$index]) || $this->types()[$index]->isSubtypeOf($type))) {
				return false;
			}
		}
		return true;
	}

	private function isSubtypeOfArray(ArrayType $ofType): bool {
		$itemType = $ofType->itemType();
		if (!$this->restType()->isSubtypeOf($itemType)) {
			return false;
		}
		foreach($this->types() as $type) {
			if (!$type->isSubtypeOf($itemType)) {
				return false;
			}
		}
		$cnt = count($this->types());
		if ($cnt < $ofType->range()->minLength()) {
			return false;
		}
		$max = $ofType->range()->maxLength();
		return $max === PlusInfinity::value || ($this->restType() instanceof NothingType && $cnt <= $max);
	}

	public function __toString(): string {
		$types = $this->types();
		if ($this->restType() instanceof AnyType) {
			$types[] = "...";
		} elseif (!$this->restType() instanceof NothingType) {
			$types[] = "... " . $this->restType();
		}
		return "[" . implode(', ', $types) . "]";
	}
}