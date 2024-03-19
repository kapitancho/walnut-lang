<?php

namespace Walnut\Lang\Implementation\Value;

use JsonSerializable;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\StateType;
use Walnut\Lang\Blueprint\Value\StateValue as StateValueInterface;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class StateValue implements StateValueInterface, JsonSerializable {

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
		$sv = (string)$this->stateValue;
		return sprintf(
			str_starts_with($sv, '[') ? "%s%s" : "%s{%s}",
			$this->typeName,
			$sv
		);
	}

	public function jsonSerialize(): array {
		return [
			'valueType' => 'State',
			'typeName' => $this->typeName,
			'stateValue' => $this->stateValue
		];
	}
}