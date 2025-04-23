<?php declare (strict_types=1);

use corrupt\tagnarok\TagToken;
use corrupt\tagnarok\Token;
use corrupt\tagnarok\TokenType;

function parseParameters(array $params): array
{
    $parameters = [];

    foreach ($params as $param) {
        if (str_contains($param, "=")) {
            $parameters[] = explode("=", $param);
        } else {
            $parameters[] = [$param, true];
        }
    }
    
    return $parameters;
}

function tag2word(TagToken $token): Token
{
    return Token::new() 
        ->setType(TokenType::Word)
        ->setValue($token->getMatch());
}