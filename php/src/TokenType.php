<?php declare (strict_types=1);

namespace corrupt\tagnarok;

use JsonSerializable;

enum TokenType implements JsonSerializable {
    case Text;
    case Word;
    case Space;
    case Newline;
    case Number;
    case Tag;
    case CloseTag;
    case TagOpenBracket;
    case TagCloseBracket;
    case Slash;
    case EqualsSign;
    case TagBody;
    case TagName;
    case Parameter;
    
    case EOF;
    case NULL;
    
    function jsonSerialize(): mixed
    {
        return $this->name;
    }
}