<?php

namespace Walnut\Lang\Implementation\NativeCode\String;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class PadRight implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof StringType || $targetType instanceof StringSubsetType) {
			$parameterType = $this->context->toBaseType($parameterType);
			if ($parameterType instanceof RecordType) {
				$types = $parameterType->types();
				$lengthType = $types['length'] ?? null;
				$padStringType = $types['padString'] ?? null;
				if (($lengthType instanceof IntegerType || $lengthType instanceof IntegerSubsetType) &&
					($padStringType instanceof StringType || $padStringType instanceof StringSubsetType)
				) {
					return $this->context->typeRegistry->string(
						max($targetType->range()->minLength(), $lengthType->range()->minValue()),
						$targetType->range()->maxLength() === PlusInfinity::value ||
							$lengthType->range()->maxValue() === PlusInfinity::value ? PlusInfinity::value :
							max($targetType->range()->maxLength(), $lengthType->range()->maxValue()),
					);
				}
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
		$parameter = $this->context->toBaseValue($parameter);
		if ($targetValue instanceof StringValue) {
			if ($parameter instanceof DictValue) {
				$values = $parameter->values();
				$length = $values['length'] ?? null;
				$padString = $values['padString'] ?? null;
				if ($length instanceof IntegerValue && $padString instanceof StringValue) {
					$result = str_pad(
						$targetValue->literalValue(),
						$length->literalValue(),
						$padString->literalValue()
                    );
					return $this->context->valueRegistry->string($result);
				}
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