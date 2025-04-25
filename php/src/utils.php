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

function tag2word(Token $token): Token
{
    return new Token(
        TokenType::Word,
        match: $token->match,
    );
}

function hasEndTag(Token $token, string $name): bool
{
    if ($token->tail === null) {
        return
            $token->type === TokenType::EndTag 
            &&
            $token->name === $name;
    }
    
    return hasEndTag($token->tail, $name);
}

function stripEndTag(Token $token): Token
{
    if ($token->tail === null) {
        return $token;
    }
    
    if ($token->tail->type === TokenType::EndTag) {
        $token->tail = null;
        return $token;
    }
    
    return stripEndTag($token->tail);
}

function getEndTag(Token $token): Token|null
{
    if ($token->tail === null) {
        return null;
    }
    
    if ($token->tail->type === TokenType::EndTag) {
        return $token->tail;
    }
    
    return getEndTag($token->tail);
}