<?php

namespace Walnut\Lang\Blueprint\Registry;

use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Function\UnknownMethod;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Type\Type;

interface MethodRegistry {
	public function method(
		Type $targetType,
		MethodNameIdentifier $methodName
	): Method|UnknownMethod;
}