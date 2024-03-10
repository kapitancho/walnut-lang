<?php

namespace Walnut\Lang\Test\Implementation\Expression;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Expression\ConstantExpression;
use Walnut\Lang\Implementation\Expression\VariableAssignmentExpression;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;

final class VariableAssignmentExpressionTest extends TestCase {
	private readonly TypeRegistry $typeRegistry;
	private readonly ValueRegistry $valueRegistry;
	private readonly VariableAssignmentExpression $variableAssignmentExpression;

	protected function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->valueRegistry = new ValueRegistry($this->typeRegistry);
		$this->variableAssignmentExpression = 
			new VariableAssignmentExpression(
				$this->typeRegistry,
				new VariableNameIdentifier('x'),
				new ConstantExpression(
					$this->typeRegistry,
					$this->valueRegistry->integer(123)
				)
			);
	}

	public function testVariableName(): void {
		self::assertEquals('x',
			$this->variableAssignmentExpression->variableName()->identifier);
	}

	public function testAssignedExpression(): void {
		self::assertInstanceOf(ConstantExpression::class,
			$this->variableAssignmentExpression->assignedExpression());
		self::assertEquals(123,
			$this->variableAssignmentExpression->assignedExpression()->value()->literalValue());
	}

	public function testAnalyse(): void {
		$result = $this->variableAssignmentExpression->analyse(
			VariableScope::fromPairs(
				new VariablePair(
					new VariableNameIdentifier('x'),
					$this->typeRegistry->integer()
				)
			)
		);
		self::assertTrue($result->expressionType->isSubtypeOf($this->typeRegistry->integer()));
		self::assertTrue(
			$result->variableScope->typeOf(
				new VariableNameIdentifier('x')
			)->isSubtypeOf($this->typeRegistry->integer())
		);
	}

	public function testExecute(): void {
		$result = $this->variableAssignmentExpression->execute(
			VariableValueScope::fromPairs(
				new VariableValuePair(
					new VariableNameIdentifier('x'),
					new TypedValue(
						$this->typeRegistry->integer(),
						$this->valueRegistry->integer(123)
					)
				)
			)
		);
		self::assertEquals($this->valueRegistry->integer(123), $result->value());
	}

}