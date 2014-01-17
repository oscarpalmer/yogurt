<?php

namespace oscarpalmer\Yogurt\Test;

use oscarpalmer\Yogurt\Dairy;
use oscarpalmer\Yogurt\Flavour;
use oscarpalmer\Yogurt\Yogurt;

class YogurtTest extends \PHPUnit_Framework_TestCase
{
    # Mock variables.
    protected $mock_directory;
    protected $mock_yogurt;

    public function setUp()
    {
        # Valid directory.
        $this->mock_directory = __DIR__ . "/../../../assets";

        # Mock Yogurt with mock settings.
        $this->mock_yogurt = new Yogurt($this->mock_directory);
    }

    public function testConstructor()
    {
        $yogurt = $this->mock_yogurt;

        # Proper Yogurt object.
        $this->assertNotNull($yogurt);
        $this->assertInstanceOf("oscarpalmer\Yogurt\Yogurt", $yogurt);
    }

    public function testFlavour()
    {
        $yogurt = $this->mock_yogurt;

        $flavour = $yogurt->flavour("simple");

        # Proper Flavour object.
        $this->assertNotNull($flavour);
        $this->assertInstanceOf("oscarpalmer\Yogurt\Flavour", $flavour);
    }

    public function testFlavourError()
    {
        $yogurt = $this->mock_yogurt;

        # Mock and invalid variables.
        $flavour_1 = 1234;
        $flavour_2 = "not_a_flavour";

        foreach (array($flavour_1, $flavour_2) as $flavour) {
            try {
                $flavour_test = $yogurt->flavour($flavour);
            } catch (\Exception $e) {
                # Proper Exception object.
                $this->assertNotNull($e);
                $this->assertInstanceOf("Exception", $e);
            }
        }
    }

    public function testGetDairy()
    {
        $yogurt = $this->mock_yogurt;

        # Proper Dairy object.
        $this->assertNotNull($yogurt->getDairy());
        $this->assertInstanceOf("oscarpalmer\Yogurt\Dairy", $yogurt->getDairy());
    }

    public function testGetSettings()
    {
        $yogurt = $this->mock_yogurt;
        $settings = $yogurt->getSettings();

        # Test that our directory was set and that the default extension is set.
        $this->assertSame($this->mock_directory, $settings["directory"]);
        $this->assertSame("html", $settings["extension"]);
    }

    public function testSetDirectory()
    {
        $yogurt = $this->mock_yogurt;

        $dir = "./..";

        $yogurt->setDirectory($dir);

        $settings = $yogurt->getSettings();

        # Newly set directory was successfully set.
        $this->assertSame($dir, $settings["directory"]);
    }

    public function testSetDirectoryError()
    {
        $yogurt = $this->mock_yogurt;
        $settings = $yogurt->getSettings();

        $old_dir = $settings["directory"];

        # Mock and invalid variables.
        $dir_1 = 1234;
        $dir_2 = "not_a_directory";

        foreach (array($dir_1, $dir_2) as $dir) {
            try {
                $yogurt->setDirectory($dir);
            } catch (\Exception $e) {
                # Proper Exception object.
                $this->assertNotNull($e);
                $this->assertInstanceOf("Exception", $e);
            }
        }

        $settings = $yogurt->getSettings();

        $this->assertSame($old_dir, $settings["directory"]);
    }

    public function testSetExtension()
    {
        $yogurt = $this->mock_yogurt;

        $ext = "new-extension";

        $yogurt->setExtension($ext);

        $settings = $yogurt->getSettings();

        # Newly set directory was successfully set.
        $this->assertSame($ext, $settings["extension"]);
    }

    public function testSetExtensionError()
    {
        $yogurt = $this->mock_yogurt;
        $settings = $yogurt->getSettings();

        $old_ext = $settings["extension"];

        try {
            $yogurt->setExtension(1234); # Invalid extension name.
        } catch (\Exception $e) {
            # Proper Exception object.
            $this->assertNotNull($e);
            $this->assertInstanceOf("Exception", $e);
        }

        $settings = $yogurt->getSettings();

        $this->assertSame($old_ext, $settings["extension"]);
    }
}
