<?php

namespace oscarpalmer\Yogurt;

/**
 * Dairy, the parser.
 */
class Dairy
{
    /**
     * @var string Useful regex snippets.
     */
    const OPERATOR_REGEX = "(?:(={2,3}|!={1,2}|>=|<=|<>|>|<|is|isnt)";
    const VALUE_REGEX = "([\w\-\.\{\}]+|(?:\"|').*?(?:\"|')|\d+)";
    const VARIABLE_REGEX = "([\w\-\.\{\}]+)";

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
            $template = $this->parseForeachs($template);
            $template = $this->parseIfs($template);
            $template = $this->parseIncludes($template);
            $template = $this->parseVariables($template);

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
        preg_match_all("/<!--\s*for.*?endfor\s*-->/s", $template, $matches);

        foreach ($matches[0] as $match) {
            preg_match(
                "/\A<!--\s*for\s+([\w\-]+)\s+in\s+" .
                static::VARIABLE_REGEX .
                "\s*-->(.*?)<!--\s*endfor\s*-->\z/s",
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
        $template = preg_replace("/<!--\s*else\s*-->/", "<?php else: ?>", $template);

        $regex = array(
            "if" => array(
                "/<!--\s*if.*?endif\s*-->/s",
                "/\A<!--\s*if\s*" .
                static::VALUE_REGEX .
                "\s*" .
                static::OPERATOR_REGEX .
                "\s*" .
                static::VALUE_REGEX .
                "\s*|)-->(.*?)<!--\s*endif\s*-->\z/s"
            ),
            "elseif" => array(
                "/<!--\s*elseif.*?-->/",
                "/\A<!--\s*elseif\s*" .
                static::VALUE_REGEX .
                "\s*" .
                static::OPERATOR_REGEX .
                "\s*" .
                static::VALUE_REGEX .
                "\s*|)-->\z/"
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
                $opr = $exists ? "" : static::getOperator($if[2]);
                $val = $exists ? "" : static::getValue($if[3]);
                $blk = $elseif ? "" : $if[4];
                $end = $elseif ? "" : "<?php endif; ?>";

                $replacement = "<?php $name($key$opr$val): ?>$blk$end";

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
        preg_match_all("/<!--\s*include.*?\s*-->/", $template, $matches);

        foreach ($matches[0] as $match) {
            preg_match("/\A<!--\s*include\s*([\w]+)(?:\.(\w+)|)\s*-->\z/", $match, $include);

            if (empty($include)) {
                throw new \LogicException($match);
            }

            $file = $include[1];
            $ext = isset($include[2]) ? $include[2] : $this->settings["extension"];

            $filename = "{$this->settings['directory']}/{$file}.{$ext}";

            if (!is_file($filename)) {
                throw new \Exception("{$filename} does not exist.");
            }

            $template = str_replace($match, $this->parse($filename, false), $template);
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
        preg_match_all("/<!--\s*" . static::VARIABLE_REGEX . "\s*-->/", $template, $matches);

        foreach ($matches[0] as $index => $variable) {
            $var = static::getObjectKey($matches[1][$index]);

            $template = str_replace($variable, "<?php echo({$var}); ?>", $template);
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
        } else {
            return " != ";
        }
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
        } else {
            return static::getObjectKey($value);
        }
    }
}
