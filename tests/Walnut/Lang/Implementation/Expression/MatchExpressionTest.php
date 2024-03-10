<?php

namespace Walnut\Lang\Test\Implementation\Expression;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Expression\MatchExpressionDefault;
use Walnut\Lang\Blueprint\Expression\MatchExpressionPair;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Expression\ConstantExpression;
use Walnut\Lang\Implementation\Expression\MatchExpression;
use Walnut\Lang\Implementation\Expression\MatchExpressionEquals;
use Walnut\Lang\Implementation\Expression\MatchExpressionIsSubtypeOf;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;

final class MatchExpressionTest extends TestCase {
	private readonly TypeRegistry $typeRegistry;
	private readonly ValueRegistry $valueRegistry;
	private readonly MatchExpression $matchExpression;

	protected function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->valueRegistry = new ValueRegistry($this->typeRegistry);
		$this->matchExpression = new MatchExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			new ConstantExpression(
				$this->typeRegistry,
				$this->valueRegistry->integer(123)
			),
			new MatchExpressionEquals(),
			[
				new MatchExpressionPair(
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->string("123")
					),
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->string("456")
					)
				),
				new MatchExpressionPair(
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->integer(123)
					),
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->true()
					)
				),
			]
		);
	}

	public function testTarget(): void {
		self::assertInstanceOf(
			ConstantExpression::class,
			$this->matchExpression->target()
		);
	}

	public function testPairs(): void {
		self::assertCount(2, $this->matchExpression->pairs());
	}

	public function testOperation(): void {
		self::assertInstanceOf(
			MatchExpressionEquals::class,
			$this->matchExpression->operation()
		);
	}

	public function testAnalyse(): void {
		$result = $this->matchExpression->analyse(VariableScope::empty());
		self::assertTrue($result->expressionType->isSubtypeOf(
			$this->typeRegistry->union([
				$this->typeRegistry->string(),
				$this->typeRegistry->true()
			]),
		));
	}

	public function testExecute(): void {
		$result = $this->matchExpression->execute(VariableValueScope::empty());
		self::assertTrue(
			$this->valueRegistry->true()->equals($result->value())
		);
	}

	public function testIsSubtypeOf(): void {
		$result = (new MatchExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			new ConstantExpression(
				$this->typeRegistry,
				$this->valueRegistry->integer(123)
			),
			new MatchExpressionIsSubtypeOf(),
			[
				new MatchExpressionPair(
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->type(
							$this->typeRegistry->string()
						)
					),
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->string("456")
					)
				),
				new MatchExpressionPair(
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->type(
							$this->typeRegistry->integer()
						)
					),
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->true()
					)
				),
			]
		))->execute(VariableValueScope::empty());
		self::assertTrue($result->value()->equals(
			$this->valueRegistry->true()
		));
	}

	public function testDefaultMatch(): void {
		$result = (new MatchExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			new ConstantExpression(
				$this->typeRegistry,
				$this->valueRegistry->integer(123)
			),
			new MatchExpressionEquals(),
			[
				new MatchExpressionPair(
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->string("123")
					),
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->string("456")
					)
				),
				new MatchExpressionDefault(
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->true()
					)
				),
			],
		))->execute(VariableValueScope::empty());
		self::assertTrue($result->value()->equals(
			$this->valueRegistry->true()
		));
	}

	public function testNoMatch(): void {
		$result = (new MatchExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			new ConstantExpression(
				$this->typeRegistry,
				$this->valueRegistry->integer(123)
			),
			new MatchExpressionEquals(),
			[
				new MatchExpressionPair(
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->string("123")
					),
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->string("456")
					)
				),
				new MatchExpressionPair(
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->integer(456)
					),
					new ConstantExpression(
						$this->typeRegistry,
						$this->valueRegistry->true()
					)
				),
			],
		))->execute(VariableValueScope::empty());
		self::assertTrue($result->value()->equals(
			$this->valueRegistry->null()
		));
	}

}