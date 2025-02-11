<?php declare (strict_types=1);

namespace corrupt\tagnarok;

class TagToken extends Token {

    protected string      $name;
    protected string|null $defaultParameter = null;
    protected array|null  $parameters = null;
    protected array|null  $content = null;
    
    private function __construct(){}
    
    public static function new(): TagToken
    {
        return (new TagToken())
            ->setType(TokenType::Tag)
            ;
    }
    
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }
    
    public function getParameters(): array|null
    {
        return $this->parameters;
    }
    
    public function setDefaultParameter(string $defaultParameter): self
    {
        $this->defaultParameter = $defaultParameter;
        return $this;
    }
    
    public function getDefaultParameter(): string|null
    {
        return $this->defaultParameter;
    }
    
    public function setContent(array|null $content): self
    {
        $this->content = $content;
        return $this;
    }
    
    public function getContent(): array|null
    {
        return $this->getContent();
    }
}
