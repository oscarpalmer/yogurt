<?php

namespace oscarpalmer\Yogurt;

/**
 * Dairy, the parser.
 */
class Dairy
{
    /**
     * @var string Useful regexes and strings.
     */
    const ELSE_REGEX = "/<!--\s*else\s*-->/";
    const ELSEIF_REGEX = "/<!--\s*elseif.*?-->/";
    const ELSEIF_END_REGEX = "\s*|)-->\z/";
    const ELSEIF_START_REGEX = "/\A<!--\s*elseif\s*";
    const FOREACH_REGEX = "/<!--\s*for.*?endfor\s*-->/s";
    const FOREACH_END_REGEX = "\s*-->(.*?)<!--\s*endfor\s*-->\z/s";
    const FOREACH_START_REGEX = "/\A<!--\s*for\s+([\w\-]+)\s+in\s+";
    const IF_REGEX = "/<!--\s*if.*?endif\s*-->/s";
    const IF_END_REGEX = "\s*|)-->(.*?)<!--\s*endif\s*-->\z/s";
    const IF_START_REGEX = "/\A<!--\s*if\s*";
    const INCLUDE_REGEX = "/<!--\s*include.*?\s*-->/";
    const INCLUDE_COMPLEX_REGEX = "/\A<!--\s*include\s*([\w]+)(?:\.(\w+)|)\s*-->\z/";
    const MODIFIER_GREEDY_REGEX = "(\w+)";
    const MODIFIER_NAMES_REGEX = "(raw)";
    const MODIFIER_SEPARATOR_REGEX = "(\~|\|)";
    const NO_PHP_REGEX = "/<\?php.*?\?>/";
    const OPERATOR_REGEX = "(?:(={2,3}|!={1,2}|>=|<=|<>|>|<|is|isnt)";
    const SPACE_REGEX = "\s*";
    const VALUE_REGEX = "([\w\-\.\{\}]+|(?:\"|').*?(?:\"|')|\d+)";
    const VARIABLE_PREFIX = "<?php echo(htmlspecialchars((string) ";
    const VARIABLE_REGEX = "([\w\-\.\{\}]+)";
    const VARIABLE_END_REGEX = "\s*-->/";
    const VARIABLE_START_REGEX = "/<!--\s*";
    const VARIABLE_SUFFIX = ", \ENT_QUOTES | \ENT_SUBSTITUTE, \"utf-8\")); ?>";

    /**
     * @var string Filename of template.
     */
    protected $filename = "unknown template";

    /**
     * @var array Settings for Dairy.
     */
    protected $settings;

    /**
     * Create a new Dairy (parser).
     *
     * @param array $settings Settings; usually from Yogurt.
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /** Public functions. */

    /**
     * Parse a template.
     *
     * @param  string  $filename      Filename for template.
     * @param  boolean $save_filename Save filename in object? Default is true; false if parsing included content.
     * @return string  Parsed template.
     */
    public function parse($filename, $save_filename = true)
    {
        if ($save_filename) {
            $this->filename = $filename;
        }

        try {
            $template = file_get_contents($filename);
            $template = preg_replace(static::NO_PHP_REGEX, "", $template);

            $template = $this->parseForeachs($template);
            $template = $this->parseIfs($template);
            $template = $this->parseIncludes($template);
            $template = $this->parseModifiers($template);
            $template = $this->parseVariables($template);

            #var_dump($template);exit;

            return $template;
        } catch (\Exception $exception) {
            static::displaySyntaxErrorMessage($exception, $this->filename);
        }
    }

    /**
     * Parse foreach-statements.
     *
     * @param  string $template Template to parse.
     * @return string Parsed template.
     */
    public function parseForeachs($template)
    {
        preg_match_all(static::FOREACH_REGEX, $template, $matches);

        foreach ($matches[0] as $match) {
            preg_match(
                static::FOREACH_START_REGEX .
                static::VARIABLE_REGEX .
                static::FOREACH_END_REGEX,
                $match,
                $foreach
            );

            if (empty($foreach)) {
                throw new \LogicException($match);
            }

            $array = static::getObjectKey($foreach[2]);

            $replacement = "<?php foreach({$array} as \${$foreach[1]}): ?>{$foreach[3]}<?php endforeach; ?>";

            $template = str_replace($match, $replacement, $template);
        }

        return $template;
    }

    /**
     * Parse if-statements.
     *
     * @param  string $template Template to parse.
     * @return string Parsed template.
     */
    public function parseIfs($template)
    {
        $template = preg_replace(static::ELSE_REGEX, "<?php else: ?>", $template);

        $regex = array(
            "if" => array(
                static::IF_REGEX,
                static::IF_START_REGEX .
                static::VALUE_REGEX .
                static::SPACE_REGEX .
                static::OPERATOR_REGEX .
                static::SPACE_REGEX .
                static::VALUE_REGEX .
                static::IF_END_REGEX
            ),
            "elseif" => array(
                static::ELSEIF_REGEX,
                static::ELSEIF_START_REGEX .
                static::VALUE_REGEX .
                static::SPACE_REGEX .
                static::OPERATOR_REGEX .
                static::SPACE_REGEX .
                static::VALUE_REGEX .
                static::ELSEIF_END_REGEX
            )
        );

        foreach ($regex as $name => $regex_array) {
            preg_match_all($regex_array[0], $template, $if_matches);

            $elseif = $name == "elseif";

            foreach ($if_matches[0] as $match) {
                preg_match($regex_array[1], $match, $if);

                if (empty($if)) {
                    throw new \LogicException($match);
                }

                $exists = empty($if[2]);

                $key = static::getValue($if[1]);
                $key = $exists ? "isset($key)" : $key;

                $operator = $exists ? "" : static::getOperator($if[2]);

                $value = $exists ? "" : static::getValue($if[3]);
                $block = $elseif ? "" : $if[4];

                $end = $elseif ? "" : "<?php endif; ?>";

                $replacement = "<?php {$name}({$key}{$operator}{$value}): ?>{$block}{$end}";

                $template = str_replace($match, $replacement, $template);
            }
        }

        return $template;
    }

