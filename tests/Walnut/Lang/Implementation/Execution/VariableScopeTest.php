<?php

namespace Walnut\Lang\Test\Implementation\Execution;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Execution\UnknownContextVariable;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Registry\TypeRegistry;

final class VariableScopeTest extends TestCase {

	private readonly TypeRegistry $typeRegistry;
	private readonly VariableScope $variableScope;

	public function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->variableScope = VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('x'),
				$this->typeRegistry->integer()
			)
		);
	}

	public function testEmptyScope(): void {
		self::assertEquals([], VariableScope::empty()->variables());
	}

	public function testVariables(): void {
		self::assertEquals(['x'], $this->variableScope->variables());
	}

	public function testTypeOf(): void {
		self::assertEquals($this->typeRegistry->integer(),
			$this->variableScope->typeOf(new VariableNameIdentifier('x'))
		);
	}

	public function testTypeOfUnknown(): void {
		$this->expectException(UnknownContextVariable::class);
		$this->variableScope->typeOf(new VariableNameIdentifier('y'));
	}

	public function testWithAddedVariablePairs(): void {
		$variableScope = $this->variableScope->withAddedVariablePairs(
			new VariablePair(
				new VariableNameIdentifier('y'),
				$this->typeRegistry->string()
			)
		);
		self::assertEquals(['x', 'y'], $variableScope->variables());
		self::assertEquals($this->typeRegistry->string(),
			$variableScope->typeOf(new VariableNameIdentifier('y'))
		);
	}

}