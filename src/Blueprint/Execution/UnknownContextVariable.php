<?php

namespace Walnut\Lang\Blueprint\Execution;

use LogicException;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;

final class UnknownContextVariable extends LogicException {

	private function __construct(public VariableNameIdentifier $variableName) {
		parent::__construct((string)$this);
	}

	public static function withName(VariableNameIdentifier $variableName): never {
		throw new self($variableName);
	}

	public function __toString(): string {
		return sprintf("Unknown context variable '%s'", $this->variableName);
	}
}