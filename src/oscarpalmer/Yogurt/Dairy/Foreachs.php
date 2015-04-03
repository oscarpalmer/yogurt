<?php

namespace oscarpalmer\Yogurt\Dairy;

use \oscarpalmer\Yogurt\Exception\Syntax;

class Foreachs extends Worker
{
    public function parse()
    {
        preg_match_all(static::FOREACH_REGEX, $this->template, $matches);

        foreach ($matches[0] as $match) {
            preg_match(
                static::FOREACH_START_REGEX .
                static::VARIABLE_REGEX .
                static::FOREACH_END_REGEX,
                $match,
                $foreach
            );

            if (empty($foreach)) {
                throw new Syntax($match);
            }

            $array = static::getObjectKey($foreach[2]);

            preg_match("/\A(?:|.*->)(.*)\z/", $array, $index);

            $replacement = "<?php foreach({$array} as {$index[1]}_index => \${$foreach[1]}): ?>";
            $replacement .= "{$foreach[3]}<?php endforeach; ?>";

            $this->template = str_replace($match, $replacement, $this->template);
        }

        return $this->template;
    }
}
