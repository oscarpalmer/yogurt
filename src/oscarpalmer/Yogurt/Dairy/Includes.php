<?php

namespace oscarpalmer\Yogurt\Dairy;

use \oscarpalmer\Yogurt\Exception\Syntax;

class Includes extends Worker
{
    public function parse()
    {
        preg_match_all(static::INCLUDE_REGEX, $this->template, $matches);

        foreach ($matches[0] as $match) {
            preg_match(static::INCLUDE_COMPLEX_REGEX, $match, $include);

            if (empty($include)) {
                throw new Syntax($match);
            }

            $filename = $include[1];
            $extension = isset($include[2]) ? $include[2] : $this->dairy->settings["extension"];

            $filename = "{$this->dairy->settings['directory']}/{$filename}.{$extension}";

            if (is_file($filename) === false) {
                throw new \LogicException("{$filename}__br__{$match}");
            }

            $this->template = str_replace($match, $this->dairy->parse($filename, false), $this->template);
        }

        return $this->template;
    }
}