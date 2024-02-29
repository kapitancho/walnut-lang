<?php

namespace Walnut\Lang\Implementation\Value;

use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\StateType;
use Walnut\Lang\Blueprint\Value\StateValue as StateValueInterface;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class StateValue implements StateValueInterface {

    public function __construct(
		private TypeRegistry $typeRegistry,
		private TypeNameIdentifier $typeName,
	    private Value $stateValue
    ) {}

    public function type(): StateType {
        return $this->typeRegistry->state($this->typeName);
    }

    public function stateValue(): Value {
		return $this->stateValue;
    }

	public function equals(Value $other): bool {
		return $other instanceof StateValueInterface &&
			$this->typeName->equals($other->type()->name()) &&
			$this->stateValue->equals($other->stateValue());
	}

	public function __toString(): string {
		return sprintf(
			"%s%s",
			$this->typeName,
			$this->stateValue
		);
	}
}