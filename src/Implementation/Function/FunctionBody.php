<?php

namespace Walnut\Lang\Implementation\Function;

use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Function\FunctionBody as FunctionBodyInterface;

final readonly class FunctionBody implements FunctionBodyInterface {

	public function __construct(
		private Expression $expression
	) {}

	public function expression(): Expression {
		return $this->expression;
	}

	public function __toString(): string {
		return (string)$this->expression;
	}
}