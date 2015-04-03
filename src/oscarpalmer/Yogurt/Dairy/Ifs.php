<?php

namespace oscarpalmer\Yogurt\Dairy;

use \oscarpalmer\Yogurt\Exception\Syntax;

class Ifs extends Worker
{
    /**
     * Parse if-syntax in template.
     *
     * @return string Parsed template.
     */
    public function parse()
    {
        $this->template = preg_replace(static::ELSE_REGEX, "<?php else: ?>", $this->template);

        $regex = array(
            "if" => array(
                static::IF_REGEX,
                static::IF_START_REGEX .
                static::VALUE_REGEX .
                static::UNKNOWN_SPACES_REGEX .
                static::OPERATOR_REGEX .
                static::UNKNOWN_SPACES_REGEX .
                static::VALUE_REGEX .
                static::IF_END_REGEX
            ),
            "elseif" => array(
                static::ELSEIF_REGEX,
                static::ELSEIF_START_REGEX .
                static::VALUE_REGEX .
                static::UNKNOWN_SPACES_REGEX .
                static::OPERATOR_REGEX .
                static::UNKNOWN_SPACES_REGEX .
                static::VALUE_REGEX .
                static::ELSEIF_END_REGEX
            )
        );

        foreach ($regex as $name => $regex_array) {
            preg_match_all($regex_array[0], $this->template, $matches);

            $elseif = $name == "elseif";

            foreach ($matches[0] as $match) {
                preg_match($regex_array[1], $match, $if);

                if (empty($if)) {
                    throw new Syntax($match);
                }

                $exists = empty($if[2]);

                $key = static::getValue($if[1]);
                $key = $exists ? "isset($key)" : $key;

                $operator = $exists ? "" : static::getOperator($if[2]);

                $value = $exists ? "" : static::getValue($if[3]);
                $block = $elseif ? "" : $if[4];

                $end = $elseif ? "" : "<?php endif; ?>";

                $replacement = "<?php {$name}({$key}{$operator}{$value}): ?>{$block}{$end}";

                $this->template = str_replace($match, $replacement, $this->template);
            }
        }

        return $this->template;
    }
}
