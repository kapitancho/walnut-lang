<?php

namespace Walnut\Lang\Implementation\Registry;

use SplObjectStorage;
use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Expression\MethodCallExpression;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Function\CustomMethod;
use Walnut\Lang\Blueprint\Function\UnknownMethod;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Registry\DependencyContainer as DependencyContainerInterface;
use Walnut\Lang\Blueprint\Registry\DependencyError;
use Walnut\Lang\Blueprint\Registry\ExpressionRegistry;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Registry\UnresolvableDependency;
use Walnut\Lang\Blueprint\Registry\ValueRegistry;
use Walnut\Lang\Blueprint\Type\AliasType;
use Walnut\Lang\Blueprint\Type\AtomType;
use Walnut\Lang\Blueprint\Type\NamedType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\StateType;
use Walnut\Lang\Blueprint\Type\SubtypeType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\ErrorValue;
use Walnut\Lang\Blueprint\Value\StateValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Execution\VariableValueScope;

final class DependencyContainer implements DependencyContainerInterface {

	/** @var SplObjectStorage<Type, Value|DependencyError> */
	private SplObjectStorage $cache;
	/** @var SplObjectStorage<Type> */
	private SplObjectStorage $visited;
	private readonly MethodCallExpression $containerCastExpression;

	public function __construct(
		private readonly ValueRegistry $valueRegistry,
		private readonly MethodRegistry $methodRegistry,
		private readonly ExpressionRegistry $expressionRegistry,
	) {
		$this->cache = new SplObjectStorage;
		$this->visited = new SplObjectStorage;
	}

	private function containerCastExpression(): MethodCallExpression {
		return $this->containerCastExpression ??= $this->expressionRegistry->methodCall(
			$this->expressionRegistry->constant(
				$this->valueRegistry->atom(new TypeNameIdentifier('DependencyContainer'))
			),
			new MethodNameIdentifier('as'),
			$this->expressionRegistry->variableName(new VariableNameIdentifier('#'))
		);
	}

	private function findInGlobalScope(Type $type): Value|DependencyError {
		$found = [];
		foreach($this->valueRegistry->variables()->all() as $typedValue) {
			if ($typedValue->type->isSubTypeOf($type)) {
				$found[] = $typedValue->value;
			}
		}
		return match(true) {
			count($found) === 0 => new DependencyError(UnresolvableDependency::notFound, $type),
			count($found) === 1 => $found[0],
			default => new DependencyError(UnresolvableDependency::ambiguous, $type)
		};
	}

	private function findValueByNamedType(NamedType $type): Value|DependencyError {
		$found = $this->findInGlobalScope($type);
		if ($found instanceof Value) {
			return $found;
		}
		try {
			$result = $this->containerCastExpression()->execute(VariableValueScope::fromPairs(
				new VariableValuePair(
					new VariableNameIdentifier('#'),
					TypedValue::forValue(
						$this->valueRegistry->type(
							$type
						)
					)
				)
			))->value();
			if ($result instanceof ErrorValue && $result->errorValue() instanceof StateValue &&
				$result->errorValue()->type()->name()->equals(new TypeNameIdentifier('CastNotAvailable'))
			) {
				if ($found instanceof DependencyError &&
					$found->unresolvableDependency === UnresolvableDependency::notFound &&
					$type instanceof AliasType
				) {
					return $this->attemptToFindAlias($type);
				}
				return $found;
			}
			return $result;
		} catch (AnalyserException) {
			return $found;
		}
	}

	private function attemptToFindAlias(AliasType $aliasType): Value|DependencyError {
		$baseType = $aliasType->aliasedType();
		return $this->findValueByType($baseType);
	}

	private function findTupleValue(TupleType $tupleType): Value|DependencyError {
		$found = [];
		foreach($tupleType->types() as $type) {
			$foundValue = $this->valueByType($type);
			if ($foundValue instanceof DependencyError) {
				return $foundValue;
			}
			$found[] = $foundValue;
		}
		return $this->valueRegistry->list($found);
	}

