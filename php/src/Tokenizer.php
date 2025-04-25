<?php declare (strict_types=1);

namespace corrupt\tagnarok;

class Spec {
    function __construct(
        public TokenType $type,
        public string    $regex,
    ){}
}

class Tokenizer {
    
    private string $string = '';
    private int    $cursor = 0;
    
    private array  $spec = [];    

    public function init(string $string): self
    {
        $this->string = $string; 

        $this->spec = [

            new Spec(
                type:  TokenType::EndTag,
                regex: '/^\[\/[^\]]+\]/'
            ),
            new Spec(
                type:  TokenType::Tag,
                regex: '/^\[[^\/\]]+\]/'
            ),
            new Spec(
                type:  TokenType::Space,
                regex: '/^\s+/'
            ),
            new Spec(
                type:  TokenType::Newline,
                regex: '/^\n/'
            ),
            new Spec(
                type:  TokenType::Word,
                regex: '/^[^\s\[\]]+/'
            ),  
        ];

        return $this;
    }
    
    protected function isEOF(): bool
    {
        return $this->cursor === strlen($this->string);
    }
    
    protected function hasNext(): bool
    {
        return $this->cursor < strlen($this->string);
    }
    
    protected function match(string $regex, string $string): string|null
    {
        $test = preg_match($regex, $string, $matches);
        if ($test === 1) {
            $this->cursor += strlen($matches[0]);
            return $matches[0];
        }
        return null;
    }
    
    public function getNextToken(): Token|null
    {
        if (!$this->hasNext()) {
            return null;
        }
        
        $string = substr($this->string, $this->cursor);
        
        foreach ($this->spec as $spec) {
            $value = $this->match($spec->regex, $string);
            
            if ($value === null)  {
                continue;
            }
            
            if ($spec->type == TokenType::NULL) {
                return $this->getNextToken();
            }
            
            return new Token(
                $spec->type,
                match: $value,
                index: $this->cursor,
            );
        }

        $this->error("Unexpected token at position {$this->cursor}: '{$string[0]}'");
        return null;
    }
    
    private function error(string $message): void
    {
        $pad = "";
        
        for ($i = 0; $i<$this->cursor; $i++) {
            $pad .= " ";
        }

        echo($this->string);
        echo "\n";
        echo($pad . "^");
        echo "\n";

        throw new SyntaxException($message);
    }
}