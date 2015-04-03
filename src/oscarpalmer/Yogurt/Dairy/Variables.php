<?php

namespace oscarpalmer\Yogurt\Dairy;

use \oscarpalmer\Yogurt\Exception\Syntax;

class Variables extends Worker
{
    /**
     * Parse variable-syntax in template.
     *
     * @return string Parsed template.
     */
    public function parse()
    {
        preg_match_all(
            static::VARIABLE_START_REGEX .
            static::VARIABLE_REGEX .
            static::VARIABLE_END_REGEX,
            $this->template,
            $matches
        );

        foreach ($matches[0] as $index => $variable) {
            $var = static::getObjectKey($matches[1][$index]);

            $this->template = static::replaceVariable(
                $variable,
                $matches[1][$index],
                $this->template
            );
        }

        return $this->template;
    }
}
