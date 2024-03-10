<?php

namespace Walnut\Lang\Implementation\Registry;

use LogicException;
use Walnut\Lang\Blueprint\Execution\Program;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\ConstantExpression;
use Walnut\Lang\Blueprint\Expression\ConstructorCallExpression;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\FunctionCallExpression;
use Walnut\Lang\Blueprint\Expression\MatchExpression;
use Walnut\Lang\Blueprint\Expression\MatchExpressionDefault;
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
use Walnut\Lang\Blueprint\Function\CustomMethod;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Range\MinusInfinity;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Registry\DependencyContainer;
use Walnut\Lang\Blueprint\Registry\ExpressionRegistry;
use Walnut\Lang\Blueprint\Registry\ProgramBuilder as ProgramBuilderInterface;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Registry\TypeRegistryBuilder;
use Walnut\Lang\Blueprint\Registry\UnresolvableDependency;
use Walnut\Lang\Blueprint\Registry\ValueRegistry;
use Walnut\Lang\Blueprint\Registry\ValueRegistryBuilder;
use Walnut\Lang\Blueprint\Type\AliasType;
use Walnut\Lang\Blueprint\Type\AnyType;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\AtomType;
use Walnut\Lang\Blueprint\Type\BooleanType;
use Walnut\Lang\Blueprint\Type\EnumerationSubsetType;
use Walnut\Lang\Blueprint\Type\EnumerationType;
use Walnut\Lang\Blueprint\Type\FalseType;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\IntersectionType;
use Walnut\Lang\Blueprint\Type\MapType;
use Walnut\Lang\Blueprint\Type\MutableType;
use Walnut\Lang\Blueprint\Type\NamedType;
use Walnut\Lang\Blueprint\Type\NothingType;
use Walnut\Lang\Blueprint\Type\NullType;
use Walnut\Lang\Blueprint\Type\ProxyNamedType;
use Walnut\Lang\Blueprint\Type\RealSubsetType;
use Walnut\Lang\Blueprint\Type\RealType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\StateType;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\SubtypeType;
use Walnut\Lang\Blueprint\Type\TrueType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Type\UnionType;
use Walnut\Lang\Blueprint\Value\AtomValue;
use Walnut\Lang\Blueprint\Value\BooleanValue;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\EnumerationValue;
use Walnut\Lang\Blueprint\Value\ErrorValue;
use Walnut\Lang\Blueprint\Value\FunctionValue;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\MutableValue;
use Walnut\Lang\Blueprint\Value\NullValue;
use Walnut\Lang\Blueprint\Value\RealValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Expression\MatchExpressionEquals;
use Walnut\Lang\Implementation\Expression\MatchExpressionIsSubtypeOf;
use Walnut\Lang\Implementation\Function\FunctionBody;

