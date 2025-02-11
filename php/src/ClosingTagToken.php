<?php declare (strict_types=1);

namespace corrupt\tagnarok;

class ClosingTagToken extends TagToken {

    private function __construct(){}
    
    public static function new(): ClosingTagToken
    {
        return (new ClosingTagToken())
            ->setType(TokenType::CloseTag)
            ;
    }
    
    
}