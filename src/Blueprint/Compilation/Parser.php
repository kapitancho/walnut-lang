<?php

namespace Walnut\Lang\Blueprint\Compilation;

interface Parser {
	public function programFromTokens(array $tokens): mixed;
}