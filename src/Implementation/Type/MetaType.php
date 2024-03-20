<?php

namespace Walnut\Lang\Implementation\Type;

use JsonSerializable;
use Walnut\Lang\Blueprint\Type\MetaType as MetaTypeInterface;
use Walnut\Lang\Blueprint\Type\MetaTypeValue;
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