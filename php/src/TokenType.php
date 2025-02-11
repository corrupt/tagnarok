<?php declare (strict_types=1);

namespace corrupt\tagnarok;

enum TokenType {
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
}