final readonly class ProgramBuilder implements ProgramBuilderInterface, Program {

	public function __construct(
		private TypeRegistryBuilder $typeRegistryBuilder,
		private TypeRegistry $typeRegistry,
		private ValueRegistryBuilder $valueRegistryBuilder,
		private ValueRegistry $valueRegistry,
		private ExpressionRegistry $expressionRegistry,
		private CustomMethodRegistryBuilder $customMethodRegistryBuilder,
		private DependencyContainer $dependencyContainer
	) {}

	/** @inheritDoc */
	public function expressionRegistry(): array {
		return [
			'constant' => fn(Value $value): ConstantExpression => $this->expressionRegistry->constant($value),
			'tuple' => fn(array $values): TupleExpression => $this->expressionRegistry->tuple($values),
			'record' => fn(array $values): RecordExpression => $this->expressionRegistry->record($values),
			'sequence' => fn(array $values): SequenceExpression => $this->expressionRegistry->sequence($values),
			'return' => fn(Expression $expression): ReturnExpression => $this->expressionRegistry->return($expression),
			'noError' => fn(Expression $expression): NoErrorExpression => $this->expressionRegistry->noError($expression),
			'var' => fn(string $variableName): VariableNameExpression =>
				$this->expressionRegistry->variableName(new VariableNameIdentifier($variableName)),
			'assign' => fn(string $variableName, Expression $value): VariableAssignmentExpression =>
				$this->expressionRegistry->variableAssignment(new VariableNameIdentifier($variableName), $value),
			'matchValue' => fn(Expression $condition, array $pairs): MatchExpression =>
				$this->expressionRegistry->match($condition, new MatchExpressionEquals, $pairs),
			'matchTrue' => fn(array $pairs): MatchExpression =>
				$this->expressionRegistry->match(
					$this->expressionRegistry->constant($this->valueRegistry->true()),
					new MatchExpressionEquals, array_map(
						fn(MatchExpressionPair|MatchExpressionDefault $pair): MatchExpressionPair|MatchExpressionDefault => match(true) {
							$pair instanceof MatchExpressionPair => new MatchExpressionPair(
								$this->expressionRegistry->methodCall(
									$pair->matchExpression,
									new MethodNameIdentifier('asBoolean'),
									$this->expressionRegistry->constant(
										$this->valueRegistry->null()
									)
								),
								$pair->valueExpression
							),
							$pair instanceof MatchExpressionDefault => $pair
						},
						$pairs
					)
				),
			'matchType' => fn(Expression $condition, array $pairs): MatchExpression =>
				$this->expressionRegistry->match($condition, new MatchExpressionIsSubtypeOf, $pairs),
			'matchIf' => fn(Expression $condition, Expression $then, Expression $else): MatchExpression =>
				$this->expressionRegistry->match($condition, new MatchExpressionEquals, [
					new MatchExpressionPair(
						$this->expressionRegistry->constant($this->valueRegistry->true()), $then),
					new MatchExpressionDefault($else)
				]),
			'matchPair' => fn(Expression $match, Expression $condition): MatchExpressionPair =>
				new MatchExpressionPair($match, $condition),
			'matchDefault' => fn(Expression $condition): MatchExpressionDefault =>
				new MatchExpressionDefault($condition),
			'call' => fn(Expression $target, Expression $parameter = null): FunctionCallExpression =>
				$this->expressionRegistry->functionCall($target, $parameter ??
					$this->expressionRegistry->constant($this->valueRegistry->null())),
			'method' => fn(Expression $target, string $methodName, Expression $parameter = null): MethodCallExpression =>
				$this->expressionRegistry->methodCall(
					$target,
					new MethodNameIdentifier($methodName),
					$parameter ?? $this->expressionRegistry->constant($this->valueRegistry->null())
				),
			'constructor' => fn(string $type, Expression $parameter = null): ConstructorCallExpression =>
				$this->expressionRegistry->constructorCall(new TypeNameIdentifier($type),
					$parameter ?? $this->expressionRegistry->constant($this->valueRegistry->null())),
			'property' => fn(Expression $target, string $propertyName): PropertyAccessExpression =>
				$this->expressionRegistry->propertyAccess($target, new PropertyNameIdentifier($propertyName)),
			'functionBody' => fn(Expression $expression): FunctionBody => $this->expressionRegistry->functionBody($expression),
		];
	}

	/** @inheritDoc */
	public function valueBuilder(): array {
		return [
			'addVariable' => fn(string $variableName, Value $value): VariableValuePair =>
				$this->valueRegistryBuilder->addVariable(
					new VariableNameIdentifier($variableName),
					$value
				),
		];
	}

	/** @inheritDoc */
	public function typeBuilder(): array {
		return [
			'addAtom' => fn(string $typeName): AtomType =>
				$this->typeRegistryBuilder->addAtom(new TypeNameIdentifier($typeName)),
			'addEnumeration' => fn(string $typeName, array $values): EnumerationType =>
				$this->typeRegistryBuilder->addEnumeration(
					new TypeNameIdentifier($typeName),
					array_map(static fn(string $value): EnumValueIdentifier
						=> new EnumValueIdentifier($value), $values
					)
				),
			'addAlias' => fn(string $typeName, Type $aliasedType): AliasType =>
				$this->typeRegistryBuilder->addAlias(
					new TypeNameIdentifier($typeName), $aliasedType
				),
			'addSubtype' => fn(string $typeName, Type $baseType, FunctionBody $constructorBody, Type|null $errorType): Type =>
				$this->typeRegistryBuilder->addSubtype(
					new TypeNameIdentifier($typeName),
					$baseType,
					$constructorBody,
					$errorType
				),
			'addState' => fn(string $typeName, Type $stateType): Type =>
				$this->typeRegistryBuilder->addState(
					new TypeNameIdentifier($typeName),
					$stateType
				),
		];
	}

	/** @inheritDoc */
	public function typeRegistry(): array {
		return [
			'Atom' => fn(string $typeName): AtomType =>
				$this->typeRegistry->atom(new TypeNameIdentifier($typeName)),
			'Enumeration' => fn(string $typeName): EnumerationType =>
				$this->typeRegistry->enumeration(new TypeNameIdentifier($typeName)),
			'Alias' => fn(string $typeName): AliasType =>
				$this->typeRegistry->alias(new TypeNameIdentifier($typeName)),
			'Subtype' => fn(string $typeName): SubtypeType =>
				$this->typeRegistry->subtype(new TypeNameIdentifier($typeName)),
			'State' => fn(string $typeName): StateType =>
				$this->typeRegistry->state(new TypeNameIdentifier($typeName)),
			'NamedType' => fn(string $typeName): NamedType =>
				$this->typeRegistry->withName(new TypeNameIdentifier($typeName)),
			'ProxyType' => fn(string $typeName): ProxyNamedType =>
				$this->typeRegistry->proxyType(new TypeNameIdentifier($typeName)),

			'TypeByName' => fn(string $typeName): Type =>
				match($typeName) {
					'Any' => $this->typeRegistry->any(),
					'Nothing' => $this->typeRegistry->nothing(),
					'Array' => $this->typeRegistry->array(),
					'Map' => $this->typeRegistry->map(),
					'Mutable' => $this->typeRegistry->mutable($this->typeRegistry->any()),
					'Type' => $this->typeRegistry->type($this->typeRegistry->any()),
					'Null' => $this->typeRegistry->null(),
					'True' => $this->typeRegistry->true(),
					'False' => $this->typeRegistry->false(),
					'Boolean' => $this->typeRegistry->boolean(),
					'Integer' => $this->typeRegistry->integer(),
					'Real' => $this->typeRegistry->real(),
					'String' => $this->typeRegistry->string(),
					default => $this->typeRegistry->withName(new TypeNameIdentifier($typeName)),
				},

			'Any' => fn(): AnyType => $this->typeRegistry->any(),
			'Nothing' => fn(): NothingType => $this->typeRegistry->nothing(),
			'Boolean' => fn(): BooleanType => $this->typeRegistry->boolean(),
			'True' => fn(): TrueType => $this->typeRegistry->true(),
			'False' => fn(): FalseType => $this->typeRegistry->false(),
			'Null' => fn(): NullType => $this->typeRegistry->null(),

			'Mutable' => fn(Type $valueType): MutableType => $this->typeRegistry->mutable($valueType),
			'Type' => fn(Type $refType): TypeType => $this->typeRegistry->type($refType),
			'Function' => fn(Type $parameterType, Type $returnType): Type =>
				$this->typeRegistry->function($parameterType, $returnType),
			'Union' => fn(array $types, bool $normalize = true): Type => $this->typeRegistry->union($types, $normalize),
			'Intersection' => fn(array $types, bool $normalize = true): Type => $this->typeRegistry->intersection($types, $normalize),
			'Result' => fn(Type $okType, Type $errorType): Type => $this->typeRegistry->result($okType, $errorType),
			'String' => fn(
				int $minLength = 0,
				int|PlusInfinity $maxLength = PlusInfinity::value
			): StringType => $this->typeRegistry->string($minLength, $maxLength),
			'Integer' => fn(
				int|MinusInfinity $min = MinusInfinity::value,
				int|PlusInfinity $max = PlusInfinity::value
			): IntegerType => $this->typeRegistry->integer($min, $max),
			'Real' => fn(
				float|MinusInfinity $min = MinusInfinity::value,
				float|PlusInfinity $max = PlusInfinity::value
			): RealType => $this->typeRegistry->real($min, $max),
			'Array' => fn(
				Type $itemType = null,
				int $minLength = 0,
				int|PlusInfinity $maxLength = PlusInfinity::value
			): ArrayType => $this->typeRegistry->array($itemType , $minLength, $maxLength),
			'Map' => fn(
				Type $itemType = null,
				int $minLength = 0,
				int|PlusInfinity $maxLength = PlusInfinity::value
			): MapType => $this->typeRegistry->map($itemType, $minLength, $maxLength),
			/** @param list<Type> $values */
			'Tuple' => fn(array $itemTypes, Type $restType = null): TupleType =>
				$this->typeRegistry->tuple($itemTypes, $restType ?? $this->typeRegistry->nothing()),
			/** @param array<string, Type> $values */
			'Record' => fn(array $itemTypes, Type $restType = null): RecordType =>
				$this->typeRegistry->record($itemTypes, $restType ?? $this->typeRegistry->nothing()),
			/** @param list<int> $values */
			'IntegerSubset' => fn(array $values): IntegerSubsetType => $this->typeRegistry->integerSubset(
				array_map(fn(int $value): IntegerValue => $this->valueRegistry->integer($value), $values)
			),
			/** @param list<float> $values */
			'RealSubset' => fn(array $values): RealSubsetType => $this->typeRegistry->realSubset(
				array_map(fn(float $value): RealValue => $this->valueRegistry->real($value), $values)
			),
			/** @param list<string> $values */
			'StringSubset' => fn(array $values): StringSubsetType => $this->typeRegistry->stringSubset(
				array_map(fn(string $value): StringValue => $this->valueRegistry->string($value), $values)
			),
			'EnumerationSubset' => fn(string $typeName, array $values): EnumerationSubsetType => $this->typeRegistry->enumeration(
				new TypeNameIdentifier($typeName)
			)->subsetType(
				array_map(static fn(string $value): EnumValueIdentifier => new EnumValueIdentifier($value), $values)
			),
		];
	}

	/** @inheritDoc */
	public function valueRegistry(): array {
		return [
			'boolean' => fn(bool $value): BooleanValue => $this->valueRegistry->boolean($value),
			'true' => fn(): BooleanValue => $this->valueRegistry->true(),
			'false' => fn(): BooleanValue => $this->valueRegistry->false(),
			'null' => fn(): NullValue => $this->valueRegistry->null(),
			'integer' => fn(int $value): IntegerValue => $this->valueRegistry->integer($value),
			'real' => fn(float $value): RealValue => $this->valueRegistry->real($value),
			'string' => fn(string $value): StringValue => $this->valueRegistry->string($value),
			/** @param list<Value> $values */
			'list' => fn(array $values): ListValue => $this->valueRegistry->list($values),
			/** @param array<string, Value> $values */
			'dict' => fn(array $values): DictValue => $this->valueRegistry->dict($values),
			'function' => fn(Type $parameterType, Type $returnType, FunctionBody $body): FunctionValue =>
				$this->valueRegistry->function($parameterType, $returnType, $body),
			'type' => fn(Type $type): TypeValue => $this->valueRegistry->type($type),
			'error' => fn(Value $value): ErrorValue => $this->valueRegistry->error($value),
			'mutable' => fn(Type $type, Value $value): MutableValue => $this->valueRegistry->mutable($type, $value),
			'atom' => fn(string $typeName): AtomValue => $this->valueRegistry->atom(new TypeNameIdentifier($typeName)),
			'enumeration' => fn(string $typeName, string $value): EnumerationValue =>
				$this->valueRegistry->enumerationValue(
					new TypeNameIdentifier($typeName),
					new EnumValueIdentifier($value)
				),
			'subtype' => fn(string $typeName, Value $value): Value =>
				$this->valueRegistry->subtypeValue(
					new TypeNameIdentifier($typeName),
					$value
				),
			'state' => fn(string $typeName, Value $value): Value =>
				$this->valueRegistry->stateValue(
					new TypeNameIdentifier($typeName),
					$value
				),
			'vars' => fn(): VariableValueScope => $this->valueRegistry->variables(),
			'val' => fn(Type|string $type): Value|UnresolvableDependency =>
				$this->dependencyContainer->valueByType(
					is_string($type) ?
						$this->typeRegistry->withName(new TypeNameIdentifier($type)) : $type
			),
		];
	}

	/**
	 * @return array{
	 *     addMethod: callable(Type, string, Type, Type|null, Type, FunctionBody): CustomMethod,
	 * }
	 */
	public function methodBuilder(): array {
		return [
			'addMethod' => fn(
				Type $targetType,
				string $methodName,
				Type $parameterType,
				Type|null $dependencyType,
				Type $returnType,
				FunctionBody $body
			): CustomMethod => $this->customMethodRegistryBuilder->addMethod(
				$targetType,
				new MethodNameIdentifier($methodName),
				$parameterType,
				$dependencyType,
				$returnType,
				$body
			)
		];
	}

	public function build(): Program {
		return $this;
	}

	private function analyseAndFindEntryPoint(
		VariableNameIdentifier $functionName,
		Type $expectedParameterType,
		Type $expectedReturnType
	): FunctionValue {
		$this->valueRegistryBuilder->analyse();
		$this->typeRegistryBuilder->analyse();
		$this->customMethodRegistryBuilder->analyse();

		$entryPoint = $this->valueRegistry->variables()->findValueOf($functionName);
		if ($entryPoint instanceof FunctionValue) {
			if ($entryPoint->type()->isSubtypeOf(
				$this->typeRegistry->function(
					$expectedParameterType,
					$expectedReturnType
				)
			)) {
				return $entryPoint;
			}
			throw new LogicException(
				sprintf("Invalid entry point function type ^%s => %s expected, ^%s => %s given",
					$expectedParameterType,
					$expectedReturnType,
					$entryPoint->type()->parameterType(),
					$entryPoint->type()->returnType()
				)
			);
		}
		throw new LogicException(
			sprintf("The entry point function %s is missing", $functionName)
		);
	}

	public function callFunction(
		VariableNameIdentifier $functionName,
		Type $expectedParameterType,
		Type $expectedReturnType,
		Value $parameter
	): Value {
		return $this->analyseAndFindEntryPoint(
			$functionName,
			$expectedParameterType,
			$expectedReturnType
		)->execute($parameter);
	}

	/*
	public function execute(string ... $values): string {
		$result = $mainFn->execute($this->valueRegistry->list( array_map(
			fn(string $value): Value => $this->valueRegistry->string($value),
			$values
		)));
		while($result instanceof SubtypeValue) {
			$result = $result->baseValue();
		}
		return $result instanceof StringValue ? $result->literalValue() :
			throw new LogicException("Invalid main function result type");
	}

	private function analyseAndFindMain(): FunctionValue {
		$this->valueRegistryBuilder->analyse();
		$this->typeRegistryBuilder->analyse();
		$this->customMethodRegistryBuilder->analyse();

		$mainFn = $this->valueRegistry->variables()->findValueOf(
			new VariableNameIdentifier('main')
		);
		if (
			$mainFn instanceof FunctionValue &&
			$mainFn->type()->isSubtypeOf(
				$this->typeRegistry->function(
					$this->typeRegistry->array($this->typeRegistry->string()),
					$this->typeRegistry->string()
				)
			)
		) {
			return $mainFn;
		}
		throw new LogicException("Invalid main function");
	}

	private function transformResponse(Value $response): ResponseInterface {
		return null;
	}

	public function handleRequest(RequestInterface $request): ResponseInterface {
		$mainFn = $this->analyseAndFindRequestHandler();
		$result = $mainFn->execute($this->valueRegistry->list( array_map(
			fn(string $value): Value => $this->valueRegistry->string($value),
			$values
		)));
		while($result instanceof SubtypeValue) {
			$result = $result->baseValue();
		}
		return $result instanceof StringValue ? $result->literalValue() :
			throw new LogicException("Invalid main function result type");
	}

	private function analyseAndFindHandle(): FunctionValue {
		$this->valueRegistryBuilder->analyse();
		$this->typeRegistryBuilder->analyse();
		$this->customMethodRegistryBuilder->analyse();

		$mainFn = $this->valueRegistry->variables()->findValueOf(
			new VariableNameIdentifier('main')
		);
		if (
			$mainFn instanceof FunctionValue &&
			$mainFn->type()->isSubtypeOf(
				$this->typeRegistry->function(
					$this->typeRegistry->array($this->typeRegistry->string()),
					$this->typeRegistry->string()
				)
			)
		) {
			return $mainFn;
		}
		throw new LogicException("Invalid main function");
	}*/

}