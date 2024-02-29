<?php

namespace Walnut\Lang\Implementation\Type;

use Walnut\Lang\Blueprint\Type\MutableType as MutableTypeInterface;
use Walnut\Lang\Blueprint\Type\TypeType as TypeTypeInterface;
use Walnut\Lang\Blueprint\Type\Type;

final readonly class TypeType implements TypeTypeInterface {

    public function __construct(
        private Type $refType
    ) {}

    public function refType(): Type {
        return $this->refType;
    }

    public function isSubtypeOf(Type $ofType): bool {
		if ($ofType instanceof TypeTypeInterface &&
			$this->refType instanceof MutableTypeInterface &&
			$ofType->refType() instanceof MutableTypeInterface
		) {
			return $this->refType->valueType()->isSubtypeOf($ofType->refType()->valueType());
		}
        return (
            $ofType instanceof TypeTypeInterface &&
            $this->refType->isSubtypeOf($ofType->refType())
        ) || (
            $ofType instanceof SupertypeChecker &&
            $ofType->isSupertypeOf($this)
        );
    }

	public function __toString(): string {
		return sprintf(
			"Type<%s>",
			$this->refType
		);
	}
}