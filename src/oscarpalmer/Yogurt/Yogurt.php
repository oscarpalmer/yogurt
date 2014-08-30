<?php

namespace oscarpalmer\Yogurt;

/**
 * Yogurt, the main engine.
 */
class Yogurt
{
    /**
     * @var string Current version number.
     */
    const VERSION = "1.1.0";

    /**
     * @var Dairy Dairy, the parser.
     */
    protected $dairy;

    /**
     * @var array Settings for Yogurt.
     */
    protected $settings;

    /**
     * Create new Yogurt.
     *
     * @param string $directory Valid directory name.
     * @param string $extension Extension name.
     */
    public function __construct($directory = null, $extension = null)
    {
        $this->setDirectory($directory ?: ".");
        $this->setExtension($extension ?: "html");

        $this->dairy = new Dairy($this->settings);
    }

    /** Public functions. */

    /**
     * Create a new flavour (template).
     *
     * @param  string  $flavour Name of flavour.
     * @return Flavour Our new flavour.
     */
    public function flavour($flavour)
    {
        if (is_string($flavour)) {
            return new Flavour($this, $flavour);
        }

        throw new \InvalidArgumentException("Flavour name must be a string, " . gettype($flavour) . " given.");
    }

    /**
     * Get Dairy, the parser.
     *
     * @return Dairy The parser object.
     */
    public function getDairy()
    {
        return $this->dairy;
    }

    /**
     * Get the settings for Yogurt.
     *
     * @return array Array of Settings.
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set directory.
     *
     * @param  string $directory Valid directory name.
     * @return Yogurt Yogurt object for optional chaining.
     */
    public function setDirectory($directory)
    {
        if (is_string($directory) === false) {
            throw new \InvalidArgumentException("Directory name must be a string, " . gettype($directory) . " given.");
        }

        $directory = rtrim($directory, "/");

        if (is_dir($directory)) {
            $this->settings["directory"] = $directory;

            return $this;
        }

        throw new \LogicException("The directory {$directory} does not exist.");
    }

    /**
     * Set extension name.
     *
     * @param  string $extension Extension name.
     * @return Yogurt Yogurt object for optional chaining.
     */
    public function setExtension($extension)
    {
        if (is_string($extension)) {
            $this->settings["extension"] = ltrim($extension, ".");

            return $this;
        }

        throw new \InvalidArgumentException("Extension name must be a string, " . gettype($extension) . " given.");
    }
}
