<?php

namespace Walnut\Lang\Blueprint\Type;

use LogicException;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;

final class UnknownProperty extends LogicException {

	public function __construct(
		public PropertyNameIdentifier $propertyName,
		public string $notFoundIn
	) {
		parent::__construct(
			sprintf(
				"Unknown property %s of %s",
				$propertyName,
				$notFoundIn
			)
		);
	}
}