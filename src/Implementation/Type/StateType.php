<?php

namespace Walnut\Lang\Implementation\Type;

use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Type\StateType as StateTypeInterface;
use Walnut\Lang\Blueprint\Type\Type;

final readonly class StateType implements StateTypeInterface {

    public function __construct(
	    private TypeNameIdentifier $typeName,
        private Type $stateType
    ) {}

	public function name(): TypeNameIdentifier {
		return $this->typeName;
    }

	public function stateType(): Type {
        return $this->stateType;
    }

    public function isSubtypeOf(Type $ofType): bool {
        return (
            $ofType instanceof StateTypeInterface &&
            $this->name()->equals($ofType->name())
        ) || (
            $ofType instanceof SupertypeChecker &&
            $ofType->isSupertypeOf($this)
        );
    }

	public function __toString(): string {
		return (string)$this->typeName;
	}
}