<?php

namespace Walnut\Lang\Blueprint\Expression;

interface MatchExpression extends Expression {
	public function target(): Expression;
	public function operation(): MatchExpressionOperation;
	/** @return list<MatchExpressionPair|MatchExpressionDefault> */
	public function pairs(): array;
}