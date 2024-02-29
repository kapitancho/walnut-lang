<?php

namespace Walnut\Lang\Blueprint\Expression;

interface SequenceExpression extends Expression {
	/** @return list<Expression> */
	public function expressions(): array;
}