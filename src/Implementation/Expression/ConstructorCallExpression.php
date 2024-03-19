<?php

namespace Walnut\Lang\Implementation\Expression;

use JsonSerializable;
use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\ReturnResult;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableScope as VariableScopeInterface;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Execution\VariableValueScope as VariableValueScopeInterface;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Helper\BaseTypeHelper;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\ConstructorCallExpression as ConstructorCallExpressionInterface;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Registry\ValueRegistry;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\ResultType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Type\UnknownType;
use Walnut\Lang\Blueprint\Value\ErrorValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Type\SubtypeType;
use Walnut\Lang\Implementation\Value\TypeValue;

final readonly class ConstructorCallExpression implements ConstructorCallExpressionInterface, JsonSerializable {
	use BaseTypeHelper;

	public function __construct(
		private TypeRegistry $typeRegistry,
		private ValueRegistry $valueRegistry,
		private MethodRegistry $methodRegistry,
		private TypeNameIdentifier $typeName,
		private Expression $parameter,
	) {}

	public function typeName(): TypeNameIdentifier {
		return $this->typeName;
	}

	public function parameter(): Expression {
		return $this->parameter;
	}

	public function analyse(VariableScopeInterface $variableScope): ExecutionResultContext {
		if ($this->typeName->equals(new TypeNameIdentifier('Mutable'))) {
			$retParam = $this->parameter->analyse($variableScope);
			$retParamType = $this->toBaseType($retParam->expressionType);

			if ($retParamType instanceof TupleType && count($retParamType->types()) === 2) {
				[$mutableType, $mutableValue] = $retParamType->types();
				if ($mutableType instanceof TypeType) {
					$mutableRefType = $mutableType->refType();
					if ($mutableValue->isSubtypeOf($mutableRefType)) {
						return $retParam->withExpressionType(
							$this->typeRegistry->mutable($mutableRefType)
						);
					}
					// @codeCoverageIgnoreStart
					throw new AnalyserException(
						sprintf(
							"The initial value of a Mutable type should be a subtype of %s. %s provided",
							$mutableRefType,
							$mutableValue,
						)
					);
					// @codeCoverageIgnoreEnd
				}
			}
			// @codeCoverageIgnoreStart
			throw new AnalyserException("A Mutable type constructor requires a tuple parameter containing the type and the initial value");
			// @codeCoverageIgnoreEnd
		}
		if ($this->typeName->equals(new TypeNameIdentifier('Type'))) {
			$retParam = $this->parameter->analyse($variableScope);
			$retParamType = $this->toBaseType($retParam->expressionType);

			if ($retParamType instanceof TypeType) {
				return $retParam->withExpressionType(
					$this->typeRegistry->type($retParamType->refType())
				);
			}
			// @codeCoverageIgnoreStart
			throw new AnalyserException("A Type type constructor requires a parameter containing the type");
			// @codeCoverageIgnoreEnd
		}
		if ($this->typeName->equals(new TypeNameIdentifier('Error'))) {
			$retParam = $this->parameter->analyse($variableScope);
			$retParamType = $this->toBaseType($retParam->expressionType);

			return $retParam->withExpressionType(
				$this->typeRegistry->result(
					$this->typeRegistry->nothing(), $retParamType
				)
			);
		}
		try {
			$type = $this->typeRegistry->subtype($this->typeName);

			//TODO - check against record

			$retParam = $this->parameter->analyse($variableScope);
			$retParamType = $retParam->expressionType;
			//$variableScope = $retParam->variableScope;
			if (!($retParamType->isSubtypeOf($type->baseType()) || (
				($rbt = $type->baseType()) instanceof RecordType &&
				$retParamType instanceof TupleType &&
				$this->isTupleCompatibleToRecord(
					$this->typeRegistry, $retParamType, $rbt
				)
			))) {
				// @codeCoverageIgnoreStart
				throw new AnalyserException(
					sprintf(
						"Cannot pass a parameter of type %s to a function expecting a parameter of type %s",
						$retParamType,
						$type->baseType(),
					)
				);
				// @codeCoverageIgnoreEnd
			}
		} catch (UnknownType) {
			try {
				$type = $this->typeRegistry->state($this->typeName);
				$retParam = $this->parameter->analyse($variableScope);
				$retParamType = $retParam->expressionType;

				$constructorType = $this->typeRegistry->withName(new TypeNameIdentifier('Constructor'));

				$method = $this->methodRegistry->method($constructorType,
					new MethodNameIdentifier($this->typeName->identifier));
				if ($method instanceof Method) {
					$methodType = $method->analyse($constructorType, $retParamType, null);
					$methodReturnType = $methodType instanceof ResultType ? $methodType->returnType() : $methodType;
					$methodErrorType = $methodType instanceof ResultType ? $methodType->errorType() : null;

					if ($methodReturnType->isSubtypeOf($type->stateType())) {
						return $retParam->withExpressionType(
							$methodErrorType ?
								$this->typeRegistry->result($type, $methodErrorType) : $type);
					}
					throw new AnalyserException(
						sprintf(
							"Cannot pass a parameter of type %s to a constructor expecting a parameter of type %s",
							$retParamType,
							$type->stateType(),
						)
					);
				}

				$rbt = $type->stateType();

				//$variableScope = $retParam->variableScope;
				if (!($retParamType->isSubtypeOf($rbt) || (
					$rbt instanceof RecordType &&
					$retParamType instanceof TupleType &&
					$this->isTupleCompatibleToRecord(
						$this->typeRegistry, $retParamType, $rbt
					)
				))) {
					// @codeCoverageIgnoreStart
					throw new AnalyserException(
						sprintf(
							"Cannot pass a parameter of type %s to a function expecting a parameter of type %s",
							$retParamType,
							$type->stateType(),
						)
					);
					// @codeCoverageIgnoreEnd
				}
				// @codeCoverageIgnoreStart
			} catch (UnknownType) {
				throw new AnalyserException(
					sprintf(
						"Unknown subtype or state %s",
						$this->typeName
					)
				);
				// @codeCoverageIgnoreEnd
			}
		}

		/*
		$retBody = $type->constructorBody()->expression()->analyse(
			VariableScope::fromPairs(
				new VariablePair(
					new VariableNameIdentifier('#'),
					$type->baseType(),
				)
			)
		);
		$retType = $this->typeRegistry->union([
			$retBody->returnType,
			$retBody->expressionType,
		]);*/
		$errorType = $type instanceof SubtypeType ? $type->errorType() : null;
		return $retParam->withExpressionType(
			$errorType ? $this->typeRegistry->result($type, $errorType) : $type);
	}

	public function execute(VariableValueScopeInterface $variableValueScope): ExecutionResultValueContext {
		if ($this->typeName->equals(new TypeNameIdentifier('Mutable'))) {
			$retParam = $this->parameter->execute($variableValueScope);
			$retParamValue = $retParam->value();

			if ($retParamValue instanceof ListValue && count($retParamValue->values()) === 2) {
				[$mutableType, $mutableValue] = $retParamValue->values();
				if ($mutableType instanceof TypeValue) {
					$mutableRefType = $mutableType->typeValue();
					if ($mutableValue->type()->isSubtypeOf($mutableRefType)) {
						return $retParam->withTypedValue(
							new TypedValue(
								$this->typeRegistry->mutable($mutableRefType),
								$this->valueRegistry->mutable($mutableRefType, $mutableValue)
							)
						);
					}
				}
			}
			// @codeCoverageIgnoreStart
			throw new AnalyserException("A Mutable type constructor requires a tuple parameter containing the type and the initial value");
			// @codeCoverageIgnoreEnd
		}
		if ($this->typeName->equals(new TypeNameIdentifier('Type'))) {
			$retParam = $this->parameter->execute($variableValueScope);
			$retParamValue = $retParam->value();

			if ($retParamValue instanceof TypeValue) {
				return $retParam->withTypedValue(TypedValue::forValue($retParamValue));
			}
			// @codeCoverageIgnoreStart
			throw new AnalyserException("A Mutable type constructor requires a tuple parameter containing the type and the initial value");
			// @codeCoverageIgnoreEnd
		}
		if ($this->typeName->equals(new TypeNameIdentifier('Error'))) {
			$retParam = $this->parameter->execute($variableValueScope);
			$retParamValue = $retParam->value();

			return $retParam->withTypedValue(
				new TypedValue(
					$this->typeRegistry->result(
						$this->typeRegistry->nothing(),
						$retParam->valueType()
					),
					$this->valueRegistry->error($retParamValue)
				)
			);
		}

		try {
			$type = $this->typeRegistry->subtype($this->typeName);
			$retParam = $this->parameter->execute($variableValueScope);
			$retParamValue = $retParam->value();

			try {
				if (($tbt = $type->baseType()) instanceof RecordType && $retParamValue instanceof ListValue) {
					$retParamValue = $this->getTupleAsRecord(
						$this->valueRegistry,
						$retParamValue,
						$tbt,
					);
				}

				$result = $type->constructorBody()->expression()->execute(
					VariableValueScope::fromPairs(
						new VariableValuePair(
							new VariableNameIdentifier('#'),
							new TypedValue(
								$type->baseType(),
								$retParamValue
							)
						)
					)
				);
				if ($result->value() instanceof ErrorValue) {
					return $retParam->withTypedValue(TypedValue::forValue($result->value()));
				}
			} catch (ReturnResult $result) {
				//TODO - more accurate return type
				if ($result->value instanceof ErrorValue) {
					return $retParam->withTypedValue(TypedValue::forValue($result->value));
				}
			}
			$value = $this->valueRegistry->subtypeValue(
				$this->typeName,
				$retParamValue
			);
			return $retParam->withTypedValue(new TypedValue(
				$this->typeRegistry->subtype($this->typeName), $value
			));
		} catch (UnknownType) {
			try {
				$type = $this->typeRegistry->state($this->typeName);
				$baseType = $type->stateType();
				$retParam = $this->parameter->execute($variableValueScope);
				$retParamValue = $retParam->value();

				$constructorType = $this->typeRegistry->atom(new TypeNameIdentifier('Constructor'));

				$method = $this->methodRegistry->method($constructorType,
					new MethodNameIdentifier($this->typeName->identifier));
				if ($method instanceof Method) {
					/*if ($method->analyse() instanceof RecordType && $retParamValue instanceof ListValue) {
						$retParamValue = $this->getTupleAsRecord(
							$this->valueRegistry,
							$retParamValue,
							$baseType,
						);
					}*/
					$retParamValue = $method->execute(
						$constructorType->value(),
						$retParamValue,
						null
					);
					if ($retParamValue instanceof ErrorValue) {
						return $retParam->withTypedValue(TypedValue::forValue($retParamValue));
					}
				}
				if ($baseType instanceof RecordType && $retParamValue instanceof ListValue) {
					$retParamValue = $this->getTupleAsRecord(
						$this->valueRegistry,
						$retParamValue,
						$baseType,
					);
				}

				$value = $this->valueRegistry->stateValue(
					$this->typeName,
					$retParamValue
				);

				return $retParam->withTypedValue(new TypedValue(
					$this->typeRegistry->state($this->typeName), $value
				));
				// @codeCoverageIgnoreStart
			} catch (UnknownType) {
				throw new ExecutionException(
					sprintf(
						"Unknown subtype or state %s",
						$this->typeName
					)
				);
				// @codeCoverageIgnoreEnd
			}
		}
	}

	public function __toString(): string {
		$parameter = (string)$this->parameter;
		if (!($parameter[0] === '[' && $parameter[-1] === ']')) {
			$parameter = "($parameter)";
		}
		if ($parameter === '(null)') {
			$parameter = '()';
		}
		return sprintf(
			"%s%s",
			$this->typeName,
			$parameter
		);
	}

	public function jsonSerialize(): array {
		return [
			'expressionType' => 'constructorCall',
			'typeName' => $this->typeName,
			'parameter' => $this->parameter
		];
	}
}