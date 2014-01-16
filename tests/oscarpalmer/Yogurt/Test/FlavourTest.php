<?php

namespace oscarpalmer\Yogurt\Test;

use oscarpalmer\Yogurt\Flavour;
use oscarpalmer\Yogurt\Yogurt;

class FlavourTest extends \PHPUnit_Framework_TestCase
{
    # Mock variables.
    protected $mock_data;
    protected $mock_flavour;

    public function setUp()
    {
        $this->mock_data = array(
            # Settings; usually passed on by Yogurt.
            "body" => "<p>Tests for Yogurt.</p>",
            "title" => "Yogurt Tests"
        );

        $this->mock_flavour = new Flavour(new Yogurt(__DIR__ . "/../../../assets"), "simple");
    }

    public function testConstructor()
    {
        $flavour = $this->mock_flavour;

        # Proper Flavour object.
        $this->assertNotNull($flavour);
        $this->assertInstanceOf("oscarpalmer\Yogurt\Flavour", $flavour);
    }

    public function testToString()
    {
        $flavour = $this->mock_flavour;
        $flavour->data($this->mock_data);

        echo $flavour;

        # We know what to expect.
        $this->expectOutputString("Yogurt Tests");
    }

    public function testData()
    {
        $flavour = $this->mock_flavour;

        $data = $flavour->data();

        # Data is empty array by default.
        $this->assertNotNull($data);
        $this->assertEmpty($data);

        $flavour->data($this->mock_data);
        $data = $flavour->data();

        # Our data was successfully set.
        $this->assertCount(2, $data);
        $this->assertSame($this->mock_data["body"], $data["body"]);
        $this->assertSame($this->mock_data["title"], $data["title"]);
    }

    public function testGetDataObject()
    {
        $flavour = $this->mock_flavour;

        $flavour->data($this->mock_data);

        $dataObject = $flavour->getDataObject();
    }

    public function testGetFilename()
    {
        $flavour = $this->mock_flavour;

        $this->assertFileExists($flavour->getFilename());
    }

    public function testSetFilename()
    {
        $flavour = $this->mock_flavour;
        $template = "foreachs";

        $flavour->setFilename($template);

        $this->assertFileExists($flavour->getFilename());
    }

    public function testSetFilenameError()
    {
        $flavour = $this->mock_flavour;

        # Mock and invalid variables.
        $filename_1 = 1234;
        $filename_2 = "not_a_filename";

        foreach (array($filename_1, $filename_2) as $filename) {
            try {
                $flavour->setFilename($filename);
            } catch (\Exception $e) {
                # Valid exception object.
                $this->assertNotNull($e);
                $this->assertInstanceOf("Exception", $e);
            }
        }
    }

    public function testTaste()
    {
        $flavour = $this->mock_flavour;

        echo $flavour->taste($this->mock_data);

        # We know what to expect.
        $this->expectOutputString("Yogurt Tests");
    }

    /** Testing static functions. */

    public function testArrayToObject()
    {
        $data = $this->mock_data;

        $object = Flavour::arrayToObject($data);

        # Valid object.
        $this->assertNotNull($object);
        $this->assertInstanceOf("stdClass", $object);
    }

    public function testErrorHandler()
    {
        # Needed because the error handler only handles Flavour's errors.
        $flavour = $this->mock_flavour;
        $flavour_file = (new \ReflectionClass(get_class($flavour)))->getFilename();

        # Custom error.
        $error = Flavour::errorHandler(0, "Error handler", $flavour_file, 1, array());

        # Error should be displayed like this:
        $this->expectOutputString("<div style=\"padding:0 1em;border:.5em solid red;font-size:1rem;font-weight:normal\"><p>Error handler on line <code>1</code> in your template.</p></div>");
    }
}
