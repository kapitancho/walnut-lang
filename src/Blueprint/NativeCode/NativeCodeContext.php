<?php

namespace Walnut\Lang\Blueprint\NativeCode;

use Walnut\Lang\Blueprint\Helper\BaseTypeHelper;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Registry\ValueRegistry;

final readonly class NativeCodeContext {
	use BaseTypeHelper;

	public function __construct(
		public TypeRegistry $typeRegistry,
		public ValueRegistry $valueRegistry
	) {}
}