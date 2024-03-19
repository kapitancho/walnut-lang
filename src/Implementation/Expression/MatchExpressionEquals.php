<?php

namespace Walnut\Lang\Implementation\Expression;

use JsonSerializable;
use Walnut\Lang\Blueprint\Expression\MatchExpressionOperation;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class MatchExpressionEquals implements MatchExpressionOperation, JsonSerializable {

	public function match(Value $matchValue, Value $matchAgainst): bool {
		return $matchValue->equals($matchAgainst);
	}

	public function __toString(): string {
		return "==";
	}

	public function jsonSerialize(): string {
		return 'equals';
	}
}