	private function findRecordValue(RecordType $recordType): Value|DependencyError {
		$found = [];
		foreach($recordType->types() as $key => $field) {
			$foundValue = $this->valueByType($field);
			if ($foundValue instanceof DependencyError) {
				return $foundValue;
			}
			$found[$key] = $foundValue;
		}
		return $this->valueRegistry->dict($found);
	}

    private function findSubtypeValue(SubtypeType $type): Value|DependencyError {
        $found = $this->findValueByNamedType($type);
        if ($found instanceof DependencyError) {
            $baseValue = $this->findValueByType($type->baseType());
            if ($baseValue instanceof Value) {
                $result = $type->constructorBody()->expression()->execute(
                    VariableValueScope::fromPairs(
                        new VariableValuePair(
                            new VariableNameIdentifier('#'),
                            TypedValue::forValue($baseValue)
                        )
                    )
                );
                if ($result->value() instanceof ErrorValue) {
                    return new DependencyError(UnresolvableDependency::errorWhileCreatingValue, $type);
                }
                return $this->valueRegistry->subtypeValue(
                    $type->name(),
                    $baseValue
                );
            }
        }
        return $found;
    }

    private function findStateValue(StateType $type): Value|DependencyError {
        $found = $this->findValueByNamedType($type);
        if ($found instanceof DependencyError) {
            $constructor = $this->valueRegistry->atom(new TypeNameIdentifier('Constructor'));
            $method = $this->methodRegistry->method($constructor->type(),
                $methodName = new MethodNameIdentifier($type->name()->identifier));

            if ($method instanceof UnknownMethod) {
                $baseValue = $this->findValueByType($type->stateType());
                if ($baseValue instanceof Value) {
                    return $this->valueRegistry->stateValue(
                        $type->name(),
                        $baseValue
                    );
                }
				return $found;
            }
			if ($method instanceof CustomMethod) {
                $baseValue = $this->findValueByType($method->parameterType());
				if ($baseValue instanceof DependencyError) {
					return $found;
				}
                $stateValue = $this->expressionRegistry->methodCall(
                    $this->expressionRegistry->constant($constructor),
                    $methodName,
                    $this->expressionRegistry->variableName(new VariableNameIdentifier('#'))
                )->execute(VariableValueScope::fromPairs(
                    new VariableValuePair(
                        new VariableNameIdentifier('#'),
                        TypedValue::forValue($baseValue)
                    )
                ))->value();
                return $this->valueRegistry->stateValue(
                    $type->name(),
                    $stateValue
                );
            }
        }
        return $found;
    }

	private function findValueByType(Type $type): Value|DependencyError {
		return match(true) {
			$type instanceof AtomType => $type->value(),
            $type instanceof SubtypeType => $this->findSubtypeValue($type),
            $type instanceof StateType => $this->findStateValue($type),
			$type instanceof NamedType => $this->findValueByNamedType($type),
			$type instanceof TupleType => $this->findTupleValue($type),
			$type instanceof RecordType => $this->findRecordValue($type),
			default => new DependencyError(UnresolvableDependency::unsupportedType, $type)
		};
	}

	public function valueByType(Type $type): Value|DependencyError {
		if ($this->visited->contains($type)) {
			return new DependencyError(UnresolvableDependency::circularDependency, $type);
		}
		$cached = $this->cache[$type] ?? null;
		if ($cached) {
			return $cached;
		}
		$this->visited->attach($type);
		$result = $this->findValueByType($type);
		if ((!$result instanceof DependencyError) && !$result->type()->isSubtypeOf($type)) {
			$result = new DependencyError(UnresolvableDependency::errorWhileCreatingValue, $type);
		}
		$this->cache[$type] = $result;
		$this->visited->detach($type);
		return $result;
	}
}