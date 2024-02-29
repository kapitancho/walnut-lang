<?php

namespace Walnut\Lang\Implementation\Execution;

use Walnut\Lang\Blueprint\Execution\UnknownContextVariable;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableScope as VariableScopeInterface;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Type\Type;

final readonly class VariableScope implements VariableScopeInterface {
	/** @var list<string> */
	private array $variables;

	/** @param array<string, VariablePair> $pairs */
	private function __construct(
		private array $pairs
	) {}

	/** @return string[] */
	public function variables(): array {
		return $this->variables ??= array_keys($this->pairs);
	}

	public function typeOf(VariableNameIdentifier $variableName): Type {
		$var = $this->pairs[$variableName->identifier] ??
			UnknownContextVariable::withName($variableName);
		return $var->variableType;
	}

	public static function empty(): VariableScope {
		return new self([]);
	}

	/**
	 * @param list<VariablePair> $pairs
	 * @return array<string, VariablePair>
	 */
	private static function pairsToKeyValueArray(array $pairs): array {
		return array_combine(
			array_map(static fn(VariablePair $pair) =>
				$pair->variableName->identifier, $pairs),
			$pairs
		);
	}

	public static function fromPairs(VariablePair ... $pairs): VariableScope {
		return new self(self::pairsToKeyValueArray($pairs));
	}

	public function withAddedVariablePairs(VariablePair ... $pairs): VariableScope {
		return new self([...$this->pairs, ...self::pairsToKeyValueArray($pairs)]);
	}
}