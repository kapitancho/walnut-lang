<?php

namespace Walnut\Lang\Blueprint\Type;

interface IntersectionType extends Type {
    /**
     * @return list<Type>
     */
    public function types(): array;
}