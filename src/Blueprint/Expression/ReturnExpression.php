<?php

namespace Walnut\Lang\Blueprint\Expression;

interface ReturnExpression extends Expression {
	public function returnedExpression(): Expression;
}