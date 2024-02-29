<?php

namespace Walnut\Lang\Implementation\NativeCode\Map;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\MapType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\FunctionValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class FilterKeyValue implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	private function getExpectedType(Type $targetType): Type {
		return $this->context->typeRegistry->function(
			$this->context->typeRegistry->record([
				'key' => $this->context->typeRegistry->string(),
				'value' => $targetType
			]),
			$this->context->typeRegistry->boolean()
		);
	}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof RecordType) {
			$targetType = $targetType->asMapType();
		}
		if ($targetType instanceof MapType) {
			$expectedType = $this->getExpectedType($targetType->itemType());
			if ($parameterType->isSubtypeOf($expectedType)) {
				//if ($targetType->itemType()->isSubtypeOf($parameterType->parameterType())) {
					return $this->context->typeRegistry->map(
						$targetType->itemType(),
						0,
						$targetType->range()->maxLength()
					);
				//}
				/*throw new AnalyserException(
					sprintf(
						"The parameter type %s of the callback function is not a subtype of %s",
						$targetType->itemType(),
						$parameterType->parameterType()
					)
				);*/
			}
			// @codeCoverageIgnoreStart
			throw new AnalyserException(sprintf("[%s] Invalid parameter type: %s", __CLASS__, $parameterType));
			// @codeCoverageIgnoreEnd
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
		$targetValue = $this->context->toBaseValue($targetValue);
		if ($targetValue instanceof DictValue) {
			if ($parameter instanceof FunctionValue) {
				$values = $targetValue->values();
				$result = [];
				$true = $this->context->valueRegistry->true();
				foreach($values as $key => $value) {
					$filterResult = $parameter->execute(
						$this->context->valueRegistry->dict([
							'key' => $this->context->valueRegistry->string($key),
							'value' => $value
						])
					);
					if ($filterResult->equals($true)) {
						$result[$key] = $value;
					}
				}
				return $this->context->valueRegistry->dict($result);
			}
			// @codeCoverageIgnoreStart
			throw new ExecutionException("Invalid parameter value");
			// @codeCoverageIgnoreEnd
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}