<?php

namespace Walnut\Lang\Implementation\NativeCode\Type;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\EnumerationSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\NullType;
use Walnut\Lang\Blueprint\Type\RealSubsetType;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\Type as TypeInterface;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Value\NullValue;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Values implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		TypeInterface $targetType,
		TypeInterface $parameterType,
		TypeInterface|null $dependencyType,
	): TypeInterface {
		if ($parameterType instanceof NullType) {
			if ($targetType instanceof TypeType) {
				$refType = $targetType->refType();
				if ($refType instanceof IntegerSubsetType ||
					$refType instanceof RealSubsetType ||
					$refType instanceof StringSubsetType ||
					$refType instanceof EnumerationSubsetType
				) {
					$t = match(true) {
						$refType instanceof IntegerSubsetType => $this->context->typeRegistry->integer(),
						$refType instanceof RealSubsetType => $this->context->typeRegistry->real(),
						$refType instanceof StringSubsetType => $this->context->typeRegistry->string(),
						$refType instanceof EnumerationSubsetType => $this->context->typeRegistry->enumeration(
							$refType->enumeration()->name(),
						),
					};
					$l = count($refType->subsetValues());
					return $this->context->typeRegistry->array($t, $l, $l);
				}
			}
			// @codeCoverageIgnoreStart
			throw new AnalyserException(sprintf("[%s] Invalid target type: %s", __CLASS__, $targetType));
			// @codeCoverageIgnoreEnd
		}
		// @codeCoverageIgnoreStart
		throw new AnalyserException(sprintf("[%s] Invalid parameter type: %s", __CLASS__, $parameterType));
		// @codeCoverageIgnoreEnd
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): Value {
		if ($parameter instanceof NullValue) {
			if ($targetValue instanceof TypeValue) {
				$refType = $targetValue->typeValue();
				if ($refType instanceof IntegerSubsetType ||
					$refType instanceof RealSubsetType ||
					$refType instanceof StringSubsetType ||
					$refType instanceof EnumerationSubsetType
				) {
					return $this->context->valueRegistry->list($refType->subsetValues());
				}
			}
			// @codeCoverageIgnoreStart
			throw new ExecutionException("Invalid target value");
			// @codeCoverageIgnoreEnd
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid parameter value");
		// @codeCoverageIgnoreEnd
	}

}