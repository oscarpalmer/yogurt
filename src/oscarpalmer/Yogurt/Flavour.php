<?php

namespace oscarpalmer\Yogurt;

/**
 * Flavour, the template object.
 */
class Flavour
{
    /**
     * @var array Data to render.
     */
    protected $data;

    /**
     * @var string Filename for template.
     */
    protected $filename;

    /**
     * @var Yogurt Our Yogurt.
     */
    protected $yogurt;

    /**
     * Create a new Flavour with Yogurt and flavour name.
     *
     * @param Yogurt $yogurt Yogurt for settings and parsing.
     * @param string $name   Flavour (template) name.
     */
    public function __construct(Yogurt $yogurt, $name)
    {
        $this->yogurt = $yogurt;

        $this->setFilename($name);
    }

    /**
     * Set a new or change an existing data item magically.
     *
     * @param mixed $key   Key for item.
     * @param mixed $value Value for item.
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Return rendered template on direct access of Flavour object.
     */
    public function __toString()
    {
        return $this->taste();
    }

    /** Public functions. */

    /**
     * Add data to flavour.
     *
     * @param  array|object $data Data to add.
     * @return array        All the data.
     */
    public function data($data = null)
    {
        if (is_null($data) === false) {
            if (is_array($data) === false && is_object($data) === false) {
                throw new \InvalidArgumentException("Data must be either an array or an object.");
            }

            foreach ((object) $data as $key => $value) {
                $this->{$key} = $value;
            }
        }

        return $this->data;
    }

    /**
     * Get current filename.
     *
     * @return string Filename.
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set the filename.
     *
     * @param string $name Filename to set.
     */
    public function setFilename($name)
    {
        if (is_string($name)) {
            $settings = $this->yogurt->getSettings();
            $filename = $settings["directory"] . "/$name";

            if (!preg_match("/\.{$settings['extension']}\z/", $filename)) {
                $filename .= ".{$settings['extension']}";
            }

            if (is_file($filename)) {
                $this->filename = $filename;

                return $this;
            }

            throw new \LogicException("The template \"{$filename}\" does not exist.");
        }

        throw new \InvalidArgumentException(
            "Filename must be a string, \"" .
            gettype($name) .
            "\" given."
        );
    }

    /**
     * Render the template.
     *
     * @param  array|object $data More data to add.
     * @return string       The rendered template.
     */
    public function taste($data = null)
    {
        $data = $this->data($data);
        $data = $this->getDataObject();

        $template = $this->yogurt->getDairy()->parse($this->filename);

        ob_start();

        extract($data);
        unset($data);

        set_error_handler("static::errorHandler");
        eval("?>{$template}");
        restore_error_handler();

        return ob_get_clean();
    }

    /** Protected functions. */

    /**
     * Array to object and then object to associative array.
     *
     * @return array|object Array or object of data.
     */
    public function getDataObject()
    {
        $data = $this->data;
        $data = static::itemToObject($data);
        $data = get_object_vars($data);

        return $data;
    }

    /** Static functions. */

    /**
     * Handle errors for the eval-function. Passes on the error if it's not a local error.
     *
     * @param  int     $number    Error level.
     * @param  string  $string    Error message.
     * @param  string  $file      Error file.
     * @param  int     $line      Error line.
     * @param  array   $variables Error variables.
     * @return boolean True if handled.
     */
    public static function errorHandler($number, $string, $file, $line, $variables)
    {
        if (strpos($file, __FILE__) !== 0) {
            return false;
        }

        $prefix = "<div style=\"padding:0 1em;border:.5em solid red;";
        $prefix .= "font-size:1em;font-weight:normal\"><p>";
        $suffix = " on line <code>{$line}</code> in your template.</p></div>";

        echo("{$prefix}{$string}{$suffix}");

        return true;
    }

    /**
     * Convert an item (array or object) to object.
     *
     * @param  array  $item item to convert.
     * @return object Converted item.
     */
    public static function itemToObject($item)
    {
        return json_decode(json_encode($item), false);
    }
}
