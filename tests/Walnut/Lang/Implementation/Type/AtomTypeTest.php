<?php

namespace Walnut\Lang\Test\Implementation\Type;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Type\UnknownType;
use Walnut\Lang\Implementation\Registry\TypeRegistry;

final class AtomTypeTest extends TestCase {

	private readonly TypeRegistry $typeRegistry;

	protected function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->typeRegistry->addAtom(new TypeNameIdentifier('MyAtom'));
		$this->typeRegistry->addAtom(new TypeNameIdentifier('AnotherAtom'));
	}

	public function testAtomType(): void {
		$atomType = $this->typeRegistry->atom(new TypeNameIdentifier('MyAtom'));
		self::assertEquals('MyAtom', $atomType->name()->identifier);
	}

	public function testAtomValue(): void {
		$atomType = $this->typeRegistry->atom(new TypeNameIdentifier('MyAtom'));
		self::assertEquals($atomType, $atomType->value()->type());
	}

	public function testIsSubtypeOf(): void {
		self::assertTrue(
			$this->typeRegistry->atom(new TypeNameIdentifier('MyAtom'))
				->isSubtypeOf($this->typeRegistry->atom(new TypeNameIdentifier('MyAtom')))
		);
		self::assertFalse(
			$this->typeRegistry->atom(new TypeNameIdentifier('MyAtom'))
				->isSubtypeOf($this->typeRegistry->atom(new TypeNameIdentifier('AnotherAtom')))
		);
	}

	public function testAtomTypeInvalid(): void {
		$this->expectException(UnknownType::class);
		$this->typeRegistry->atom(new TypeNameIdentifier('YourAtom'));
	}
}