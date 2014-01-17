<?php

namespace oscarpalmer\Yogurt;

/**
 * Flavour, the template object.
 */
class Flavour
{
    /**
     * Data to render.
     *
     * @access public
     */
    protected $data = array();

    /**
     * Filename for template.
     *
     * @access public
     */
    protected $filename;

    /**
     * Our Yogurt.
     *
     * @access public
     */
    protected $yogurt;

    /**
     * Constructor.
     *
     * Create a new Flavour with Yogurt and flavour name.
     *
     * @access public
     * @param Yogurt $yogurt Yogurt for settings and parsing.
     * @param string $name   Flavour (template) name.
     */
    public function __construct(Yogurt $yogurt, $name)
    {
        $this->yogurt = $yogurt;

        $this->setFilename($name);
    }

    /**
     * Return rendered template on direct access of Flavour object.
     *
     * @access public
     */
    public function __toString()
    {
        return $this->taste();
    }

    /**
     * Add data to flavour.
     *
     * @access public
     * @param  array $data Data to add.
     * @return array All the data.
     */
    public function data(array $data = null)
    {
        if (isset($data)) {
            foreach ($data as $key => $value) {
                $this->data[$key] = $value;
            }
        }

        return $this->data;
    }

    /**
     * Array to object and then object to associative array.
     *
     * @access public
     * @return array Array of data.
     */
    public function getDataObject()
    {
        $data = $this->data;
        $data = static::arrayToObject($data);
        $data = get_object_vars($data);

        return $data;
    }

    /**
     * Get current filename.
     *
     * @access public
     * @return string Filename.
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set the filename.
     *
     * @access public
     * @param string $name Filename to set.
     */
    public function setFilename($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException("Filename must be a string, " . gettype($name) . " given.");
        }

        $settings = $this->yogurt->getSettings();
        $filename = $settings["directory"] . "/$name";

        if (!preg_match("/\.{$settings['extension']}\z/", $filename)) {
            $filename .= ".{$settings['extension']}";
        }

        if (is_file($filename)) {
            $this->filename = $filename;

            return $this;
        }

        throw new \LogicException("The template {$filename} does not exist.");
    }

    /**
     * Render the template.
     *
     * @access public
     * @param  array  $data More data to add.
     * @return string The rendered template.
     */
    public function taste(array $data = null)
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

    /** Static functions. */

    /**
     * Convert array to object.
     *
     * @access public
     * @param  array  $array Array to convert.
     * @return object Converted array.
     */
    public static function arrayToObject(array $array)
    {
        $object = new \stdClass;

        foreach ($array as $key => $value) {
            if (strlen($key)) {
                if (is_array($value)) {
                    $object->{$key} = static::arrayToObject($value);
                } else {
                    $object->{$key} = $value;
                }
            }
        }

        return $object;
    }

    /**
     * Handle errors for the eval-function. Passes on the error if it's not a local error.
     *
     * @access public
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

        $prefix = "<div style=\"padding:0 1em;border:.5em solid red;font-size:1rem;font-weight:normal\"><p>";
        $suffix = " on line <code>$line</code> in your template.</p></div>";

        echo("{$prefix}{$string}{$suffix}");

        return true;
    }
}
