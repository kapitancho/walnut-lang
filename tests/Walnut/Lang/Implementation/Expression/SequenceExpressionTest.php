<?php

namespace Walnut\Lang\Test\Implementation\Expression;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Expression\ConstantExpression;
use Walnut\Lang\Implementation\Expression\ReturnExpression;
use Walnut\Lang\Implementation\Expression\SequenceExpression;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;

final class SequenceExpressionTest extends TestCase {
	private readonly TypeRegistry $typeRegistry;
	private readonly ValueRegistry $valueRegistry;
	private readonly SequenceExpression $sequenceExpression;

	protected function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->valueRegistry = new ValueRegistry($this->typeRegistry);
		$this->sequenceExpression = new SequenceExpression(
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
		self::assertCount(2, $this->sequenceExpression->expressions());
	}

	public function testAnalyse(): void {
		$result = $this->sequenceExpression->analyse(VariableScope::empty());
		self::assertTrue($result->expressionType->isSubtypeOf(
			$this->typeRegistry->string()
		));
	}

	public function testExecute(): void {
		$result = $this->sequenceExpression->execute(VariableValueScope::empty());
		self::assertEquals(
			$this->valueRegistry->string("456"),
			$result->value()
		);
	}

	public function testAnalyseWithReturn(): void {
		$result = (new SequenceExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			[
				new ReturnExpression(
					$this->typeRegistry,
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->integer(123)
					)
				),
				new ConstantExpression(
					$this->typeRegistry,
					$this->valueRegistry->string("456")
				)
			]
		))->analyse(VariableScope::empty());
		self::assertTrue($result->expressionType->isSubtypeOf(
			$this->typeRegistry->integer()
		));
	}

}