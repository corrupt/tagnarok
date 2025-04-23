<?php declare (strict_types=1);

namespace corrupt\tagnarok;

use Google\Service\BigtableAdmin\StandardIsolation;

require_once "utils.php";

class Parser {
    
    private Tokenizer  $tokenizer;
    private string     $string = '';
    private Token|null $lookahead;
    
    private array $context = [];
    
    public array $tags = [
        'album' => false,
        'article' => false,
        'artist' => false,
        'b' => true,
        'band' => false,
        'bandcamp' => false,
        'br' => false,
        'center' => true,
        'color' => true,
        'date' => false,
        'email' => false,
        'font' => true,
        'glow' => true,
        'hr' => false,
        'i' => false,
        'img' => false,
        'left' => true,
        'list' => true,
        'mention' => false,
        'noparse' => true,
        'quote' => true,
        'review' => true,
        'right' => true,
        's' => true,
        'size' => true,
        'soundcloud' => false,
        'spotify' => false,
        'table' => true,
        'td' => true,
        'thread' => false,
        'tr' => true,
        'url' => false,
        'user' => true,
        'vimeo' => false,
        'youtube' => false,
    ];
    
    function __construct()
    {
        $this->tokenizer = new Tokenizer();
    }
    
    protected function isValidTag(string $name): bool
    {
        return in_array($name, array_keys($this->tags));
    }
    
    protected function tagRequiresCloser(string $name): bool
    {
        return $this->tags[$name] ?? false;
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
            ->setValue($this->AST());
    }
    

    function descendTagToken(TagToken $token): Token
    {
        $this->pushContext($token->getName());

        $next = $this->AST();

        if ($this->getContext() == $token->getName()) {
            $token->setContent($next);
        } else {
            if ($this->tagRequiresCloser($token->getName())) {
                $token = tag2word($token);
            }
            $token->setTail($next);
        }

        $this->popContext();

        return $token;
    }

    
    /**
     * Checks if a tag token is among the list of allowed tags,
     * converts it to word otherwise
     * @param TagToken $token 
     * @return Token 
     */
    function sanitizeTokenType(Token $token): Token
    {
        if ($token instanceof TagToken && !$this->isValidTag($token->getName())) {
            $token = tag2word($token);
        }
        return $token;
    }
    

    function AST(): Token
    {
        if ($this->lookahead == null) {
            $this->popContext();
            return Token::new(TokenType::EOF);
        }
        
        $token = $this->literal();
        $next  = $this->AST();
        
        if ($token instanceof TagToken && get_class($token) == TagToken::class) {
            $this->pushContext($token->getName());
            
            if ($this->lookahead == null) {
                $token->setTail($next);
            } else {
                $token->setContent($next);
                $this->popContext();
            }

            return $token;
        }
        
        if ($token instanceof ClosingTagToken && get_class($token) == ClosingTagToken::class) {
            if ($this->getContext() == $token->getName()) {
                return $token;
            }
            return tag2word($token);
        }
        
        return $token->setTail($next);
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