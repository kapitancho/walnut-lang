<?php

namespace Walnut\Lang\Implementation\Registry;

use SplObjectStorage;
use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Registry\DependencyContainer as DependencyContainerInterface;
use Walnut\Lang\Blueprint\Registry\ExpressionRegistry;
use Walnut\Lang\Blueprint\Registry\UnresolvableDependency;
use Walnut\Lang\Blueprint\Type\AliasType;
use Walnut\Lang\Blueprint\Type\AtomType;
use Walnut\Lang\Blueprint\Type\NamedType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\StateValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Expression\MethodCallExpression;
use Walnut\Lang\Implementation\Value\ErrorValue;

final class DependencyContainer implements DependencyContainerInterface {

	/** @var SplObjectStorage<Type, Value|UnresolvableDependency> */
	private SplObjectStorage $cache;
	/** @var SplObjectStorage<Type> */
	private SplObjectStorage $visited;
	private readonly MethodCallExpression $containerCastExpression;

	public function __construct(
		private readonly ValueRegistry $valueRegistry,
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

	private function findInGlobalScope(Type $type): Value|UnresolvableDependency {
		$found = [];
		foreach($this->valueRegistry->variables()->all() as $typedValue) {
			if ($typedValue->type->isSubTypeOf($type)) {
				$found[] = $typedValue->value;
			}
		}
		return match(true) {
			count($found) === 0 => UnresolvableDependency::notFound,
			count($found) === 1 => $found[0],
			default => UnresolvableDependency::ambiguous
		};
	}

	private function findValueByNamedType(NamedType $type): Value|UnresolvableDependency {
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
				if ($found === UnresolvableDependency::notFound && $type instanceof AliasType) {
					return $this->attemptToFindAlias($type);
				}
				return $found;
			}
			return $result;
		} catch (AnalyserException) {
			return $found;
		}
	}

	private function attemptToFindAlias(AliasType $aliasType): Value|UnresolvableDependency {
		$baseType = $aliasType->aliasedType();
		return $this->findValueByType($baseType);
	}

	private function findTupleValue(TupleType $tupleType): Value|UnresolvableDependency {
		$found = [];
		foreach($tupleType->types() as $type) {
			$foundValue = $this->valueByType($type);
			if ($foundValue instanceof UnresolvableDependency) {
				return $foundValue;
			}
			$found[] = $foundValue;
		}
		return $this->valueRegistry->list($found);
	}

	private function findRecordValue(RecordType $recordType): Value|UnresolvableDependency {
		$found = [];
		foreach($recordType->types() as $key => $field) {
			$foundValue = $this->valueByType($field);
			if ($foundValue instanceof UnresolvableDependency) {
				return $foundValue;
			}
			$found[$key] = $foundValue;
		}
		return $this->valueRegistry->dict($found);
	}

	private function findValueByType(Type $type): Value|UnresolvableDependency {
		return match(true) {
			$type instanceof AtomType => $type->value(),
			$type instanceof NamedType => $this->findValueByNamedType($type),
			$type instanceof TupleType => $this->findTupleValue($type),
			$type instanceof RecordType => $this->findRecordValue($type),
			default => UnresolvableDependency::unsupportedType
		};
	}

	public function valueByType(Type $type): Value|UnresolvableDependency {
		if ($this->visited->contains($type)) {
			return UnresolvableDependency::circularDependency;
		}
		$cached = $this->cache[$type] ?? null;
		if ($cached) {
			return $cached;
		}
		$this->visited->attach($type);
		$result = $this->findValueByType($type);
		$this->cache[$type] = $result;
		$this->visited->detach($type);
		return $result;
	}
}