<?php

namespace oscarpalmer\Yogurt\Dairy;

use \oscarpalmer\Yogurt\Exception\Syntax;

class Modifiers extends Worker
{
    public function parse()
    {
        preg_match_all(
            static::VARIABLE_START_REGEX .
            static::VARIABLE_REGEX .
            static::UNKNOWN_SPACES_REGEX .
            static::MODIFIER_SEPARATOR_REGEX .
            static::UNKNOWN_SPACES_REGEX .
            static::MODIFIER_GREEDY_REGEX .
            static::VARIABLE_END_REGEX,
            $this->template,
            $matches
        );

        foreach ($matches[0] as $index => $match) {
            $functions = preg_split(static::MULTIPLE_SPACES_REGEX, $matches[2][$index]);
            $functions = array_reverse($functions);

            if (count($functions) === 1 && $functions[0] === "escape") {
                $this->template = static::replaceVariable($match, $matches[1][$index], $this->template);

                continue;
            }

            $prefix = array();
            $suffix = array();

            foreach ($functions as $function) {
                if ($function === "raw") {
                    continue;
                }

                $parts = $this->dairy->getModifierFunction($function);

                if (is_null($parts)) {
                    throw new Syntax($match);
                }

                array_push($prefix, $parts[0]);
                array_unshift($suffix, $parts[1]);
            }

            $this->template = str_replace(
                $match,
                "<?php echo(" .
                implode($prefix) .
                static::getObjectKey($matches[1][$index]) .
                implode($suffix) .
                "); ?>",
                $this->template
            );
        }

        return $this->template;
    }
}