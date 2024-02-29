<?php

namespace Walnut\Lang\Implementation\Type;

use Walnut\Lang\Blueprint\Type\AnyType as AnyTypeInterface;
use Walnut\Lang\Blueprint\Type\Type;

final readonly class AnyType implements AnyTypeInterface, SupertypeChecker {

    public function isSubtypeOf(Type $ofType): bool {
        return $ofType instanceof AnyTypeInterface || (
			$ofType instanceof SupertypeChecker && $ofType->isSupertypeOf($this)
        );
    }

    public function isSupertypeOf(Type $ofType): bool {
        return true;
    }

	public function __toString(): string {
		return 'Any';
	}
}