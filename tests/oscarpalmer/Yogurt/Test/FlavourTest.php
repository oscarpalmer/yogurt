<?php

namespace oscarpalmer\Yogurt\Test;

use oscarpalmer\Yogurt\Flavour;
use oscarpalmer\Yogurt\Yogurt;

class FlavourTest extends \PHPUnit_Framework_TestCase
{
    # Mock variables.
    protected $data;
    protected $flavour;

    public function setUp()
    {
        $data = array(
            "body" => "<p>Tests for Yogurt.</p>",
            "title" => "Yogurt Tests"
        );

        $object = new \stdClass;
        $object->title = "Yogurt Tests, object";

        $data["object"] = $object;

        $this->data = $data;

        $this->flavour = new Flavour(new Yogurt(__DIR__ . "/../../../assets"), "simple");
    }

    public function testConstructor()
    {
        $flavour = $this->flavour;

        # Proper Flavour object.
        $this->assertNotNull($flavour);
        $this->assertInstanceOf("oscarpalmer\Yogurt\Flavour", $flavour);
    }

    public function testToString()
    {
        $flavour = $this->flavour;
        $flavour->data($this->data);

        $this->assertSame("Yogurt Tests; Yogurt Tests, object", (string) $flavour);
    }

    /**
     * @covers oscarpalmer\Yogurt\Flavour::__set
     * @covers oscarpalmer\Yogurt\Flavour::data
     */
    public function testData()
    {
        $flavour = $this->flavour;

        $data = $flavour->data();

        $this->assertNull($data);

        $flavour->data($this->data);
        $flavour->magic = "cool";
        $data = $flavour->data();

        $this->assertCount(4, $data);
        $this->assertSame($this->data["body"], $data["body"]);
        $this->assertSame($this->data["title"], $data["title"]);
        $this->assertSame("cool", $data["magic"]);

        try {
            $flavour->data(1);
        } catch (\Exception $e) {
            $this->assertInstanceOf("InvalidArgumentException", $e);
        }
    }

    public function testGetDataObject()
    {
        $flavour = $this->flavour;

        $flavour->data($this->data);

        $dataObject = $flavour->getDataObject();
    }

    public function testGetFilename()
    {
        $flavour = $this->flavour;

        $this->assertFileExists($flavour->getFilename());
    }

    public function testSetFilename()
    {
        $flavour = $this->flavour;
        $template = "foreachs";

        $flavour->setFilename($template);

        $this->assertFileExists($flavour->getFilename());
    }

    public function testSetFilenameError()
    {
        $flavour = $this->flavour;

        $filename_1 = 1234;
        $filename_2 = "not_a_filename";

        foreach (array($filename_1, $filename_2) as $filename) {
            try {
                $flavour->setFilename($filename);
            } catch (\Exception $e) {
                $this->assertInstanceOf("Exception", $e);
            }
        }
    }

    public function testTaste()
    {
        $flavour = $this->flavour;

        $this->assertSame("Yogurt Tests; Yogurt Tests, object", $flavour->taste($this->data));
    }

    /** Testing static functions. */

    public function testItemToObject()
    {
        $data = $this->data;
        $data["arr"] = array(1, 2, 3);

        $object = Flavour::itemToObject($data);

        # Valid object.
        $this->assertNotNull($object);
        $this->assertInstanceOf("stdClass", $object);
    }

    public function testErrorHandler()
    {
        # Needed because the error handler only handles Flavour's errors.
        $flavour = $this->flavour;
        $flavour_class = new \ReflectionClass(get_class($flavour));
        $flavour_file = $flavour_class->getFilename();

        # Custom error.
        Flavour::errorHandler(0, "Error handler", $flavour_file, 1, array());
        # Error should be displayed like this:
        $this->expectOutputString("<div style=\"padding:0 1em;border:.5em solid red;font-size:1em;font-weight:normal\"><p>Error handler on line <code>1</code> in your template.</p></div>");

        $this->assertFalse(Flavour::errorHandler(0, "Error handler", __FILE__, 1, array()));
    }
}
