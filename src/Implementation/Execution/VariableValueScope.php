<?php

namespace Walnut\Lang\Implementation\Execution;

use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\UnknownContextVariable;
use Walnut\Lang\Blueprint\Execution\UnknownVariable;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Execution\VariableValueScope as VariableValueScopeInterface;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class VariableValueScope implements VariableValueScopeInterface {
	/** @var list<string> */
	private array $variables;

	/** @param array<string, VariableValuePair> $pairs */
	private function __construct(
		private array $pairs
	) {}

	/** @return string[] */
	public function variables(): array {
		return $this->variables ??= array_keys($this->pairs);
	}

	/**
	 * @return iterable<VariableNameIdentifier, TypedValue>
	 */
	public function all(): iterable {
		foreach($this->pairs as $pair) {
			yield $pair->variableName => $pair->typedValue;
		}
	}

	public function findVariable(VariableNameIdentifier $variableName): VariableValuePair|UnknownVariable {
		return $this->pairs[$variableName->identifier] ?? UnknownVariable::value;
	}

	public function findValueOf(VariableNameIdentifier $variableName): Value|UnknownVariable {
		$var = $this->pairs[$variableName->identifier] ?? null;
		return $var ? $var->typedValue->value : UnknownVariable::value;
	}

	/** @throws UnknownContextVariable */
	public function getVariable(VariableNameIdentifier $variableName): VariableValuePair {
		return $this->pairs[$variableName->identifier] ??
			UnknownContextVariable::withName($variableName);
	}

	/** @throws UnknownContextVariable */
	public function typedValueOf(VariableNameIdentifier $variableName): TypedValue {
		return $this->getVariable($variableName)->typedValue;
	}

	/** @throws UnknownContextVariable */
	public function valueOf(VariableNameIdentifier $variableName): Value {
		return $this->typedValueOf($variableName)->value;
	}

	/** @throws UnknownContextVariable */
	public function typeOf(VariableNameIdentifier $variableName): Type {
		return $this->typedValueOf($variableName)->type;
	}

	public static function empty(): VariableValueScope {
		return new self([]);
	}

	/**
	 * @param list<VariableValuePair> $pairs
	 * @return array<string, VariableValuePair>
	 */
	private static function pairsToKeyValueArray(array $pairs): array {
		return array_combine(
			array_map(static fn(VariableValuePair $pair) =>
				$pair->variableName->identifier, $pairs),
			$pairs
		);
	}

	public static function fromPairs(VariableValuePair ... $pairs): VariableValueScope {
		return new self(self::pairsToKeyValueArray($pairs));
	}

	public function withAddedValues(VariableValuePair ... $pairs): VariableValueScope {
		return new self([...$this->pairs, ...self::pairsToKeyValueArray($pairs)]);
	}

	public function withAddedVariablePairs(VariablePair ...$pairs): VariableScope {
		return VariableScope::fromPairs(... [
			...array_values(array_map(
				static fn(VariableValuePair $pair): VariablePair =>
					new VariablePair($pair->variableName, $pair->typedValue->type),
					$this->pairs
			)),
			... $pairs
		]);
	}
}