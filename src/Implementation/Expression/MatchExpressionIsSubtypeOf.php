<?php

namespace Walnut\Lang\Implementation\Expression;

use Walnut\Lang\Blueprint\Expression\MatchExpressionOperation;
use Walnut\Lang\Blueprint\Value\SubtypeValue;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class MatchExpressionIsSubtypeOf implements MatchExpressionOperation {

	public function match(Value $matchValue, Value $matchAgainst): bool {
		return
			($matchAgainst instanceof TypeValue) && (
				$matchValue->type()->isSubtypeOf($matchAgainst->typeValue()) ||
				($matchValue instanceof SubtypeValue && $this->match($matchValue->baseValue(), $matchAgainst))
			);
	}

	public function __toString(): string {
		return "<:";
	}
}