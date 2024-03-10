<?php

namespace Walnut\Lang\Test\Implementation\Expression;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Expression\ConstantExpression;
use Walnut\Lang\Implementation\Expression\TupleExpression;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;

final class TupleExpressionTest extends TestCase {
	private readonly TypeRegistry $typeRegistry;
	private readonly ValueRegistry $valueRegistry;
	private readonly TupleExpression $tupleExpression;

	protected function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->valueRegistry = new ValueRegistry($this->typeRegistry);
		$this->tupleExpression = new TupleExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			[
				new ConstantExpression(
					$this->typeRegistry,
					$this->valueRegistry->integer(123)
				),
				new ConstantExpression(
					$this->typeRegistry,
					$this->valueRegistry->string("456")
				)
			]
		);
	}

	public function testValues(): void {
		self::assertCount(2, $this->tupleExpression->values());
	}

	public function testAnalyse(): void {
		$result = $this->tupleExpression->analyse(VariableScope::empty());
		self::assertTrue($result->expressionType->isSubtypeOf(
			$this->typeRegistry->tuple([
				$this->typeRegistry->integer(),
				$this->typeRegistry->string()
			])
		));
	}

	public function testExecute(): void {
		$result = $this->tupleExpression->execute(VariableValueScope::empty());
		self::assertEquals(
			$this->valueRegistry->list([
				$this->valueRegistry->integer(123),
				$this->valueRegistry->string("456")
			]),
			$result->value()
		);
	}

}