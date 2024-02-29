<?php

namespace Walnut\Lang\Blueprint\Function;

use Stringable;
use Walnut\Lang\Blueprint\Expression\Expression;

interface FunctionBody extends Stringable {
	public function expression(): Expression;
}