<?php

namespace oscarpalmer\Yogurt\Test;

use oscarpalmer\Yogurt\Dairy;
use oscarpalmer\Yogurt\Flavour;
use oscarpalmer\Yogurt\Yogurt;

class YogurtTest extends \PHPUnit_Framework_TestCase
{
    # Mock variables.
    protected $directory;
    protected $yogurt;

    public function setUp()
    {
        $this->directory = __DIR__ . "/../../../assets";

        $this->yogurt = new Yogurt($this->directory);
    }

    public function testConstructor()
    {
        $yogurt = $this->yogurt;

        # Proper Yogurt object.
        $this->assertInstanceOf("oscarpalmer\Yogurt\Yogurt", $yogurt);
    }

    public function testFlavour()
    {
        $yogurt = $this->yogurt;

        $flavour = $yogurt->flavour("simple");

        # Proper Flavour object.
        $this->assertInstanceOf("oscarpalmer\Yogurt\Flavour", $flavour);
    }

    public function testFlavourError()
    {
        $yogurt = $this->yogurt;

        $flavour_1 = 1234;
        $flavour_2 = "not_a_flavour";

        foreach (array($flavour_1, $flavour_2) as $flavour) {
            try {
                $flavour_test = $yogurt->flavour($flavour);
            } catch (\Exception $e) {
                # Proper Exception object.
                $this->assertInstanceOf("Exception", $e);
            }
        }
    }

    public function testGetDairy()
    {
        $yogurt = $this->yogurt;

        # Proper Dairy object.
        $this->assertInstanceOf("oscarpalmer\Yogurt\Dairy", $yogurt->getDairy());
    }

    public function testGetSettings()
    {
        $yogurt = $this->yogurt;
        $settings = $yogurt->getSettings();

        # Test that our directory was set and that the default extension is set.
        $this->assertSame($this->directory, $settings["directory"]);
        $this->assertSame("html", $settings["extension"]);
    }

    public function testSetDirectory()
    {
        $yogurt = $this->yogurt;

        $dir = "./..";

        $yogurt->setDirectory($dir);

        $settings = $yogurt->getSettings();

        $this->assertSame($dir, $settings["directory"]);
    }

    public function testSetDirectoryError()
    {
        $yogurt = $this->yogurt;
        $settings = $yogurt->getSettings();

        $old_dir = $settings["directory"];

        $dir_1 = 1234;
        $dir_2 = "not_a_directory";

        foreach (array($dir_1, $dir_2) as $dir) {
            try {
                $yogurt->setDirectory($dir);
            } catch (\Exception $e) {
                # Proper Exception object.
                $this->assertInstanceOf("Exception", $e);
            }
        }

        $settings = $yogurt->getSettings();

        $this->assertSame($old_dir, $settings["directory"]);
    }

    public function testSetExtension()
    {
        $yogurt = $this->yogurt;

        $ext = "new-extension";

        $yogurt->setExtension($ext);

        $settings = $yogurt->getSettings();

        $this->assertSame($ext, $settings["extension"]);
    }

    public function testSetExtensionError()
    {
        $yogurt = $this->yogurt;
        $settings = $yogurt->getSettings();

        $old_ext = $settings["extension"];

        try {
            $yogurt->setExtension(1234); # Invalid extension name.
        } catch (\Exception $e) {
            # Proper Exception object.
            $this->assertInstanceOf("InvalidArgumentException", $e);
        }

        $settings = $yogurt->getSettings();

        $this->assertSame($old_ext, $settings["extension"]);
    }
}
