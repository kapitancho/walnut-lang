<?php

namespace Walnut\Lang\Implementation\Expression;

use Walnut\Lang\Blueprint\Expression\MatchExpressionOperation;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class MatchExpressionIsSubtypeOf implements MatchExpressionOperation {

	public function match(Value $matchValue, Value $matchAgainst): bool {
		return
			($matchAgainst instanceof TypeValue) &&
			$matchValue->type()->isSubtypeOf($matchAgainst->typeValue());
	}

	public function __toString(): string {
		return "<:";
	}
}