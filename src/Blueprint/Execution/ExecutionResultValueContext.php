<?php

namespace Walnut\Lang\Blueprint\Execution;

use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class ExecutionResultValueContext {
	public function __construct(
		public TypedValue $typedValue,
		public VariableValueScope $variableValueScope
	) {}

	public function typedValue(): TypedValue {
		return $this->typedValue;
	}

	public function value(): Value {
		return $this->typedValue->value;
	}

	public function valueType(): Type {
		return $this->typedValue->type;
	}

	public function withTypedValue(TypedValue $typedValue): self {
		return new self($typedValue, $this->variableValueScope);
	}

	public function withVariableValueScope(VariableValueScope $variableValueScope): self {
		return new self($this->typedValue, $variableValueScope);
	}
}