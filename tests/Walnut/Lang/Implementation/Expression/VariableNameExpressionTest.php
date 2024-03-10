<?php

namespace Walnut\Lang\Test\Implementation\Expression;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Expression\VariableNameExpression;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;

final class VariableNameExpressionTest extends TestCase {
	private readonly TypeRegistry $typeRegistry;
	private readonly ValueRegistry $valueRegistry;
	private readonly VariableNameExpression $variableNameExpression;

	protected function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->valueRegistry = new ValueRegistry($this->typeRegistry);
		$this->variableNameExpression = new VariableNameExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			new VariableNameIdentifier('x')
		);
	}

	public function testVariableName(): void {
		self::assertEquals('x',
			$this->variableNameExpression->variableName()->identifier);
	}

	public function testAnalyse(): void {
		$result = $this->variableNameExpression->analyse(
			VariableScope::fromPairs(
				new VariablePair(
					new VariableNameIdentifier('x'),
					$this->typeRegistry->integer()
				)
			)
		);
		self::assertEquals($this->typeRegistry->integer(), $result->expressionType);
	}

	public function testExecute(): void {
		$result = $this->variableNameExpression->execute(
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