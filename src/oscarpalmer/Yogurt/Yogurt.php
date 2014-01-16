<?php

namespace oscarpalmer\Yogurt;

use oscarpalmer\Yogurt\Dairy;
use oscarpalmer\Yogurt\Flavour;

class Yogurt
{
    /**
     * Dairy, our parser.
     *
     * @access public
     */
    protected $dairy;

    /**
     * Settings for Yogurt.
     *
     * @access public
     */
    protected $settings;

    /**
     * Constructor.
     *
     * Create new Yogurt.
     *
     * @access public
     * @param string $directory Valid directory name.
     * @param string $extension Extension name.
     */
    public function __construct($directory = null, $extension = null)
    {
        $this->setDirectory($directory ?: ".");
        $this->setExtension($extension ?: "html");

        $this->dairy = new Dairy($this->settings);
    }

    /**
     * Create a new flavour (template).
     *
     * @access public
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
     * Get Dairy, our parser.
     *
     * @access public
     * @return Dairy The parser object.
     */
    public function getDairy()
    {
        return $this->dairy;
    }

    /**
     * Get the settings for Yogurt.
     *
     * @access public
     * @return array Settings.
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set directory.
     *
     * @access public
     * @param string $directory Valid directory name.
     */
    public function setDirectory($directory)
    {
        if (!is_string($directory)) {
            throw new \InvalidArgumentException("Directory name must be a string, " . gettype($directory) . " given.");
        }

        $directory = rtrim($directory, "/");

        try {
            if (is_dir($directory)) {
                $this->settings["directory"] = $directory;

                return $this;
            }

            throw new \LogicException("The directory $directory does not exist.");
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Set extension name.
     *
     * @access public
     * @param string $extension Extension name.
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
