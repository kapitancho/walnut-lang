<?php

namespace Walnut\Lang\Implementation\Registry;

use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Registry\DependencyContainer as DependencyContainerInterface;
use Walnut\Lang\Blueprint\Registry\ProgramBuilder as ProgramBuilderInterface;
use Walnut\Lang\Blueprint\Registry\UnresolvableDependency;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Function\MainMethodRegistry;
use Walnut\Lang\Implementation\NativeCode\NativeCodeTypeMapper;

final readonly class ProgramBuilderFactory implements DependencyContainerInterface {

	private ProgramBuilderInterface $builder;
	public TypeRegistry $typeRegistry;
	public ValueRegistry $valueRegistry;
	public ExpressionRegistry $expressionRegistry;
	public CustomMethodRegistryBuilder $customMethodRegistryBuilder;
	public DependencyContainer $dependencyContainer;

	public function __construct() {
		$typeRegistryBuilder = new TypeRegistry;
		$this->typeRegistry = $typeRegistryBuilder->build();
		$valueRegistryBuilder = new ValueRegistry($this->typeRegistry);
		$this->valueRegistry = $valueRegistryBuilder->build();
		$this->expressionRegistry = new ExpressionRegistry(
			$this->typeRegistry,
			$this->valueRegistry,
			$methodRegistry = new MainMethodRegistry(
				new NativeCodeContext($this->typeRegistry, $this->valueRegistry),
				new NativeCodeTypeMapper(),
				$this->customMethodRegistryBuilder =
					new CustomMethodRegistryBuilder(
						$this->typeRegistry,
						$this->valueRegistry,
						$this
					),
				$this
			),
		);
		$this->dependencyContainer = new DependencyContainer(
			$this->valueRegistry,
            $methodRegistry,
			$this->expressionRegistry,
		);
		$this->builder = new ProgramBuilder(
			$typeRegistryBuilder,
			$this->typeRegistry,
			$valueRegistryBuilder,
			$this->valueRegistry,
			$this->expressionRegistry,
			$this->customMethodRegistryBuilder,
			$this->dependencyContainer
		);
	}
	public function valueByType(Type $type): Value|UnresolvableDependency {
		return $this->dependencyContainer->valueByType($type);
	}

	public function getProgramBuilder(): ProgramBuilderInterface {
		return $this->builder;
	}
}