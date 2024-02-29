<?php

namespace Walnut\Lang\Blueprint\Expression;

use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;

interface VariableNameExpression extends Expression {
	public function variableName(): VariableNameIdentifier;
}