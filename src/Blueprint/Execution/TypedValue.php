<?php

namespace Walnut\Lang\Blueprint\Execution;

use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class TypedValue {
	public function __construct(
		public Type $type,
		public Value $value,
	) {}

	public static function forValue(Value $value): self {
		return new self($value->type(), $value);
	}
}