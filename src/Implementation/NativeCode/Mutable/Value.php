<?php

namespace Walnut\Lang\Implementation\NativeCode\Mutable;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\MutableType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\MutableValue;
use Walnut\Lang\Blueprint\Value\Value as ValueInterface;

final readonly class Value implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$t = $this->context->toBaseType($targetType);
		if ($t instanceof MutableType) {
			return $t->valueType();		}
		// @codeCoverageIgnoreStart
		throw new AnalyserException(sprintf("[%s] Invalid target type: %s", __CLASS__, $targetType));
		// @codeCoverageIgnoreEnd
	}

	public function execute(
		ValueInterface $targetValue,
		ValueInterface $parameter,
		ValueInterface|null $dependencyValue,
	): TypedValue {
		$v = $this->context->toBaseValue($targetValue);
		if ($v instanceof MutableValue) {
			return new TypedValue($v->targetType(), $v->value());
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}