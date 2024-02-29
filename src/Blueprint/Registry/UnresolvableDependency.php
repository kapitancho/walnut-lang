<?php

namespace Walnut\Lang\Blueprint\Registry;

enum UnresolvableDependency {
	case notFound;
	case circularDependency;
	case ambiguous;
	case unsupportedType;
}