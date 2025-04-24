<?php declare (strict_types=1);

namespace corrupt\tagnarok;

use JsonSerializable;

class Token implements JsonSerializable {

    protected TokenType $type;
    protected Token|string|int|array $value;
    protected Token|string|int|array $tail;
    protected string $match;
    protected int $index;

    private function __construct(){}
        
    public static function new(): Token
    {
        return new Token();
    }
    
    public function setType(TokenType $type): static
    {
        $this->type = $type;
        return $this;
    }
    
    public function getType(): TokenType
    {
        return $this->type;
    }
    
    public function setValue(Token|int|string|array $value): static
    {
        $this->value = $value;
        return $this;
    }
    
    public function getValue(): int|string
    {
        return $this->value;
    }
    
    public function setMatch(string $match): static
    {
        $this->match = $match;
        return $this;
    }
    
    public function getMatch(): string
    {
        return $this->match;
    }
    
    public function setIndex(int $index): static
    {
        $this->index = $index;
        return $this;
    }
    
    public function getIndex(): int
    {
        return $this->index;
    }
    
    public function getTail(): Token|string|int|array
    {
        return $this->tail;
    }
    
    public function setTail(Token|string|int|array $tail): static
    {
        $this->tail = $tail;
        return $this;
    }
    
    public function is(TokenType $tokenType): bool
    {
        return $this->type == $tokenType;
    }
    
    public function jsonSerialize(): mixed
    {
        $ret = [];
        
        if (isset($this->type)) {
            $ret['type'] = $this->type;
        }
        
        if (isset($this->value)) {
            $ret['value'] = $this->value;
        }
        
        if (isset($this->match)) {
            $ret['match'] = $this->match;
        }
        
        if (isset($this->index)) {
            $ret['index'] = $this->index;
        }
        
        if (isset($this->tail)) {
            $ret['tail'] = $this->tail;
        }

        return $ret;
    }
}