<?php declare (strict_types=1);

namespace corrupt\tagnarok;

require_once "utils.php";

class Parser {
    
    private Tokenizer  $tokenizer;
    private string     $string = '';
    private Token|null $lookahead;
    
    private array $context = [];
    
    public array $tags = [
        'album',
        'article',
        'artiest',
        'b',
        'band',
        'bandcamp',
        'br',
        'center',
        'center',
        'color',
        'date',
        'email',
        'font',
        'glow',
        'hr',
        'i',
        'img',
        'left',
        'list',
        'mention',
        'noparse',
        'quote',
        'review',
        'right',
        's',
        'size',
        'soundcloud',
        'spotify',
        'table',
        'td',
        'thread',
        'tr',
        'url',
        'user',
        'vimeo',
        'youtube',
    ];
    
    function __construct()
    {
        $this->tokenizer = new Tokenizer();
    }
    
    protected function isValidTag(string $name): bool
    {
        return in_array($name, $this->tags);
    }
    
    protected function getContext(): string|null
    {
        $len = count($this->context);
        
        if ($len == 0) {
            return null;
        }
        
        return $this->context[$len-1];
    }
    
    protected function pushContext(string $context): self
    {
        $this->context[] = $context;
        return $this;
    }
    
    protected function popContext(): string|null
    {
        if ($this->getContext()){
            return array_pop($this->context);
        }

        return null;
    }
    
    protected function backtrack(): self
    {
        $this->tokenizer->backtrack();
        $this->tokenizer->removePosition();
        $this->lookahead = $this->tokenizer->getNextToken();
        return $this;
    }
    
    protected function storePosition(): self
    {
        $this->tokenizer->storePosition();
        return $this;
    }
    
    protected function removePosition(): self
    {
        $this->tokenizer->removePosition();
        return $this;
    }
    
    function parse(string $string) {
        
        $this->string = $string;
        $this->tokenizer->init($this->string);
        
        $this->lookahead = $this->tokenizer->getNextToken(); 
        
        return $this->text();
    }
    
    function consume(TokenType $tokenType): Token|null
    {
        $token = $this->lookahead;
        
        if ($token === null) {
            throw new SyntaxException("Unexpected end of input, expected: '{$tokenType->name}'");
        }
        
        if ($token->getType() !== $tokenType) {
            throw new SyntaxException("Unexpected token: '{$token->getType()->name}', expected: '{$tokenType->name}'");
        }
        
        $this->lookahead = $this->tokenizer->getNextToken();
        
        return $token;
    }
    
    
    function text(): Token
    {
        return Token::new()
            ->setType(TokenType::Text)
            ->setValue($this->TokenList());
    }

    
    function TokenList(): array
    {
        $tokenList = [];
        
        while ($this->lookahead !== null) {
            
            $literal = $this->literal();

            switch (true) {

                case $literal instanceof TagToken && get_class($literal) == TagToken::class:
                    
                    $this->pushContext($literal->getName());
                    $this->storePosition(); 

                    $next = $this->TokenList();

                    if ($this->getContext() == $literal->getName()) {
                        $literal->setContent($next);
                        $this->removePosition();
                    } else {
                        $this->backtrack();
                    }

                    $this->popContext();

                    break;

                case $literal instanceof ClosingTagToken && get_class($literal) == ClosingTagToken::class:

                    if ($this->getContext() == $literal->getName()) {
                        return $tokenList;
                    }
                    
                    $literal = Token::new()
                        ->setType(TokenType::Word)
                        ->setValue($literal->getMatch());

                    break;
            }

            array_push($tokenList, $literal);
        }
        
        if ($this->lookahead == null) {
            $this->popContext();
        }
        
        return $tokenList;
    }

    
    /**
     * Literal
     *  : NumericLiteral 
     *  | StringLiteral
     *  ;
     *  
     * @throws SyntaxException
     */
    function literal(): Token|null
    {
        if ($this->lookahead === null) {
            return Token::new()
                ->setType(TokenType::EOF);
        }

        return match($this->lookahead->getType()) {

            TokenType::Space    => $this->space(),
            TokenType::Newline  => $this->newLine(),
            TokenType::Tag      => $this->tag(),
            TokenType::CloseTag => $this->closingTag(),
            TokenType::Word     => $this->word(),
            
            default             => throw new SyntaxException("Unknown token Type"),
        };
    }

    
    /**
     * StringLiteral
     *   : String
     *   ;
     * @return Token 
     * @throws SyntaxException 
     */
    function word(): Token
    {
        return $this->consume(TokenType::Word);
    }

    
    /**
     * NumericLiteral
     *   : Number
     *   ;
     * @return Token 
     * @throws SyntaxException 
     */
    function number(): Token
    {
        return $this->consume(TokenType::Number);
    }

    
    function space(): Token
    {
        return $this->consume(TokenType::Space);
    }

    
    function newLine(): Token
    {
        return $this->consume(TokenType::Newline);
    }

    /**
     * Tag
     *   : ParameterTag 
     *   | DefaultValueTag 
     *   ;
     * @return void 
     */
    function tag(): Token|null
    {
        $token  = $this->lookahead;
        $tag    = TagToken::new();

        $value  = substr($token->getValue(), 1, -1);
        $params = preg_split('/\s+/', $value);
        $name   = array_shift($params);
        
        switch (true) {
            case str_contains($name, "="):
                [$key, $value] = explode("=", $name);
                $tag->setName($key);
                $tag->setDefaultParameter($value);
                break;
            default:
                $tag->setName($name);
                break;
        }

        $tag->setParameters(
            parseParameters($params)
        );

        $this->consume(TokenType::Tag);
        return $tag;
        
    }
    

    function closingTag(): TagToken
    {
        $token = $this->lookahead;
        $value = substr($token->getValue(), 2, -1);
        
        $this->consume(TokenType::CloseTag);
        
        return ClosingTagToken::new()
            ->setName($value)
            ->setMatch($token->getValue());
    }
}