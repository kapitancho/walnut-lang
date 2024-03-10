<?php

namespace Walnut\Lang\Implementation\Expression;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\ReturnResult;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Expression\ConstantExpression;
use Walnut\Lang\Implementation\Expression\ReturnExpression;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class PropertyAccessExpressionTest extends BaseProgramTestHelper {

	private PropertyAccessExpression $recordPropertyAccessExpression;
	private PropertyAccessExpression $tuplePropertyAccessExpression;

	protected function setUp(): void {
		parent::setUp();
		$this->recordPropertyAccessExpression = $this->expressionRegistry->propertyAccess(
			$this->expressionRegistry->variableName(new VariableNameIdentifier('#')),
			new PropertyNameIdentifier('y')
		);
		$this->tuplePropertyAccessExpression = $this->expressionRegistry->propertyAccess(
			$this->expressionRegistry->variableName(new VariableNameIdentifier('#')),
			new PropertyNameIdentifier('1')
		);
		$this->typeRegistry->addSubtype(
			new TypeNameIdentifier("MyRecord"),
			$this->typeRegistry->record([
				'x' => $this->typeRegistry->integer(),
				'y' => $this->typeRegistry->string(),
			]),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant(
					$this->valueRegistry->null()
				)
			),
			null,
		);
		$this->typeRegistry->addSubtype(
			new TypeNameIdentifier("MyTuple"),
			$this->typeRegistry->tuple([
				$this->typeRegistry->integer(),
				$this->typeRegistry->string(),
			]),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant(
					$this->valueRegistry->null()
				)
			),
			null,
		);
	}
	
	public function testAnalyseDefault(): void {
		$result = $this->recordPropertyAccessExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('#'),
				$this->typeRegistry->record([
					'x' => $this->typeRegistry->integer(),
					'y' => $this->typeRegistry->string(),
				])
			)
		));
		self::assertEquals(
			$this->typeRegistry->string(),
			$result->expressionType
		);

		$result = $this->tuplePropertyAccessExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('#'),
				$this->typeRegistry->tuple([
					$this->typeRegistry->integer(),
					$this->typeRegistry->string(),
				])
			)
		));
		self::assertEquals(
			$this->typeRegistry->string(),
			$result->expressionType
		);
	}

	public function testAnalyseOnSubtypes(): void {
		$result = $this->recordPropertyAccessExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('#'),
				$this->typeRegistry->withName(new TypeNameIdentifier('MyRecord'))
			)
		));
		self::assertEquals(
			$this->typeRegistry->string(),
			$result->expressionType
		);

		$result = $this->tuplePropertyAccessExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('#'),
				$this->typeRegistry->withName(new TypeNameIdentifier('MyTuple'))
			)
		));
		self::assertEquals(
			$this->typeRegistry->string(),
			$result->expressionType
		);
	}

	public function testAnalyseFailWrongRecordProperty(): void {
		$this->expectException(AnalyserException::class);
		$this->recordPropertyAccessExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('#'),
				$this->typeRegistry->record([
					'a' => $this->typeRegistry->integer(),
					'b' => $this->typeRegistry->string(),
				])
			)
		));
	}

	public function testAnalyseFailWrongTupleProperty(): void {
		$this->expectException(AnalyserException::class);
		$this->tuplePropertyAccessExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('#'),
				$this->typeRegistry->tuple([
					$this->typeRegistry->integer(),
				])
			)
		));
	}

	public function testAnalyseFailStringPropertyOnTuple(): void {
		$this->expectException(AnalyserException::class);
		$this->recordPropertyAccessExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('#'),
				$this->typeRegistry->tuple([
					$this->typeRegistry->integer(),
					$this->typeRegistry->string(),
				])
			)
		));
	}

	public function testAnalyseFailWrongType(): void {
		$this->expectException(AnalyserException::class);
		$this->recordPropertyAccessExpression->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('#'),
				$this->typeRegistry->integer()
			)
		));
	}

	public function testExecuteDefault(): void {
		$result = $this->recordPropertyAccessExpression->execute(VariableValueScope::fromPairs(
			new VariableValuePair(
				new VariableNameIdentifier('#'),
				TypedValue::forValue(
					$this->valueRegistry->dict([
						'x' => $this->valueRegistry->integer(1),
						'y' => $this->valueRegistry->string("hi"),
					])
				)
			)
		));
		self::assertTrue($result->value()->equals($this->valueRegistry->string("hi")));

		$result = $this->tuplePropertyAccessExpression->execute(VariableValueScope::fromPairs(
			new VariableValuePair(
				new VariableNameIdentifier('#'),
				TypedValue::forValue(
					$this->valueRegistry->list([
						$this->valueRegistry->integer(1),
						$this->valueRegistry->string("hi"),
					])
				)
			)
		));
		self::assertTrue($result->value()->equals($this->valueRegistry->string("hi")));
	}

	public function testExecuteOnSubtypes(): void {
		$result = $this->recordPropertyAccessExpression->execute(VariableValueScope::fromPairs(
			new VariableValuePair(
				new VariableNameIdentifier('#'),
				TypedValue::forValue(
					$this->valueRegistry->subtypeValue(
						new TypeNameIdentifier('MyRecord'),
						$this->valueRegistry->dict([
							'x' => $this->valueRegistry->integer(1),
							'y' => $this->valueRegistry->string("hi"),
						])
					)
				)
			)
		));
		self::assertTrue($result->value()->equals($this->valueRegistry->string("hi")));

		$result = $this->tuplePropertyAccessExpression->execute(VariableValueScope::fromPairs(
			new VariableValuePair(
				new VariableNameIdentifier('#'),
				TypedValue::forValue(
					$this->valueRegistry->subtypeValue(
						new TypeNameIdentifier('MyTuple'),
						$this->valueRegistry->list([
							$this->valueRegistry->integer(1),
							$this->valueRegistry->string("hi"),
						])
					)
				)
			)
		));
		self::assertTrue($result->value()->equals($this->valueRegistry->string("hi")));
	}

	public function testExecuteFailWrongType(): void {
		$this->expectException(ExecutionException::class);
		$this->recordPropertyAccessExpression->execute(VariableValueScope::fromPairs(
			new VariableValuePair(
				new VariableNameIdentifier('#'),
				TypedValue::forValue(
					$this->valueRegistry->integer(1)
				)
			)
		));
	}

}