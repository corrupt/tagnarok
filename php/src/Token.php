<?php declare (strict_types=1);

namespace corrupt\tagnarok;

use JsonSerializable;

class Token implements JsonSerializable {

    public function __construct(

        public TokenType $type
        {
            set => $this->type = $value;
            get => $this->type;
        },

        public string|null $name = null
        {
            set => $this->name = $value;
            get => $this->name;
        },
            
        public Token|null $tail = null
        {
            set => $this->tail = $value;
            get => $this->tail;
        },

        public Token|null $content = null
        {
            set => $this->content = $value;
            get => $this->content;
        },
            
        public Token|null $endTag = null
        {
            set => $this->terminator = $value;
            get => $this->terminator;
        },

        public string|null $match = null
        {
            set => $this->match = $value;
            get => $this->match;
        },
            
        public int|null $index = null
        {
            set => $this->index = $value;
            get => $this->index;
        },

        public string|null $defaultParameter = null
        {
            set => $this->defaultParameter = $value;
            get => $this->defaultParameter;
        },

        public array $parameters = []
        {
            set => $this->parameters = $value;
            get => $this->parameters;
        }
    ) {}
        
    public int $length {
        get => mb_strlen($this->match ?? '');
    }
    
    public function jsonSerialize(): mixed
    {
        $ret = [];
        
        if (isset($this->type)) {
            $ret['type'] = $this->type;
        }
        
        if (null !== $this->name) {
            $ret['name'] = $this->name;
        }
        
        if (null !== $this->match) {
            $ret['match'] = $this->match;
        }
        
        if (null !== $this->index) {
            $ret['index'] = $this->index;
        }
        
        if (null !== $this->defaultParameter) {
            $ret['defaultParameter'] = $this->defaultParameter;
        }
        
        if (count($this->parameters) > 0) {
            $ret['parameters'] = $this->parameters;
        }

        if (null !== $this->endTag) {
            $ret['terminator'] = $this->endTag;
        }
        
        if (null !== $this->content) {
            $ret['content'] = $this->content;
        }
        
        if (isset($this->tail)) {
            $ret['tail'] = $this->tail;
        }

        return $ret;
    }
    
}