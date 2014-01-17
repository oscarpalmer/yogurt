<?php

namespace oscarpalmer\Yogurt\Test;

use oscarpalmer\Yogurt\Dairy;
use oscarpalmer\Yogurt\Yogurt;

class DairyTest extends \PHPUnit_Framework_TestCase
{
    # Mock variables.
    protected $mock_directory;
    protected $mock_dairy;

    public function setUp()
    {
        $this->mock_dairy = new Dairy(array(
            # These are usually passed on by Yogurt.
            "directory" => __DIR__ . "/../../../assets",
            "extension" => "html"
        ));

        # Valid directory.
        $this->mock_directory = __DIR__ . "/../../../assets";
    }

    public function testConstructor()
    {
        $dairy = $this->mock_dairy;

        # Proper Dairy object.
        $this->assertNotNull($dairy);
        $this->assertInstanceOf("oscarpalmer\Yogurt\Dairy", $dairy);
    }

    public function testParseForeachs()
    {
        $dairy = $this->mock_dairy;

        # Read and parse the file.
        $template = file_get_contents($this->mock_directory . "/foreachs.html");
        $template = $dairy->parseForeachs($template);

        echo $template;

        # We know what to expect.
        $this->expectOutPutString("<?php foreach(\$items as \$item): ?>\n<!-- item -->\n<?php endforeach; ?>");
    }

    public function testParseIfs()
    {
        $dairy = $this->mock_dairy;

        # Read and parse the file.
        $template = file_get_contents($this->mock_directory . "/ifs.html");
        $template = $dairy->parseIfs($template);

        echo $template;

        # We know what to expect.
        $this->expectOutPutString("<?php if(isset(\$title)): ?><!-- body --><?php endif; ?>\n<?php if(\$title == \"A\"): ?>A<?php elseif(\$title === \"B\"): ?>B<?php else: ?>C<?php endif; ?>");
    }

    public function testParseIncludes()
    {
        $dairy = $this->mock_dairy;

        # Read and parse the file.
        $template = file_get_contents($this->mock_directory . "/includes.html");
        $template = $dairy->parseIncludes($template);

        echo $template;

        # We know what to expect.
        $this->expectOutPutString("<?php if(\$title == \"A\"): ?>A<?php else: ?>!A<?php endif; ?>");
    }

    public function testParseVariables()
    {
        $dairy = $this->mock_dairy;

        # Read and parse the file.
        $template = file_get_contents($this->mock_directory . "/simple.html");
        $template = $dairy->parseVariables($template);

        echo $template;

        # We know what to expect.
        $this->expectOutPutString("<?php echo(\$title); ?>");
    }

    /** Testing static functions. */

    public function testDisplaySyntaxErrorMessage()
    {
        $filename = $this->mock_directory . "/simple.html";
        $message = "<!-- This is not a real syntax error. -->";

        Dairy::displaySyntaxErrorMessage(new \Exception($message), $filename);

        # We know what to expect.
        $prefix = "<div style=\"padding:0 1em;border:.5em solid red;font-size:1rem;font-weight:normal\"><p><b>Error!</b></p>";
        $middle = "<p>The syntax below is invalid and can be found in the template file <code>{$filename}</code>.</p>";
        $suffix = "<pre>" . htmlspecialchars($message) . "</pre>\n</div>";

        $this->expectOutputString("{$prefix}\n{$middle}\n{$suffix}");
    }

    public function testGetObjectKey()
    {
        $this->assertSame("\$this->is->a->key", Dairy::getObjectKey("this.is.a.key"));
        $this->assertSame("\$so->{0}->is->{0}->this", Dairy::getObjectKey("so.0.is.0.this"));
    }

    public function testGetOperator()
    {
        # Regular comparison operators.
        foreach (array("===", "==", "!==", "!=", ">=", "<=", "<>", ">", "<") as $operator) {
            $this->assertSame(" $operator ", Dairy::getOperator($operator));
        }

        # Custom operators.
        $this->assertSame(" == ", Dairy::getOperator("is"));
        $this->assertSame(" != ", Dairy::getOperator("isnt"));
    }

    public function testGetValue()
    {
        $booleans_integers = array(
            array("1234", "1234"),
            array("true", "true"),
            array("'5678'", "5678"),
            array("'false", "false"),
        );

        # Booleans and integers.
        foreach ($booleans_integers as $items) {
            $this->assertSame($items[1], Dairy::getValue($items[0]));
        }

        # Regular strings.
        $this->assertSame("\"this is a string value\"", Dairy::getValue("\"this is a string value\""));
        $this->assertSame("'and another'", Dairy::getValue("'and another'"));

        # Keys and variables.
        $this->assertSame("\$this->is->a->key", Dairy::getValue("this.is.a.key"));
        $this->assertSame("\$so->{0}->is->{0}->this", Dairy::getValue("so.0.is.0.this"));
    }
}
