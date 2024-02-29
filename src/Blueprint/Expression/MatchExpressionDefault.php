<?php

namespace Walnut\Lang\Blueprint\Expression;

use Stringable;

final readonly class MatchExpressionDefault implements Stringable {
	public function __construct(
		public Expression $valueExpression,
	) {}

	public function __toString(): string {
		return sprintf("~: %s", $this->valueExpression);
	}
}