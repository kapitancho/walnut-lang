<?php

namespace Walnut\Lang\Blueprint\Expression;

interface NoErrorExpression extends Expression {
	public function targetExpression(): Expression;
}