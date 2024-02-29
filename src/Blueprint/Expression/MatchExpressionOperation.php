<?php

namespace Walnut\Lang\Blueprint\Expression;

use Stringable;
use Walnut\Lang\Blueprint\Value\Value;

interface MatchExpressionOperation extends Stringable {
	public function match(Value $matchValue, Value $matchAgainst): bool;
}