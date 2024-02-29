<?php

namespace Walnut\Lang\Implementation\Value;

use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\ResultType;
use Walnut\Lang\Blueprint\Value\ErrorValue as ErrorValueInterface;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class ErrorValue implements ErrorValueInterface {
    public function __construct(
        private TypeRegistry $typeRegistry,
        private Value $errorValue
    ) {}

    public function type(): ResultType {
        return $this->typeRegistry->result(
            $this->typeRegistry->nothing(),
            $this->errorValue->type()
        );
    }

    public function errorValue(): Value {
        return $this->errorValue;
    }

    public function equals(Value $other): bool {
        return $other instanceof self && $this->errorValue->equals($other->errorValue());
    }

    public function __toString(): string {
        return sprintf("@%s", $this->errorValue);
    }
}