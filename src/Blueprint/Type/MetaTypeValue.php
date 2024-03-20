<?php

namespace Walnut\Lang\Blueprint\Type;

enum MetaTypeValue: string {
    case Function = 'Function';
	case Tuple = 'Tuple';
	case Record = 'Record';
	case Union = 'Union';
	case Intersection = 'Intersection';
}