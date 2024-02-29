<?php

namespace Walnut\Lang\Blueprint\Expression;

use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;

interface PropertyAccessExpression extends Expression {
	public function target(): Expression;
	public function propertyName(): PropertyNameIdentifier;
}