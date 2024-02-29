<?php

namespace Walnut\Lang\Implementation\NativeCode\DatabaseConnector;

use PDO;
use PDOException;
use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\StateType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\StateValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Execute implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		if ($targetType instanceof StateType && $targetType->name()->equals(
			new TypeNameIdentifier('DatabaseConnector')
		)) {
			if ($parameterType->isSubtypeOf(
				$this->context->typeRegistry->withName(
					new TypeNameIdentifier('DatabaseQueryCommand')
				)
			)) {
				return $this->context->typeRegistry->result(
					$this->context->typeRegistry->integer(0),
					$this->context->typeRegistry->withName(
						new TypeNameIdentifier('DatabaseQueryFailure')
					)
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
		$targetValue = $this->context->toBaseValue($targetValue);
		if ($targetValue instanceof StateValue && $targetValue->type()->name()->equals(
			new TypeNameIdentifier('DatabaseConnector')
		)) {
			if ($parameter->type()->isSubtypeOf(
				$this->context->typeRegistry->withName(
					new TypeNameIdentifier('DatabaseQueryCommand')
				)
			)) {
				$dsn = $targetValue->stateValue()->valueOf(new PropertyNameIdentifier('connection'))
					->baseValue()->values()['dsn']->literalValue();
				try {
					$pdo = new PDO($dsn);
					$stmt = $pdo->prepare($parameter->values()['query']->literalValue());
					$stmt->execute(array_map(static fn(Value $value): string|int|null =>
						$value->literalValue(), $parameter->values()['boundParameters']->values()
					));
					$rowCount = $stmt->rowCount();
					return $this->context->valueRegistry->integer(
						$rowCount
					);
				} catch (PDOException $ex) {
					return $this->context->valueRegistry->error(
						$this->context->valueRegistry->stateValue(
							new TypeNameIdentifier('DatabaseQueryFailure'),
							$this->context->valueRegistry->dict([
								'query' => $parameter->values()['query'],
								'boundParameters' => $parameter->values()['boundParameters'],
								'error' => $this->context->valueRegistry->string($ex->getMessage())
							])
						)
					);
				}
			}
			// @codeCoverageIgnoreStart
			throw new AnalyserException("Invalid parameter value");
			// @codeCoverageIgnoreEnd
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}