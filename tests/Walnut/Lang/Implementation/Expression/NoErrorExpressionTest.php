<?php

namespace Walnut\Lang\Implementation\Expression;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Execution\ReturnResult;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;

final class NoErrorExpressionTest extends TestCase {
	private readonly TypeRegistry $typeRegistry;
	private readonly ValueRegistry $valueRegistry;
	private readonly NoErrorExpression $noErrorExpression;
	private readonly NoErrorExpression $errorExpression;

	protected function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->valueRegistry = new ValueRegistry($this->typeRegistry);
		$this->noErrorExpression = new NoErrorExpression(
			$this->typeRegistry,
			new ConstantExpression(
				$this->typeRegistry,
				$this->valueRegistry->integer(123)
			),
		);
		$this->errorExpression = new NoErrorExpression(
			$this->typeRegistry,
			new ConstantExpression(
				$this->typeRegistry,
				$this->valueRegistry->error(
					$this->valueRegistry->integer(123)
				)
			),
		);
	}

	public function testReturnedExpression(): void {
		self::assertInstanceOf(ConstantExpression::class,
			$this->noErrorExpression->targetExpression());
	}

	public function testAnalyse(): void {
		$result = $this->noErrorExpression->analyse(VariableScope::empty());
		self::assertTrue($result->returnType->isSubtypeOf(
			$this->typeRegistry->integer()
		));
	}

	public function testExecute(): void {
		$this->expectNotToPerformAssertions();
		$this->noErrorExpression->execute(VariableValueScope::empty());
	}

	public function testExecuteOnError(): void {
		$this->expectException(ReturnResult::class);
		$this->errorExpression->execute(VariableValueScope::empty());
	}

	public function testExecuteResult(): void {
		try {
			$this->errorExpression->execute(VariableValueScope::empty());
		} catch (ReturnResult $e) {
			self::assertEquals(
				$this->valueRegistry->error(
					$this->valueRegistry->integer(123)
				),
				$e->value
			);
			return;
		}
	}

}