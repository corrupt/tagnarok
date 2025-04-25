<?php declare (strict_types=1);

namespace corrupt\tagnarok;

use Google\Service\BigtableAdmin\StandardIsolation;

require_once "utils.php";

class Parser {
    
    public const END_TAG_NON = 0;
    public const END_TAG_REG = 1;
    public const END_TAG_OPT = 2;
    
    private Tokenizer  $tokenizer;
    private string     $string = '';
    private Token|null $lookahead;
    
    private array $context = [];
    
    public array $tags = [
        'album'      => self::END_TAG_OPT,
        'article'    => self::END_TAG_OPT,
        'artist'     => self::END_TAG_OPT,
        'b'          => self::END_TAG_REG,
        'band'       => self::END_TAG_OPT,
        'bandcamp'   => self::END_TAG_NON,
        'br'         => self::END_TAG_NON,
        'center'     => self::END_TAG_REG,
        'color'      => self::END_TAG_REG,
        'date'       => self::END_TAG_NON,
        'email'      => self::END_TAG_NON,
        'font'       => self::END_TAG_REG,
        'glow'       => self::END_TAG_REG,
        'hr'         => self::END_TAG_NON,
        'i'          => self::END_TAG_REG,
        'img'        => self::END_TAG_OPT,
        'left'       => self::END_TAG_REG,
        'list'       => self::END_TAG_REG,
        'mention'    => self::END_TAG_NON,
        'noparse'    => self::END_TAG_REG,
        'quote'      => self::END_TAG_REG,
        'review'     => self::END_TAG_REG,
        'right'      => self::END_TAG_REG,
        's'          => self::END_TAG_REG,
        'size'       => self::END_TAG_REG,
        'soundcloud' => self::END_TAG_OPT,
        'spotify'    => self::END_TAG_OPT,
        'table'      => self::END_TAG_REG,
        'td'         => self::END_TAG_REG,
        'thread'     => self::END_TAG_OPT,
        'tr'         => self::END_TAG_REG,
        'url'        => self::END_TAG_OPT,
        'user'       => self::END_TAG_OPT,
        'vimeo'      => self::END_TAG_NON,
        'youtube'    => self::END_TAG_NON,
    ];
    
    function __construct()
    {
        $this->tokenizer = new Tokenizer();
    }
    
    protected function isValidTag(string $name): bool
    {
        return in_array($name, array_keys($this->tags));
    }
    
    protected function endTagRequired(Token $token): bool
    {
        return $token->type === TokenType::Tag
            && $this->tags[$token->name] === self::END_TAG_REG;
    }
    
    protected function endTagOptional(Token $token): bool
    {
        return $token->type === TokenType::Tag
            && $this->tags[$token->name] === self::END_TAG_OPT;
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

    function parse(string $string): Token
    {
        
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
        
        if ($token->type !== $tokenType) {
            throw new SyntaxException("Unexpected token: '{$token->type->name}', expected: '{$tokenType->name}'");
        }
        
        $this->lookahead = $this->tokenizer->getNextToken();
        
        return $token;
    }
    
    
    function text(): Token
    {
        return new Token(
            TokenType::Text,
            tail: $this->AST(),
        );
    }

    function AST(): Token
    {
        if ($this->lookahead === null) {
            $this->popContext();
            return new Token(TokenType::EOF);
        }
        
        $token = $this->literal();
            
        switch ($token->type) {
            
            case TokenType::Tag:

                if ($this->endTagRequired($token) || $this->endTagOptional($token)) {

                    $this->pushContext($token->name);
                    $next = $this->AST();
                    
                    if (hasEndTag($next, $token->name)) {
                        $this->popContext();
                        $token->endTag  = getEndTag($next);
                        $token->content = stripEndTag($next);
                        $token->tail    = $this->AST();
                    } else {
                        $token = $this->endTagRequired($token)
                            ? $token = tag2word($token)
                            : $token;
                        $token->tail = $next;
                    }
                    return $token;
                }
                
                $token->tail = $this->AST();
                return $token;
                
            case TokenType::EndTag:
                
                return $this->getContext() === $token->name
                    ? $token
                    : tag2word($token);
            

            case TokenType::Word:
            case TokenType::Space:

                $token->tail = $this->AST();
                return $token;
            

            case TokenType::EOF:

                $token->tail = null;
                return $token;
                

            default:

                $token = tag2word($token);
                $token->tail = $this->AST();
                return $token;
        }
    }

    
    /**
     * Literal
     *  : NumericLiteral 
     *  | StringLiteral
     *  ;
     *  
     * @throws SyntaxException
     */
    function literal(): Token
    {
        if ($this->lookahead === null) {
            return new Token(TokenType::EOF);
        }

        return match($this->lookahead->type) {

            TokenType::Space   => $this->space(),
            TokenType::Newline => $this->newLine(),
            TokenType::Tag     => $this->tag(),
            TokenType::EndTag  => $this->endTag(),
            TokenType::Word    => $this->word(),
            
            default            => throw new SyntaxException("Unknown token Type"),
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
    function tag(): Token
    {
        $token  = $this->lookahead;
        $tag    = new Token(
            TokenType::Tag,
            match: $token->match,
            index: $token->index,
        );

        $value  = substr($token->match, 1, -1);
        $params = preg_split('/\s+/', $value);
        $name   = array_shift($params);
        
        switch (true) {
            case str_contains($name, "="):
                [$key, $value] = explode("=", $name, 2);
                $tag->name = $key;
                $tag->defaultParameter = $value;
                break;
            default:
                $tag->name = $name;
                break;
        }

        $tag->parameters = parseParameters($params);

        $this->consume(TokenType::Tag);
        return $tag;
    }
    

    function endTag(): Token
    {
        $token = $this->lookahead;
        $value = substr($token->match, 2, -1);
        
        $this->consume(TokenType::EndTag);
        
        return new Token(
            TokenType::EndTag,
            match: $token->match,
            index: $token->index,
            name: $value,
        );
    }
}