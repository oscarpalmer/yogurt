<?php

namespace oscarpalmer\Yogurt;

class Dairy
{
    /**
     * Filename of template.
     *
     * @access public
     */
    protected $filename = "unknown template";

    /**
     * Settings for Dairy.
     *
     * @access public
     */
    protected $settings;

    /**
     * Constructor.
     *
     * Create a new Dairy (parser).
     *
     * @access public
     * @param array $settings Settings; usually from Yogurt.
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Parse a template.
     *
     * @access public
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
     * @access public
     * @param  string $template Template to parse.
     * @return string Parsed template.
     */
    public function parseForeachs($template)
    {
        preg_match_all("/<!--\s*for.*?endfor\s*-->/s", $template, $matches);

        foreach ($matches[0] as $match) {
            preg_match("/\A<!--\s*for\s+([\w\-]+)\s+in\s+([\w\-\.\{\}]+)\s*-->(.*?)<!--\s*endfor\s*-->\z/s", $match, $foreach);

            if (empty($foreach)) {
                throw new \LogicException($match);
            }

            $array = static::getObjectKey($foreach[2]);

            $template = str_replace($match, "<?php foreach({$array} as \${$foreach[1]}): ?>{$foreach[3]}<?php endforeach; ?>", $template);
        }

        return $template;
    }

    /**
     * Parse if-statements.
     *
     * @access public
     * @param  string $template Template to parse.
     * @return string Parsed template.
     */
    public function parseIfs($template)
    {
        $template = preg_replace("/<!--\s*else\s*-->/", "<?php else: ?>", $template);

        $regex = array(
            "if" => array(
                "/<!--\s*if.*?endif\s*-->/s",
                "/\A<!--\s*if\s*([\w\-\.\{\}]+|(?:\"|').*?(?:\"|')|\d+)\s*(?:(={2,3}|!={1,2}|>=|<=|<>|>|<|is|isnt)\s*([\w\-\.\{\}]+|(?:\"|').*?(?:\"|')|\d+)\s*|)-->(.*?)<!--\s*endif\s*-->\z/s"
            ),
            "elseif" => array(
                "/<!--\s*elseif.*?-->/",
                "/\A<!--\s*elseif\s*([\w\-\.\{\}]+|(?:\"|').*?(?:\"|')|\d+)\s*(?:(={2,3}|!={1,2}|>=|<=|<>|>|<|is|isnt)\s*([\w\-\.\{\}]+|(?:\"|').*?(?:\"|')|\d+)\s*|)-->\z/"
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
     * @access public
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
     * @access public
     * @param  string $template Template to parse.
     * @return string Parsed template.
     */
    public function parseVariables($template)
    {
        preg_match_all("/<!--\s*([\w\-\.\{\}]+)\s*-->/", $template, $matches);

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
     * @access public
     * @param Exception $exception Thrown exception.
     * @param string    $filename  Filename for template in which the error was found.
     */
    public static function displaySyntaxErrorMessage(\Exception $exception, $filename)
    {
        $prefix = "<div style=\"padding:0 1em;border:.5em solid red;font-size:1rem;font-weight:normal\"><p><b>Error!</b></p>";
        $middle = "<p>The syntax below is invalid and can be found in the template file <code>{$filename}</code>.</p>";

        $suffix = $exception->getMessage();
        $suffix = preg_replace("/>\s+</", ">\n<", $suffix);
        $suffix = htmlspecialchars($suffix);
        $suffix = "<pre>{$suffix}</pre>\n</div>";

        echo("{$prefix}\n{$middle}\n{$suffix}");
    }

    /**
     * Get object-key name from regular key.
     *
     * @access public
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
     * @access public
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
     * @access public
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
