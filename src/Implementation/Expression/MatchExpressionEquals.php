<?php

namespace Walnut\Lang\Implementation\Expression;

use Walnut\Lang\Blueprint\Expression\MatchExpressionOperation;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class MatchExpressionEquals implements MatchExpressionOperation {

	public function match(Value $matchValue, Value $matchAgainst): bool {
		return $matchValue->equals($matchAgainst);
	}

	public function __toString(): string {
		return "==";
	}
}