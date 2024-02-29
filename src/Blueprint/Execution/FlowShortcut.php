<?php

namespace Walnut\Lang\Blueprint\Execution;

use RuntimeException;
use Walnut\Lang\Blueprint\Value\Value;

class FlowShortcut extends RuntimeException {
	public function __construct(public readonly Value $value) {
		parent::__construct();
	}
}
