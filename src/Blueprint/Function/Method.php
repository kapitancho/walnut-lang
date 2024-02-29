<?php

namespace Walnut\Lang\Blueprint\Function;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;

interface Method {
	/** @throws AnalyserException */
	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type;

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): Value|TypedValue;
}