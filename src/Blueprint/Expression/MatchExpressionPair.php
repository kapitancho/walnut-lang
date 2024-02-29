<?php

namespace Walnut\Lang\Blueprint\Expression;

use Stringable;

final readonly class MatchExpressionPair implements Stringable {
	public function __construct(
		public Expression $matchExpression,
		public Expression $valueExpression,
	) {}

	public function __toString(): string {
		return sprintf("%s: %s",
			$this->matchExpression,
			$this->valueExpression
		);
	}
}