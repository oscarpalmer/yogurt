<?php

namespace oscarpalmer\Yogurt\Dairy;

abstract class Worker
{
    /**
     * @var string Useful regexes and strings.
     */
    const ELSE_REGEX = "/<!--\s*else\s*-->/";
    const ELSEIF_REGEX = "/<!--\s*elseif.*?-->/";
    const ELSEIF_END_REGEX = "\s*|)-->\z/";
    const ELSEIF_START_REGEX = "/\A<!--\s*elseif\s+";
    const FOREACH_REGEX = "/<!--\s*for.*?endfor\s*-->/s";
    const FOREACH_END_REGEX = "\s*-->(.*?)<!--\s*endfor\s*-->\z/s";
    const FOREACH_START_REGEX = "/\A<!--\s*for\s+([\w\-]+)\s+in\s+";
    const IF_REGEX = "/<!--\s*if.*?endif\s*-->/s";
    const IF_END_REGEX = "\s*|)-->(.*?)<!--\s*endif\s*-->\z/s";
    const IF_START_REGEX = "/\A<!--\s*if\s*";
    const INCLUDE_REGEX = "/<!--\s*include.*?\s*-->/";
    const INCLUDE_COMPLEX_REGEX = "/\A<!--\s*include\s+([\w\-]+)(?:\.(\w+)|)\s*-->\z/";
    const MODIFIER_GREEDY_REGEX = "(.*?)";
    const MODIFIER_SEPARATOR_REGEX = "(?:\~|\|)";
    const OPERATOR_REGEX = "(?:(={2,3}|!={1,2}|>=|<=|<>|>|<|is|isnt)";
    const MULTIPLE_SPACES_REGEX = "/\s+/";
    const UNKNOWN_SPACES_REGEX = "\s*";
    const VALUE_REGEX = "([\w\-\.\{\}]+|(?:\"|').*?(?:\"|')|\d+)";
    const VARIABLE_PREFIX = "<?php echo(htmlspecialchars(";
    const VARIABLE_REGEX = "([\w\-\.\{\}]+)";
    const VARIABLE_END_REGEX = "\s*-->/";
    const VARIABLE_START_REGEX = "/<!--\s*";
    const VARIABLE_SUFFIX = ", \ENT_QUOTES | \ENT_SUBSTITUTE, \"utf-8\")); ?>";

    /**
     * @var string Template to parse.
     */
    protected $template;

    /**
     * Create a new parser object.
     *
     * @param string $template Template to parse.
     */
    public function __construct(\oscarpalmer\Yogurt\Dairy $dairy, $template)
    {
        $this->dairy = $dairy;
        $this->template = $template;
    }

    /**
     *
     */
    abstract public function parse();

    /** Static functions. */

    /**
     * Get object-key name from regular key.
     *
     * @param  string $key Key to convert.
     * @return string Converted key.
     */
    public static function getObjectKey($key)
    {
        $key = preg_replace("/\.(\d+)(\.|)/", "{\\1}\\2", $key);
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

    /**
     * Replace variable-syntax with PHP syntax.
     *
     * @param  string $variable    Substring to replace.
     * @param  string $replacement String to insert.
     * @param  string $string      String to replace within.
     * @return string String with PHP syntax.
     */
    public static function replaceVariable($variable, $replacement, $string)
    {
        return str_replace(
            $variable,
            static::VARIABLE_PREFIX .
            static::getObjectKey($replacement) .
            static::VARIABLE_SUFFIX,
            $string
        );
    }
}
