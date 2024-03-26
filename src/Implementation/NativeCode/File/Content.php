<?php

namespace Walnut\Lang\Implementation\NativeCode\File;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\StateType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\StateValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Content implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		if ($targetType instanceof StateType && $targetType->name()->equals(
			new TypeNameIdentifier('File')
		)) {
			return $this->context->typeRegistry->result(
				$this->context->typeRegistry->string(),
				$this->context->typeRegistry->withName(
					new TypeNameIdentifier('CannotReadFile')
				)
			);
		}
		// @codeCoverageIgnoreStart
		throw new AnalyserException(sprintf("[%s] Invalid target type: %s", __CLASS__, $targetType));
		// @codeCoverageIgnoreEnd
	}

	public function execute(Value $targetValue, Value $parameter, ?Value $dependencyValue,): Value|TypedValue {
		$targetValue = $this->context->toBaseValue($targetValue);
		if ($targetValue instanceof StateValue && $targetValue->type()->name()->equals(
			new TypeNameIdentifier('File')
		)) {
			$path = $targetValue->stateValue()->valueOf(new PropertyNameIdentifier('path'))->literalValue();
			$contents = @file_get_contents($path);
			if ($contents === false) {
				return $this->context->valueRegistry->error(
					$this->context->valueRegistry->stateValue(
						new TypeNameIdentifier('CannotReadFile'),
						$targetValue
					)
				);
			}
			return $this->context->valueRegistry->string($contents);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}
}