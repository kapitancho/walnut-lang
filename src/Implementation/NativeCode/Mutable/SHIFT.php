<?php

namespace Walnut\Lang\Implementation\NativeCode\Mutable;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\MutableType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\MutableValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class SHIFT implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$t = $this->context->toBaseType($targetType);
		if ($t instanceof MutableType && $t->valueType() instanceof ArrayType && $t->valueType()->range()->minLength() === 0) {
			return $this->context->typeRegistry->result(
				$t->valueType()->itemType(),
				$this->context->typeRegistry->atom(new TypeNameIdentifier("ItemNotFound"))
			);
		}
		// @codeCoverageIgnoreStart
		throw new AnalyserException(sprintf("[%s] Invalid target type: %s", __CLASS__, $targetType));
		// @codeCoverageIgnoreEnd
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): Value {
		$v = $this->context->toBaseValue($targetValue);
		if ($v instanceof MutableValue) {
			if ($v->targetType() instanceof ArrayType) {
				$values = $v->value()->values();
				if (count($values) > 0) {
					$value = array_shift($values);
					$v->changeValueTo($this->context->valueRegistry->list($values));
					return $value;
				}
				return $this->context->valueRegistry->error(
					$this->context->valueRegistry->atom(new TypeNameIdentifier("ItemNotFound"))
				);
			}
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}