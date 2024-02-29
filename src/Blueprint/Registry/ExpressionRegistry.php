<?php

namespace Walnut\Lang\Blueprint\Registry;

use Walnut\Lang\Blueprint\Expression\ConstantExpression;
use Walnut\Lang\Blueprint\Expression\ConstructorCallExpression;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\FunctionCallExpression;
use Walnut\Lang\Blueprint\Expression\MatchExpression;
use Walnut\Lang\Blueprint\Expression\MatchExpressionDefault;
use Walnut\Lang\Blueprint\Expression\MatchExpressionOperation;
use Walnut\Lang\Blueprint\Expression\MatchExpressionPair;
use Walnut\Lang\Blueprint\Expression\MethodCallExpression;
use Walnut\Lang\Blueprint\Expression\NoErrorExpression;
use Walnut\Lang\Blueprint\Expression\PropertyAccessExpression;
use Walnut\Lang\Blueprint\Expression\RecordExpression;
use Walnut\Lang\Blueprint\Expression\ReturnExpression;
use Walnut\Lang\Blueprint\Expression\SequenceExpression;
use Walnut\Lang\Blueprint\Expression\TupleExpression;
use Walnut\Lang\Blueprint\Expression\VariableAssignmentExpression;
use Walnut\Lang\Blueprint\Expression\VariableNameExpression;
use Walnut\Lang\Blueprint\Function\FunctionBody;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Value\Value;

interface ExpressionRegistry {
	public function constant(Value $value): ConstantExpression;

	/** @param list<Expression> $values */
	public function tuple(array $values): TupleExpression;
	/** @param array<string, Expression> $values */
	public function record(array $values): RecordExpression;

	/** @param list<Expression> $values */
	public function sequence(array $values): SequenceExpression;

	public function return(Expression $returnedExpression): ReturnExpression;
	public function noError(Expression $targetExpression): NoErrorExpression;
	public function variableName(VariableNameIdentifier $variableName): VariableNameExpression;
	public function variableAssignment(
		VariableNameIdentifier $variableName,
		Expression $assignedExpression
	): VariableAssignmentExpression;

	/** @param list<MatchExpressionPair|MatchExpressionDefault> $pairs */
	public function match(
		Expression $target,
		MatchExpressionOperation $operation,
		array $pairs
	): MatchExpression;

	public function functionCall(
		Expression $target,
		Expression $parameter
	): FunctionCallExpression;

	public function methodCall(
		Expression $target,
		MethodNameIdentifier $methodName,
		Expression $parameter
	): MethodCallExpression;

	public function constructorCall(
		TypeNameIdentifier $typeName,
		Expression $parameter
	): ConstructorCallExpression;

	public function propertyAccess(
		Expression $target,
		PropertyNameIdentifier $propertyName
	): PropertyAccessExpression;

	public function functionBody(Expression $expression): FunctionBody;

}