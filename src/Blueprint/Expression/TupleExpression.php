<?php

namespace Walnut\Lang\Blueprint\Expression;

interface TupleExpression extends Expression {
	/** @return list<Expression> */
	public function values(): array;
}