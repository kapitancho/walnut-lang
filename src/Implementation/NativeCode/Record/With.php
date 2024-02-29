<?php

namespace Walnut\Lang\Implementation\NativeCode\Record;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\MapType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\SubtypeType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\ErrorValue;
use Walnut\Lang\Blueprint\Value\SubtypeValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Execution\VariableValueScope;

final readonly class With implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$originalTargetType = $targetType;
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof RecordType && $parameterType instanceof RecordType) {
			$recTypes = [...$targetType->types(), ...$parameterType->types()];
			$result = $this->context->typeRegistry->record($recTypes);
			if ($originalTargetType instanceof SubtypeType) {
				$type = $originalTargetType->baseType();
				if ($result->isSubtypeOf($type)) {
					return $originalTargetType;
				}
			}
			return $result;
		}
		if ($targetType instanceof RecordType) {
			$targetType = $targetType->asMapType();
		}
		if ($parameterType instanceof RecordType) {
			$parameterType = $parameterType->asMapType();
		}
		if ($targetType instanceof MapType) {
			if ($parameterType instanceof MapType) {
				return $this->context->typeRegistry->map(
					$this->context->typeRegistry->union([
						$targetType->itemType(),
						$parameterType->itemType()
					]),
					max($targetType->range()->minLength(), $parameterType->range()->minLength()),
					$targetType->range()->maxLength() + $parameterType->range()->maxLength()
				);
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
		$originalValue = $targetValue;
		$targetValue = $this->context->toBaseValue($targetValue);
		if ($targetValue instanceof DictValue) {
			if ($parameter instanceof DictValue) {
				$result = $this->context->valueRegistry->dict([
					... $targetValue->values(), ... $parameter->values()
				]);
				if ($originalValue instanceof SubtypeValue) {
					$type = $originalValue->type();
					if ($result->type()->isSubtypeOf($type->baseType())) {
						$callResult = $type->constructorBody()->expression()->execute(
							VariableValueScope::fromPairs(
								new VariableValuePair(
									new VariableNameIdentifier('#'),
									new TypedValue(
										$type->baseType(),
										$result
									)
								)
							)
						);
						$callValue = $callResult->value();
						if ($callValue instanceof ErrorValue) {
							return $callValue;
						}
					}
					$result = $this->context->valueRegistry->subtypeValue(
						$originalValue->type()->name(),
						$callValue
					);
				}
				return $result;
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