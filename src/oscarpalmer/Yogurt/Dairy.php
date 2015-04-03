<?php

namespace oscarpalmer\Yogurt;

use oscarpalmer\Yogurt\Exception\Syntax;

// Define ENT_SUBSTITUTE for older versions of PHP.
// @codeCoverageIgnoreStart
if (defined("ENT_SUBSTITUTE") === false) {
    define("ENT_SUBSTITUTE", 0);
}
// @codeCoverageIgnoreEnd

/**
 * Dairy, the parser.
 */
class Dairy
{
    /**
     * @var array Array of modifier-function prefixes and suffixes.
     */
    protected $modifiers = array(
        "dump" => array(
            "var_dump(",
            ")"
        ),
        # Useful for combining default output with other modifiers.
        "escape" => array(
            "htmlspecialchars(",
            ", \ENT_QUOTES | \ENT_SUBSTITUTE, \"utf-8\")"
        ),
        "json" => array(
            "json_encode(",
            ")"
        ),
        "lowercase" => array(
            "mb_strtolower(",
            ", \"utf-8\")"
        ),
        "trim" => array(
            "trim(",
            ")"
        ),
        "uppercase" => array(
            "mb_strtoupper(",
            ", \"utf-8\")"
        )
    );

    /**
     * @var array Settings for Dairy.
     */
    public $settings;

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
     * Get function name for string modifier.
     *
     * @param  string     Modifier function name to find.
     * @return array|null Array of function prefix and suffix or null.
     */
    public function getModifierFunction($modifier)
    {
        if (array_key_exists($modifier, $this->modifiers)) {
            return $this->modifiers[$modifier];
        }

        return null;
    }

    /**
     * Parse a template.
     *
     * @param  string  $filename      Filename for template.
     * @param  boolean $save_filename Save filename in object? False if parsing included content.
     * @return string  Parsed template.
     */
    public function parse($filename)
    {
        try {
            $template = file_get_contents($filename);
            $template = preg_replace("/<\?php.*?\?>/", "", $template);

            foreach (array(
                "oscarpalmer\Yogurt\Dairy\Foreachs",
                "oscarpalmer\Yogurt\Dairy\Ifs",
                "oscarpalmer\Yogurt\Dairy\Includes",
                "oscarpalmer\Yogurt\Dairy\Modifiers",
                "oscarpalmer\Yogurt\Dairy\Variables"
            ) as $class) {
                $parser = new $class($this, $template);

                $template = $parser->parse();
            }

            return $template;
        } catch (Syntax $exception) {
            static::displayErrorMessage($exception, $filename);
        } catch (\LogicException $exception) {
            static::displayErrorMessage($exception, $filename, false);
        }
    }

    /** Static functions. */

    /**
     * Display an error message.
     *
     * @param Exception $exception Thrown exception.
     * @param string    $filename  Filename for template in which the error was found.
     */
    public static function displayErrorMessage(
        \Exception $exception,
        $filename,
        $syntax = true
    ) {
        if ($syntax) {
            $middle = "<p>The syntax below is invalid and can be found in";
        } else {
            list($middle, $suffix) = explode("__br__", $exception->getMessage(), 2);

            $middle = "<p>The file <code>{$middle}</code> could not be found when included from";
        }

        echo(
            static::errorPrefix($syntax ? "Syntax error!" : "Error!") .
            "{$middle} the template file <code>{$filename}</code>.</p>" .
            static::errorSuffix($syntax ? $exception->getMessage() : $suffix)
        );
    }

    /**
     * Get the error message prefix with custom title.
     *
     * @param  string $prefix Prefix to insert.
     * @return string Prefix for message.
     */
    public static function errorPrefix($prefix)
    {
        return "<div style=\"padding:0 1em;border:.5em solid red;" .
               "font-size:1em;font-weight:normal\">" .
               "<h2>{$prefix}</h2>";
    }

    /**
     * Get the error message suffix with custom content.
     *
     * @param  string $suffix Content to insert.
     * @return string Suffix for message.
     */
    public static function errorSuffix($suffix)
    {
        $suffix = preg_replace("/>\s+</", ">\n<", $suffix);
        $suffix = htmlspecialchars($suffix);

        return "<pre>{$suffix}</pre></div>";
    }
}
