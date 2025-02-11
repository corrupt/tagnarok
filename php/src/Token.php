<?php declare (strict_types=1);

namespace corrupt\tagnarok;

use JsonSerializable;

class Token implements JsonSerializable {

    protected TokenType $type;
    protected Token|string|int|array $value;
    protected string $match;

    private function __construct(){}
        
    public static function new(): Token
    {
        return new Token();
    }
    
    public function setType(TokenType $type): self
    {
        $this->type = $type;
        return $this;
    }
    
    public function getType(): TokenType
    {
        return $this->type;
    }
    
    public function setValue(Token|int|string|array $value): self
    {
        $this->value = $value;
        return $this;
    }
    
    public function getValue(): int|string
    {
        return $this->value;
    }
    
    public function setMatch(string $match): self
    {
        $this->match = $match;
        return $this;
    }
    
    public function getMatch(): string
    {
        return $this->match;
    }
    
    public function is(TokenType $tokenType): bool
    {
        return $this->type == $tokenType;
    }
    
    public function jsonSerialize(): mixed
    {
        return [
            "type"  => $this->type,
            "value" => $this->value,
            "match" => $this->match,
        ];
    }
}