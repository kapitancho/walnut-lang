<?php

namespace Walnut\Lang\Blueprint\Expression;

use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;

interface ConstructorCallExpression extends Expression {
	public function typeName(): TypeNameIdentifier;
	public function parameter(): Expression;
}