<?php

namespace Walnut\Lang\Implementation\Compilation;

use Walnut\Lang\Blueprint\Compilation\Lexer as LexerInterface;
use Walnut\Lang\Implementation\Compilation\Token as CompilationToken;
use Walnut\Lib\Walex\Lexer;
use Walnut\Lib\Walex\Pattern;
use Walnut\Lib\Walex\Rule;
use Walnut\Lib\Walex\SpecialRuleTag;
use Walnut\Lib\Walex\Token;

final readonly class WalexLexerAdapter implements LexerInterface {
	private Lexer $lexer;

	public function __construct() {
        $this->lexer = new Lexer([
            ... array_map(
                fn(CompilationToken $token): Rule => new Rule(
                    new Pattern($token->value),
                    $token->name
                ),
                CompilationToken::cases()
            ),
            new Rule(
                new Pattern('[\n]'),
                SpecialRuleTag::newLine
            ),
            new Rule(
                new Pattern('.'),
                SpecialRuleTag::skip
            )


        ]);
	}

    /**
     * @param string $sourceCode
     * @return array<Token>
     */
	public function tokensFromSource(string $sourceCode): array {
		return iterator_to_array(
            $this->lexer->getTokensFor($sourceCode)
        );
	}
}