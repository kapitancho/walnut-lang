<?php

namespace Walnut\Lang\Implementation\Type;

use JsonSerializable;
use Walnut\Lang\Blueprint\Type\MetaType as MetaTypeInterface;
use Walnut\Lang\Blueprint\Type\MetaTypeValue;
use Walnut\Lang\Blueprint\Type\AtomType;
use Walnut\Lang\Blueprint\Type\EnumerationType;
use Walnut\Lang\Blueprint\Type\EnumerationSubsetType;
use Walnut\Lang\Blueprint\Type\FunctionType;
use Walnut\Lang\Blueprint\Type\IntersectionType;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\NamedType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\RealSubsetType;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\UnionType;
use Walnut\Lang\Blueprint\Type\Type;

final readonly class MetaType implements MetaTypeInterface, SupertypeChecker, JsonSerializable {
	public function __construct(
		public MetaTypeValue $value
	) {}

	public function __toString(): string {
		return $this->value->value;
	}

	public function value(): MetaTypeValue {
		return $this->value;
	}

	public function isSupertypeOf(Type $ofType): bool {
		if ($ofType instanceof self && $this->value === $ofType->value) {
			return true;
		}
		return match($this->value) {
			MetaTypeValue::Function => $ofType instanceof FunctionType,
			MetaTypeValue::Tuple => $ofType instanceof TupleType,
			MetaTypeValue::Record => $ofType instanceof RecordType,
			MetaTypeValue::Union => $ofType instanceof UnionType,
			MetaTypeValue::Intersection => $ofType instanceof IntersectionType,
			MetaTypeValue::Alias => $ofType instanceof AliasType,
			MetaTypeValue::Subtype => $ofType instanceof SubtypeType,
			MetaTypeValue::State => $ofType instanceof StateType,
			MetaTypeValue::Atom => $ofType instanceof AtomType,
			MetaTypeValue::Enumeration => $ofType instanceof EnumerationType,
			MetaTypeValue::EnumerationSubset => $ofType instanceof EnumerationSubsetType,
			MetaTypeValue::IntegerSubset => $ofType instanceof IntegerSubsetType,
			MetaTypeValue::RealSubset => $ofType instanceof RealSubsetType,
			MetaTypeValue::StringSubset => $ofType instanceof StringSubsetType,
			MetaTypeValue::Named => $ofType instanceof NamedType,
		};
    }

	public function isSubtypeOf(Type $ofType): bool {
		return $ofType instanceof SupertypeChecker && $ofType->isSupertypeOf($this);
	}

	public function jsonSerialize(): mixed {
		return [
			'type' => $this->value->value
		];
	}
}