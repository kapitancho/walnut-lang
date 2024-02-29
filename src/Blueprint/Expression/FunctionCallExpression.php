<?php

namespace Walnut\Lang\Blueprint\Expression;

interface FunctionCallExpression extends Expression {
	public function target(): Expression;
	public function parameter(): Expression;
}