<?php

namespace Walnut\Lang\Implementation\Compilation;

use RuntimeException;
use Walnut\Lib\Walex\Token as LexerToken;

final class ParserException extends RuntimeException {
	public function __construct(public ParserState $state, string $message, LexerToken $token) {
		parent::__construct(
            sprintf("Parser error at token %s at %s: %s",
                is_string($token->rule->tag) ? $token->rule->tag : $token->rule->tag->name,
                $token->sourcePosition,
                $message
            )
		);
	}
}