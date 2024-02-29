<?php

namespace Walnut\Lang\Implementation\Function;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ReturnResult;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Function\CustomMethod as CustomMethodInterface;
use Walnut\Lang\Blueprint\Function\FunctionBody;
use Walnut\Lang\Blueprint\Helper\BaseTypeHelper;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Registry\DependencyContainer;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Registry\UnresolvableDependency;
use Walnut\Lang\Blueprint\Registry\ValueRegistry;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;

final readonly class CustomMethod implements CustomMethodInterface {
	use BaseTypeHelper;

	public function __construct(
		private TypeRegistry $typeRegistry,
		private ValueRegistry $valueRegistry,
		private DependencyContainer $dependencyContainer,
		private Type $targetType,
		private MethodNameIdentifier $methodName,
		private Type $parameterType,
		private Type|null $dependencyType,
		private Type $returnType,
		private FunctionBody $functionBody,
	) {}

	private bool $isAnalysed;

	/** @throws AnalyserException */
	private function analyseFunctionBody(): Type {
		if ($this->isAnalysed ?? false) {
			return $this->returnType;
		}
		$this->isAnalysed ??= true;
		$pairs = [
			new VariablePair(
				new VariableNameIdentifier('#'),
				$this->parameterType,
			),
			new VariablePair(
				new VariableNameIdentifier('$'),
				$this->targetType,
			),
		];
		if ($this->dependencyType) {
			 $pairs[] = new VariablePair(
				new VariableNameIdentifier('%'),
				$this->dependencyType
			 );
		}
		//try {
			$result = $this->functionBody->expression()->analyse(
				VariableScope::fromPairs(... $pairs)
			);
		//} catch (AnalyserException $analyserException) {
		//	throw new AnalyserException(
		//		sprintf("[analysing the call to {$this->targetType}->{$this->methodName}] %s",$analyserException->getMessage())
		//	);
		//}
		$type = $this->typeRegistry->union([
			$result->expressionType,
			$result->returnType
		]);
		if (!$type->isSubtypeOf($this->returnType)) {
			throw new AnalyserException(
				sprintf(
					"Invalid return type: %s should be a subtype of %s",
					$type,
					$this->returnType
				)
			);
		}
		return $this->returnType;
	}

	public function targetType(): Type {
		return $this->targetType;
	}

	public function methodName(): MethodNameIdentifier {
		return $this->methodName;
	}

	public function parameterType(): Type {
		return $this->parameterType;
	}

	public function dependencyType(): Type|null {
		return $this->dependencyType;
	}

	public function returnType(): Type {
		return $this->returnType;
	}

	public function functionBody(): FunctionBody {
		return $this->functionBody;
	}

	public function analyse(Type $targetType, Type $parameterType, Type|null $dependencyType): Type {
		if (!$targetType->isSubtypeOf($this->targetType)) {
			throw new AnalyserException(
				sprintf(
					"[analysing the call to {$this->targetType}->{$this->methodName}]: Invalid target type: %s should be a subtype of %s",
					$targetType,
					$this->targetType
				)
			);
		}
		if (!($parameterType->isSubtypeOf($this->parameterType) || (
			$this->parameterType instanceof RecordType &&
			$parameterType instanceof TupleType &&
			$this->isTupleCompatibleToRecord(
				$this->typeRegistry, $parameterType, $this->parameterType
			)
		))) {
			throw new AnalyserException(sprintf(
				"[analysing the call to {$this->targetType}->{$this->methodName}]: Invalid parameter type: %s should be a subtype of %s", $parameterType, $this->parameterType));
		}
		if ($dependencyType !== null && !$dependencyType->isSubtypeOf($this->dependencyType)) {
			throw new AnalyserException(
				sprintf(
					"[analysing the call to {$this->targetType}->{$this->methodName}]: Invalid dependency type: %s should be a subtype of %s",
					$dependencyType,
					$this->dependencyType
				)
			);
		}
		return $this->analyseFunctionBody();
	}

	public function execute(Value $targetValue, Value $parameter, Value|null $dependencyValue): Value {
		try {
			$pairs = [
				new VariableValuePair(
					new VariableNameIdentifier('#'),
					new TypedValue(
						$this->parameterType,
						$parameter
					)
				),
				new VariableValuePair(
					new VariableNameIdentifier('$'),
					new TypedValue(
						$this->targetType,
						$targetValue
					)
				),
			];
			if ($this->dependencyType) {
				$dep = $dependencyValue ?? $this->dependencyContainer->valueByType($this->dependencyType);
				if ($dep instanceof UnresolvableDependency) {
					return $this->valueRegistry->error(
						$this->valueRegistry->stateValue(
							new TypeNameIdentifier('DependencyContainerError'),
							$this->valueRegistry->dict([
								'targetType' => $this->valueRegistry->type($this->dependencyType),
								'errorMessage' => $this->valueRegistry->string(
									match($dep) {
										UnresolvableDependency::circularDependency => 'Circular dependency',
										UnresolvableDependency::ambiguous => 'Ambiguous dependency',
										UnresolvableDependency::notFound => 'Dependency not found',
										UnresolvableDependency::unsupportedType => 'Unsupported type',
									}
								)
							])
						)
					);
				}
				$pairs[] = new VariableValuePair(
					new VariableNameIdentifier('%'),
					new TypedValue(
						$this->dependencyType,
						$dep
					)
				);
			}
			return $this->functionBody->expression()->execute(
				VariableValueScope::fromPairs(... $pairs)
			)->value();
		} catch (ReturnResult $result) {
			return $result->value;
		}
	}

	public function __toString(): string {
		$dependency = $this->dependencyType ?
			sprintf(" using %s", $this->dependencyType) : '';
		return sprintf(
			"%s:%s ^%s => %s%s :: %s",
			$this->targetType,
			$this->methodName,
			$this->parameterType,
			$this->returnType,
			$dependency,
			$this->functionBody
		);
	}
}