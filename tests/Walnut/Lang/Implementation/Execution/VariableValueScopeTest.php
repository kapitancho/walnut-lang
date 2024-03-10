<?php

namespace Walnut\Lang\Test\Implementation\Execution;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\UnknownContextVariable;
use Walnut\Lang\Blueprint\Execution\UnknownVariable;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;

final class VariableValueScopeTest extends TestCase {

	private readonly TypeRegistry $typeRegistry;
	private readonly ValueRegistry $valueRegistry;
	private readonly VariableValueScope $variableValueScope;

	public function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->valueRegistry = new ValueRegistry($this->typeRegistry);
		$this->variableValueScope = VariableValueScope::fromPairs(
			new VariableValuePair(
				new VariableNameIdentifier('x'),
				new TypedValue(
					$this->typeRegistry->integer(),
					$this->valueRegistry->integer(123)
				)
			)
		);
	}

	public function testEmptyScope(): void {
		self::assertEquals([], VariableValueScope::empty()->variables());
	}

	public function testVariables(): void {
		self::assertEquals(['x'], $this->variableValueScope->variables());
	}

	public function testFindVariable(): void {
		self::assertEquals(
			new VariableValuePair(
				new VariableNameIdentifier('x'),
				new TypedValue(
					$this->typeRegistry->integer(),
					$this->valueRegistry->integer(123)
				)
			),
			$this->variableValueScope->findVariable(new VariableNameIdentifier('x'))
		);
	}

	public function testFindVariableNotFound(): void {
		self::assertEquals(
			UnknownVariable::value,
			$this->variableValueScope->findVariable(new VariableNameIdentifier('y'))
		);
	}

	public function testTypeOf(): void {
		self::assertEquals(
			$this->typeRegistry->integer(),
			$this->variableValueScope->typeOf(new VariableNameIdentifier('x'))
		);
	}

	public function testValueOf(): void {
		self::assertEquals(
			$this->valueRegistry->integer(123),
			$this->variableValueScope->valueOf(new VariableNameIdentifier('x'))
		);
	}

	public function testTypeOfUnknown(): void {
		$this->expectException(UnknownContextVariable::class);
		$this->variableValueScope->typeOf(new VariableNameIdentifier('y'));
	}

	public function testWithAddedValues(): void {
		$variableValueScope = $this->variableValueScope->withAddedValues(
			new VariableValuePair(
				new VariableNameIdentifier('y'),
				new TypedValue(
					$this->typeRegistry->string(),
					$this->valueRegistry->string('abc')
				)
			)
		);
		self::assertEquals(['x', 'y'], $variableValueScope->variables());
		self::assertEquals(
			$this->typeRegistry->string(),
			$variableValueScope->typeOf(new VariableNameIdentifier('y'))
		);
		self::assertEquals(
			$this->valueRegistry->string('abc'),
			$variableValueScope->valueOf(new VariableNameIdentifier('y'))
		);
	}

	public function testWithAddedTypes(): void {
		$variableValueScope = $this->variableValueScope->withAddedVariablePairs(
			new VariablePair(
				new VariableNameIdentifier('y'),
				$this->typeRegistry->string(),
			)
		);
		self::assertEquals(['x', 'y'], $variableValueScope->variables());
		self::assertEquals(
			$this->typeRegistry->string(),
			$variableValueScope->typeOf(new VariableNameIdentifier('y'))
		);
	}

}