<?php

namespace Walnut\Lang\Implementation\Value;

use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\AtomType;
use Walnut\Lang\Blueprint\Value\NullValue as NullValueInterface;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class NullValue implements NullValueInterface {

    public function __construct(
        private TypeRegistry $typeRegistry
    ) {}

    public function type(): AtomType {
        return $this->typeRegistry->null();
    }

	public function literalValue(): null {
		return null;
	}

	public function equals(Value $other): bool {
		return $other instanceof NullValueInterface;
	}

	public function __toString(): string {
		return 'null';
	}
}