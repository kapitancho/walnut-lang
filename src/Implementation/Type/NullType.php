<?php

namespace Walnut\Lang\Implementation\Type;

use JsonSerializable;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Type\NullType as NullTypeInterface;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\NullValue;

final readonly class NullType implements NullTypeInterface, JsonSerializable {

    public function __construct(
        private TypeNameIdentifier $typeName,
        private NullValue $atomValue
    ) {}

    public function value(): NullValue {
        return $this->atomValue;
    }

    public function name(): TypeNameIdentifier {
        return $this->typeName;
    }

    public function isSubtypeOf(Type $ofType): bool {
        return $ofType instanceof NullTypeInterface || (
            $ofType instanceof SupertypeChecker &&
            $ofType->isSupertypeOf($this)
        );
    }

	public function __toString(): string {
		return 'Null';
	}

	public function jsonSerialize(): array {
		return ['type' => 'Null'];
	}
}