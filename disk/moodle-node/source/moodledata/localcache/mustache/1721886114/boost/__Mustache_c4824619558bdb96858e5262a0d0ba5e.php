<?php

class __Mustache_c4824619558bdb96858e5262a0d0ba5e extends Mustache_Template
{
    private $lambdaHelper;

    public function renderInternal(Mustache_Context $context, $indent = '')
    {
        $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
        $buffer = '';

        $value = $context->find('welcomemessage');
        $buffer .= $this->section711537e8af2b34b41a65a87863544b00($context, $indent, $value);

        return $buffer;
    }

    private function section711537e8af2b34b41a65a87863544b00(Mustache_Context $context, $indent, $value)
    {
        $buffer = '';
    
        if (!is_string($value) && is_callable($value)) {
            $source = '
<h2 class="mb-3 mt-3">
    {{.}}
</h2>
';
            $result = (string) call_user_func($value, $source, $this->lambdaHelper);
            $buffer .= $result;
        } elseif (!empty($value)) {
            $values = $this->isIterable($value) ? $value : array($value);
            foreach ($values as $value) {
                $context->push($value);
                
                $buffer .= $indent . '<h2 class="mb-3 mt-3">
';
                $buffer .= $indent . '    ';
                $value = $this->resolveValue($context->last(), $context);
                $buffer .= ($value === null ? '' : call_user_func($this->mustache->getEscape(), $value));
                $buffer .= '
';
                $buffer .= $indent . '</h2>
';
                $context->pop();
            }
        }
    
        return $buffer;
    }

}