    /**
     * Parse includes and their included content.
     *
     * @param  string $template Template to parse.
     * @return string Parsed template.
     */
    public function parseIncludes($template)
    {
        preg_match_all(static::INCLUDE_REGEX, $template, $matches);

        foreach ($matches[0] as $match) {
            preg_match(static::INCLUDE_COMPLEX_REGEX, $match, $include);

            if (empty($include)) {
                throw new \LogicException($match);
            }

            $file = $include[1];
            $ext = isset($include[2]) ? $include[2] : $this->settings["extension"];

            $filename = "{$this->settings['directory']}/{$file}.{$ext}";

            if (is_file($filename) === false) {
                throw new \LogicException("{$filename} does not exist.");
            }

            $template = str_replace($match, $this->parse($filename, false), $template);
        }

        return $template;
    }

    /**
     * Parse variables with modifiers.
     *
     * @param  string $template Template to parse.
     * @return Parsed Template.
     */
    public function parseModifiers($template)
    {
        preg_match_all(
            static::VARIABLE_START_REGEX .
            static::VARIABLE_REGEX .
            static::SPACE_REGEX .
            static::MODIFIER_SEPARATOR_REGEX .
            static::SPACE_REGEX .
            static::MODIFIER_GREEDY_REGEX .
            static::VARIABLE_END_REGEX,
            $template,
            $matches
        );

        foreach ($matches[0] as $match) {
            preg_match(
                static::VARIABLE_START_REGEX .
                static::VARIABLE_REGEX .
                static::SPACE_REGEX .
                static::MODIFIER_SEPARATOR_REGEX .
                static::SPACE_REGEX .
                static::MODIFIER_NAMES_REGEX .
                static::VARIABLE_END_REGEX,
                $match,
                $modifier
            );

            if (empty($modifier)) {
                throw new \LogicException($match);
            }

            $function = static::getFunctionName($modifier[2]);
            $is_raw = is_null($function);
            $variable = static::getObjectKey($modifier[1]);

            $prefix = $is_raw ? "" : "{$function}(";
            $suffix = $is_raw ? "": ")";

            $template = str_replace(
                $match,
                "<?php echo({$prefix}(string) {$variable}{$suffix}); ?>",
                $template
            );
        }

        return $template;
    }

    /**
     * Parse variables.
     *
     * @param  string $template Template to parse.
     * @return string Parsed template.
     */
    public function parseVariables($template)
    {
        preg_match_all(
            static::VARIABLE_START_REGEX .
            static::VARIABLE_REGEX .
            static::VARIABLE_END_REGEX,
            $template,
            $matches
        );

        foreach ($matches[0] as $index => $variable) {
            $var = static::getObjectKey($matches[1][$index]);

            $template = str_replace(
                $variable,
                static::VARIABLE_PREFIX .
                $var .
                static::VARIABLE_SUFFIX,
                $template
            );
        }

        return $template;
    }

    /** Static functions. */

    /**
     * Display a syntax error message.
     *
     * @param Exception $exception Thrown exception.
     * @param string    $filename  Filename for template in which the error was found.
     */
    public static function displaySyntaxErrorMessage(\Exception $exception, $filename)
    {
        $prefix = "<div style=\"padding:0 1em;border:.5em solid red;font-size:1rem;font-weight:normal\">";
        $prefix .= "<p><b>Error!</b></p>";

        $middle = "<p>The syntax below is invalid and can be found in the template file";
        $middle .= " <code>{$filename}</code>.</p>";

        $suffix = $exception->getMessage();
        $suffix = preg_replace("/>\s+</", ">\n<", $suffix);
        $suffix = htmlspecialchars($suffix);
        $suffix = "<pre>{$suffix}</pre>\n</div>";

        echo("{$prefix}\n{$middle}\n{$suffix}");
    }

    /**
     * Get function name for string modifier.
     *
     * @param  string Modifier function name to find.
     * @return string Internal PHP function name.
     */
    public static function getFunctionName($modifier)
    {
        switch ($modifier) {
            case "raw":
                return null;
        }
    }

    /**
     * Get object-key name from regular key.
     *
     * @param  string $key Key to convert.
     * @return string Converted key.
     */
    public static function getObjectKey($key)
    {
        $key = preg_replace("/(\A|\.)(\d+)(\.|\z)/", "\\1{\\2}\\3", $key);
        $key = str_replace(".", "->", $key);

        return "\${$key}";
    }

    /**
     * Get comparison operator.
     *
     * @param  string $operator Operator to fix.
     * @return string Fixed operator.
     */
    public static function getOperator($operator)
    {
        if (in_array($operator, array("===", "==", "!==", "!=", ">=", "<=", "<>", ">", "<"))) {
            return " {$operator} ";
        }

        if ($operator == "is") {
            return " == ";
        }

        return " != ";
    }

    /**
     * Get "raw" value.
     *
     * @param  string $value Value to convert.
     * @return string Raw value.
     */
    public static function getValue($value)
    {
        if (preg_match("/\A(\"|\'|)(\d+|false|null|true)(\"|\'|)\z/", $value)) {
            return trim($value, "'\"");
        } elseif (preg_match("/\A\"|'/", $value)) {
            return $value;
        }

        return static::getObjectKey($value);
    }
}
