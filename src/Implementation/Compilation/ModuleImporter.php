<?php

namespace Walnut\Lang\Implementation\Compilation;

use Walnut\Lang\Blueprint\Compilation\ModuleImporter as ModuleImporterInterface;
use Walnut\Lang\Blueprint\Registry\ProgramBuilder;

final class ModuleImporter implements ModuleImporterInterface {

	/** @var array<string, bool> */
	private array $cache = [];

	public function __construct(
		private readonly string $sourceRoot,
		private readonly ProgramBuilder $programBuilder,
		private readonly TransitionLogger $transitionLogger,
	) {}

	public function importModule(string $moduleName): void {
		$c = $this->cache[$moduleName] ?? null;
		if ($c === false) {
			die('TODO: handle import loop: ' . $moduleName);
		}
		if ($c) {
			return;
		}
		$this->cache[$moduleName] = false;
		$parser = new Parser(
			$this->programBuilder,
			$this->transitionLogger,
			$this
		);
		$lexer = new WalexLexerAdapter();
		$sourceCode = file_get_contents("$this->sourceRoot/$moduleName.nut");
		$tokens = $lexer->tokensFromSource($sourceCode);
		$parser->programFromTokens($tokens);
		$this->cache[$moduleName] = true;
	}
}