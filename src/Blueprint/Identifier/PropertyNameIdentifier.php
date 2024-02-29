<?php

namespace Walnut\Lang\Blueprint\Identifier;
use JsonSerializable;

final readonly class PropertyNameIdentifier implements JsonSerializable {
	/** @throws IdentifierException */
	public function __construct(
		public string $identifier
	) {
		preg_match('/^((\w+)|\d+)$/', $identifier) ||
			IdentifierException::invalidPropertyNameIdentifier($identifier);
	}

	public function equals(PropertyNameIdentifier $identifier): bool {
		return $this->identifier === $identifier->identifier;
	}

	public function __toString(): string {
		return $this->identifier;
	}

	public function jsonSerialize(): string {
		return $this->identifier;
	}
}