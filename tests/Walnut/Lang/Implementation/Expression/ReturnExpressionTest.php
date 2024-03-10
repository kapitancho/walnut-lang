<?php

namespace Walnut\Lang\Test\Implementation\Expression;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Execution\ReturnResult;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Expression\ConstantExpression;
use Walnut\Lang\Implementation\Expression\ReturnExpression;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;

final class ReturnExpressionTest extends TestCase {
	private readonly TypeRegistry $typeRegistry;
	private readonly ValueRegistry $valueRegistry;
	private readonly ReturnExpression $returnExpression;

	protected function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->valueRegistry = new ValueRegistry($this->typeRegistry);
		$this->returnExpression = new ReturnExpression(
			$this->typeRegistry,
			new ConstantExpression(
				$this->typeRegistry,
				$this->valueRegistry->integer(123)
			),
		);
	}

	public function testReturnedExpression(): void {
		self::assertInstanceOf(ConstantExpression::class,
			$this->returnExpression->returnedExpression());
	}

	public function testAnalyse(): void {
		$result = $this->returnExpression->analyse(VariableScope::empty());
		self::assertTrue($result->returnType->isSubtypeOf(
			$this->typeRegistry->integer()
		));
	}

	public function testExecute(): void {
		$this->expectException(ReturnResult::class);
		$this->returnExpression->execute(VariableValueScope::empty());
	}

	public function testExecuteResult(): void {
		try {
			$this->returnExpression->execute(VariableValueScope::empty());
		} catch (ReturnResult $e) {
			self::assertEquals(
				$this->valueRegistry->integer(123),
				$e->value
			);
			return;
		}
	}

}