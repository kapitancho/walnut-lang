<?php

namespace Walnut\Lang\Blueprint\Execution;

use Walnut\Lang\Blueprint\Type\Type;

final readonly class ExecutionResultContext {
	public function __construct(
		public Type          $expressionType,
		public Type          $returnType,
		public VariableScope $variableScope
	) {}

	public function withExpressionType(Type $expressionType): self {
		return new self(
			$expressionType,
			$this->returnType,
			$this->variableScope,
		);
	}

	public function withReturnType(Type $returnType): self {
		return new self(
			$this->expressionType,
			$returnType,
			$this->variableScope,
		);
	}

	public function withVariableScope(VariableScope $variableScope): self {
		return new self(
			$this->expressionType,
			$this->returnType,
			$variableScope,
		);
	}
}