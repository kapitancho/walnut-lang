<?php

namespace Walnut\Lang\Blueprint\Registry;

use Walnut\Lang\Blueprint\Type\Type;

final readonly class DependencyError {
	public function __construct(
		public UnresolvableDependency $unresolvableDependency,
		public Type $type
	) {}
}