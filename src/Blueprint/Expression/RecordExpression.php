<?php

namespace Walnut\Lang\Blueprint\Expression;

interface RecordExpression extends Expression {
	/** @return array<string, Expression> */
	public function values(): array;
}