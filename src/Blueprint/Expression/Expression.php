<?php

namespace Walnut\Lang\Blueprint\Expression;

use Stringable;
use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\FlowShortcut;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;

interface Expression extends Stringable {
	/** @throws AnalyserException */
	public function analyse(VariableScope $variableScope): ExecutionResultContext;
	/** @throws FlowShortcut */
	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext;
}