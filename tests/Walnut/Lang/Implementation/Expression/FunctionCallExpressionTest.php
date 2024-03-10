<?php

namespace Walnut\Lang\Implementation\Expression;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Value\FunctionValue;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class FunctionCallExpressionTest extends BaseProgramTestHelper {

	private FunctionCallExpression $functionCallExpression;
	private FunctionValue $functionValue;

	protected function setUp(): void {
		parent::setUp();
		$this->functionCallExpression = $this->expressionRegistry->functionCall(
			$this->expressionRegistry->variableName(new VariableNameIdentifier('a')),
			$this->expressionRegistry->variableName(new VariableNameIdentifier('b')),
		);
		$this->typeRegistry->addState(
			new TypeNameIdentifier("MyCustomType"),
			$this->typeRegistry->integer()
		);
		$this->builder->methodBuilder()['addMethod'](
			$this->typeRegistry->withName(new TypeNameIdentifier('MyCustomType')),
			'invoke',
			$this->typeRegistry->integer(),
			null,
			$this->typeRegistry->string(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant(
					$this->valueRegistry->string("hi")
				)
			)
		);
		$this->typeRegistry->addSubtype(
			new TypeNameIdentifier("MyFunction"),
			$this->typeRegistry->function(
				$this->typeRegistry->integer(),
				$this->typeRegistry->string(),
			),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant(
					$this->valueRegistry->null()
				)
			),
			null,
		);
		$this->functionValue = $this->valueRegistry->function(
			$this->typeRegistry->integer(),
			$this->typeRegistry->string(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant(
					$this->valueRegistry->string("hi")
				)
			)
		);
	}
	
	public function testAnalyseDefault(): void {
		$result = $this->functionCallExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('a'),
				$this->typeRegistry->function(
					$this->typeRegistry->integer(),
					$this->typeRegistry->string(),
				),
			),
			new VariablePair(new VariableNameIdentifier('b'), $this->typeRegistry->integer())
		));
		self::assertEquals(
			$this->typeRegistry->string(),
			$result->expressionType
		);
	}

	public function testAnalyseOnSubtypes(): void {
		$result = $this->functionCallExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('a'),
				$this->typeRegistry->withName(new TypeNameIdentifier('MyFunction'))
			),
			new VariablePair(new VariableNameIdentifier('b'), $this->typeRegistry->integer())
		));
		self::assertEquals(
			$this->typeRegistry->string(),
			$result->expressionType
		);
	}

	public function testAnalyseOnCustomType(): void {
		$result = $this->functionCallExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('a'),
				$this->typeRegistry->withName(new TypeNameIdentifier('MyCustomType'))
			),
			new VariablePair(new VariableNameIdentifier('b'), $this->typeRegistry->integer())
		));
		self::assertTrue($result->expressionType->isSubtypeOf($this->typeRegistry->string()));
	}

	public function testAnalyseFailWrongParameter(): void {
		$this->expectException(AnalyserException::class);
		$this->functionCallExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('a'),
				$this->typeRegistry->function(
					$this->typeRegistry->integer(),
					$this->typeRegistry->string(),
				),
			),
			new VariablePair(new VariableNameIdentifier('b'), $this->typeRegistry->boolean())
		));
	}

	public function testAnalyseFailWrongType(): void {
		$this->expectException(AnalyserException::class);
		$this->functionCallExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('a'),
				$this->typeRegistry->integer()
			),
			new VariablePair(new VariableNameIdentifier('b'), $this->typeRegistry->integer())
		));
	}
	public function testExecuteDefault(): void {
		$result = $this->functionCallExpression->execute(VariableValueScope::fromPairs(
			new VariableValuePair(
				new VariableNameIdentifier('a'),
				TypedValue::forValue($this->functionValue)
			),
			new VariableValuePair(
				new VariableNameIdentifier('b'),
				TypedValue::forValue(
					$this->valueRegistry->integer(1)
				)
			)
		));
		self::assertTrue($result->value()->equals($this->valueRegistry->string("hi")));
	}

	public function testExecuteOnSubtypes(): void {
		$result = $this->functionCallExpression->execute(VariableValueScope::fromPairs(
			new VariableValuePair(
				new VariableNameIdentifier('a'),
				TypedValue::forValue(
					$this->valueRegistry->subtypeValue(
						new TypeNameIdentifier('MyFunction'),
						$this->functionValue
					)
				)
			),
			new VariableValuePair(
				new VariableNameIdentifier('b'),
				TypedValue::forValue(
					$this->valueRegistry->integer(1)
				)
			)
		));
		self::assertTrue($result->value()->equals($this->valueRegistry->string("hi")));
	}

	public function testExecuteOnCustomType(): void {
		$result = $this->functionCallExpression->execute(VariableValueScope::fromPairs(
			new VariableValuePair(
				new VariableNameIdentifier('a'),
				TypedValue::forValue(
					$this->valueRegistry->stateValue(
						new TypeNameIdentifier('MyCustomType'),
						$this->valueRegistry->integer(1)
					)
				)
			),
			new VariableValuePair(
				new VariableNameIdentifier('b'),
				TypedValue::forValue(
					$this->valueRegistry->integer(1)
				)
			)
		));
		self::assertTrue($result->value()->equals($this->valueRegistry->string("hi")));
	}

	public function testExecuteFailWrongType(): void {
		$this->expectException(ExecutionException::class);
		$this->functionCallExpression->execute(VariableValueScope::fromPairs(
			new VariableValuePair(
				new VariableNameIdentifier('a'),
				TypedValue::forValue(
					$this->valueRegistry->integer(1)
				)
			),
			new VariableValuePair(
				new VariableNameIdentifier('b'),
				TypedValue::forValue(
					$this->valueRegistry->integer(1)
				)
			)
		));
	}
}