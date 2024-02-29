<?php

namespace Walnut\Lang\Implementation\NativeCode\Any;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Function\UnknownMethod;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Type\AliasType;
use Walnut\Lang\Blueprint\Type\Type as TypeInterface;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\NativeCode\NativeCodeTypeMapper;
use Walnut\Lang\Implementation\Type\ResultType;

final readonly class CastAs implements Method {

	public function __construct(
		private NativeCodeContext $context,
		private MethodRegistry $methodRegistry,
		private NativeCodeTypeMapper $typeMapper,
	) {}

	/**
	 * @return array{0: string, 1: Method}|UnknownMethod
	 */
	private function getMethod(
		TypeInterface $targetType,
		TypeInterface $parameterType
	): array|UnknownMethod {
		foreach($this->typeMapper->getTypesFor($parameterType) as $candidate) {
			$method = $this->methodRegistry->method($targetType,
				$methodName = new MethodNameIdentifier(sprintf('as%s',
					$candidate
				))
			);
			if ($method instanceof Method) {
				return [$methodName, $method];
			}
		}
		return UnknownMethod::value;
	}

	public function analyse(
		TypeInterface $targetType,
		TypeInterface $parameterType,
		TypeInterface|null $dependencyType,
	): TypeInterface {
		if ($parameterType instanceof TypeType) {
			$refType = $parameterType->refType();
			if ($targetType->isSubtypeOf($refType)) {
				return $refType;
			}
			$method = $this->getMethod($targetType, $refType);
			if ($method instanceof UnknownMethod) {
				return $this->context->typeRegistry->result(
					$refType,
					$this->context->typeRegistry->withName(new TypeNameIdentifier('CastNotAvailable'))
				);
				/*throw new AnalyserException(
					sprintf(
						"Cannot cast type %s to %s",
						$targetType,
						$refType
					)
				);*/
			}
			$returnType = $method[1]->analyse(
				$targetType,
				$this->context->typeRegistry->nothing(),
				null
			);
			$resultType = $returnType instanceof ResultType ? $returnType->returnType() : $returnType;

			if (!$resultType->isSubtypeOf($refType)) {
				throw new AnalyserException(sprintf(
					"Cast method '%s' returns '%s' which is not a subtype of '%s'",
					$method[0],
					$resultType,
					$refType
				));
			}
			return $refType instanceof AliasType ? $refType : $returnType;
		}
		// @codeCoverageIgnoreStart
		throw new AnalyserException(sprintf("[%s] Invalid parameter type: %s", __CLASS__, $parameterType));
		// @codeCoverageIgnoreEnd
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): TypedValue {
		if ($parameter instanceof TypeValue) {
			if ($targetValue->type()->isSubtypeOf($parameter->typeValue())) {
				return new TypedValue($parameter->typeValue(), $targetValue);
			}
			$method = $this->getMethod(
				$targetType = $targetValue->type(),
				$parameterType = $parameter->typeValue());
			if ($method instanceof UnknownMethod) {
				$val = $this->context->valueRegistry->error(
					$this->context->valueRegistry->stateValue(
						new TypeNameIdentifier('CastNotAvailable'),
						$this->context->valueRegistry->dict([
							'from' => $this->context->valueRegistry->type($targetType),
							'to' => $this->context->valueRegistry->type($parameterType)
						])
					)
				);
				return TypedValue::forValue($val);
				/*throw new AnalyserException(
					sprintf(
						"Cannot cast type %s to %s",
						$targetType,
						$parameterType
					)
				);*/
			}
			$result = $method[1]->execute($targetValue, $parameter, null);
			return $result instanceof TypedValue ? $result : new TypedValue($parameterType, $result);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid parameter value");
		// @codeCoverageIgnoreEnd
	}

}