<?php

namespace Walnut\Lang\Implementation\Expression;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableScope as VariableScopeInterface;
use Walnut\Lang\Blueprint\Execution\VariableValueScope as VariableValueScopeInterface;
use Walnut\Lang\Blueprint\Expression\VariableNameExpression;
use Walnut\Lang\Blueprint\Helper\BaseTypeHelper;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\PropertyAccessExpression as PropertyAccessExpressionInterface;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\IntersectionType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\StateType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\UnknownProperty;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\StateValue;
use Walnut\Lang\Implementation\Type\MapType;
use Walnut\Lang\Implementation\Type\UnionType;

final readonly class PropertyAccessExpression implements PropertyAccessExpressionInterface {
	use BaseTypeHelper;

	public function __construct(
		private TypeRegistry $typeRegistry,
		private Expression $target,
		private PropertyNameIdentifier $propertyName,
	) {}

	public function target(): Expression {
		return $this->target;
	}

	public function propertyName(): PropertyNameIdentifier {
		return $this->propertyName;
	}

	private function getPropertyType(Type $retType): Type {
		$retType = $this->toBaseType($retType);
		if ($retType instanceof UnionType) {
			$pTypes = [];
			foreach($retType->types() as $type) {
				$pTypes[] = $this->getPropertyType($type);
			}
			if (count($pTypes) > 0) {
				return $this->typeRegistry->union($pTypes);
			}
		}
		if ($retType instanceof IntersectionType) {
			$pTypes = [];
			foreach($retType->types() as $type) {
				try {
					$pTypes[] = $this->getPropertyType($type);
				} catch (AnalyserException) {}
			}
			if (count($pTypes) > 0) {
				return $this->typeRegistry->intersection($pTypes);
			}
		}
		if ($retType instanceof RecordType) {
			try {
				$propertyType = $retType->typeOf($this->propertyName);
			} catch (UnknownProperty) {
				throw new AnalyserException(
					sprintf(
						"Unknown property %s on a record type %s",
						$this->propertyName,
						$retType
					)
				);
			}
			return $propertyType;
		}
		if ($retType instanceof TupleType) {
			if (preg_match('/^0|[1-9]\d*$/', $this->propertyName->identifier)) {
				try {
					$propertyType = $retType->typeOf((int)$this->propertyName->identifier);
				} catch (UnknownProperty) {
					throw new AnalyserException(
						sprintf(
							"Unknown property %s on tuple type %s",
							$this->propertyName,
							$retType
						)
					);
				}
				return $propertyType;
			}
			throw new AnalyserException(
				sprintf(
					"Cannot access non-numeric property '%s' on a tuple '%s'",
					$this->propertyName,
					$retType
				)
			);
		}
		throw new AnalyserException(
			sprintf(
				"Cannot access property '%s' on type '%s'",
				$this->propertyName,
				$retType
			)
		);
	}

	public function analyse(VariableScopeInterface $variableScope): ExecutionResultContext {
		$ret = $this->target->analyse($variableScope);
		if ($ret->expressionType instanceof StateType &&
			$this->target instanceof VariableNameExpression &&
			$this->target->variableName()->equals(new VariableNameIdentifier('$'))
		) {
			$stateType = $ret->expressionType->stateType();
			if ($stateType instanceof RecordType) {
				try {
					$propertyType = $stateType->typeOf($this->propertyName);
				} catch (UnknownProperty) {
					throw new AnalyserException(
						sprintf(
							"Unknown property %s on a state type %s",
							$this->propertyName,
							$stateType
						)
					);
				}
				return $ret->withExpressionType($propertyType);
			}
			if ($stateType instanceof TupleType) {
				try {
					$propertyType = $stateType->typeOf((int)$this->propertyName->identifier);
				} catch (UnknownProperty) {
					throw new AnalyserException(
						sprintf(
							"Unknown property %s on a state type %s",
							$this->propertyName,
							$stateType
						)
					);
				}
				return $ret->withExpressionType($propertyType);
			}
		}

		$retType = $this->toBaseType($ret->expressionType);
		return $ret->withExpressionType($this->getPropertyType($retType));
	}

	public function execute(VariableValueScopeInterface $variableValueScope): ExecutionResultValueContext {
		$ret = $this->target->execute($variableValueScope);
		$retValue = $this->toBaseValue($ret->value());
		if ($retValue instanceof StateValue &&
			$this->target instanceof VariableNameExpression &&
			$this->target->variableName()->equals(new VariableNameIdentifier('$'))
		) {
			$stateValue = $retValue->stateValue();
			if ($stateValue instanceof DictValue) {
				$propertyValue = $stateValue->valueOf($this->propertyName);
				$t = $ret->valueType();
				$propertyValueType = match(true) {
					$t instanceof RecordType => $t->typeOf($this->propertyName),
					$t instanceof MapType => $t->itemType(),
					default => $propertyValue->type()
				};
				return $ret->withTypedValue(new TypedValue($propertyValueType, $propertyValue));
			}
			if ($stateValue instanceof ListValue) {
				$propertyValue = $stateValue->valueOf((int)$this->propertyName->identifier);
				$t = $ret->valueType();
				$propertyValueType = match(true) {
					$t instanceof TupleType => $t->typeOf((int)$this->propertyName->identifier),
					$t instanceof ArrayType => $t->itemType(),
					default => $propertyValue->type()
				};
				return $ret->withTypedValue(new TypedValue($propertyValueType, $propertyValue));
			}
		}

		if ($retValue instanceof DictValue) {
			$propertyValue = $retValue->valueOf($this->propertyName);
			$t = $ret->valueType();
			$propertyValueType = match(true) {
				$t instanceof RecordType => $t->typeOf($this->propertyName),
				$t instanceof MapType => $t->itemType(),
				default => $propertyValue->type()
			};
			return $ret->withTypedValue(new TypedValue($propertyValueType, $propertyValue));
		}
		if ($retValue instanceof ListValue && preg_match('/^0|[1-9]\d*$/', $this->propertyName->identifier)) {
			$propertyValue = $retValue->valueOf((int)$this->propertyName->identifier);
			$t = $ret->valueType();
			$propertyValueType = match(true) {
				$t instanceof RecordType => $t->typeOf($this->propertyName),
				$t instanceof MapType => $t->itemType(),
				default => $propertyValue->type()
			};
			return $ret->withTypedValue(new TypedValue($propertyValueType, $propertyValue));
		}
		throw new ExecutionException(
			sprintf(
				"Cannot access property '%s' on type '%s'",
				$this->propertyName,
				$retValue->type()
			)
		);
	}

	public function __toString(): string {
		return sprintf(
			"%s.%s",
			$this->target,
			$this->propertyName
		);
	}
}