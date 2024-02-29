<?php

namespace Walnut\Lang\Blueprint\Compilation;

final readonly class Source {
	public function __construct(
		public string $sourceRoot,
		public string $startModuleName
	) {}
}