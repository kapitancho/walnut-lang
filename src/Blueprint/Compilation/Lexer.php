<?php

namespace Walnut\Lang\Blueprint\Compilation;

interface Lexer {
	public function tokensFromSource(string $sourceCode): array;
}