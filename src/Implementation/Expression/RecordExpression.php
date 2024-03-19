<?php

namespace Walnut\Lang\Implementation\Expression;

use JsonSerializable;
use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\RecordExpression as RecordExpressionInterface;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Registry\ValueRegistry;

final readonly class RecordExpression implements RecordExpressionInterface, JsonSerializable {

	/** @param array<string, Expression> $values */
	public function __construct(
		private TypeRegistry $typeRegistry,
		private ValueRegistry $valueRegistry,
		private array $values
	) {}

	/** @return array<string, Expression> */
	public function values(): array {
		return $this->values;
	}

	public function analyse(VariableScope $variableScope): ExecutionResultContext {
		$subtypes = [];
		$returnTypes = [];
		foreach($this->values as $key => $value) {
			$subRet = $value->analyse($variableScope);

			$subtypes[$key] = $subRet->expressionType;
			$variableScope = $subRet->variableScope;
			$returnTypes[] = $subRet->returnType;
		}
		return new ExecutionResultContext(
			$this->typeRegistry->record($subtypes),
			$this->typeRegistry->union($returnTypes),
			$variableScope
		);
	}

	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext {
		$values = [];
		$types = [];
		foreach($this->values as $key => $value) {
			$subRet = $value->execute($variableValueScope);

			$values[$key] = $subRet->value();
			$types[$key] = $subRet->valueType();
			$variableValueScope = $subRet->variableValueScope;
		}
		return new ExecutionResultValueContext(
			new TypedValue(
				$this->typeRegistry->record($types),
				$this->valueRegistry->dict($values)
			),
			$variableValueScope
		);
	}

	public function __toString(): string {
		$values = [];
		foreach($this->values as $key => $type) {
			$values[] = "$key: $type";
		}
		return count($values) ? sprintf(
			"[%s]",
			implode(", ", $values)
		) : '[:]';
	}

	public function jsonSerialize(): array {
		return [
			'expressionType' => 'Record',
			'values' => $this->values
		];
	}
}