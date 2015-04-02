<?php

namespace oscarpalmer\Yogurt\Test;

use oscarpalmer\Yogurt\Dairy;
use oscarpalmer\Yogurt\Yogurt;
use oscarpalmer\Yogurt\Exception\Syntax;

class DairyTest extends \PHPUnit_Framework_TestCase
{
    # Mock variables.
    protected $directory;
    protected $dairy;

    public function setUp()
    {
        $this->directory = __DIR__ . "/../../../assets";

        $this->dairy = new Dairy(array(
            # These are usually passed on by Yogurt.
            "directory" => $this->directory,
            "extension" => "html"
        ));

        $this->htmlsc_start = "<?php echo(htmlspecialchars(";
        $this->htmlsc_end = ", \ENT_QUOTES | \ENT_SUBSTITUTE, \"utf-8\")";
    }

    public function testConstructor()
    {
        $dairy = $this->dairy;

        # Proper Dairy object.
        $this->assertInstanceOf("oscarpalmer\Yogurt\Dairy", $dairy);
    }

    public function testModifiers()
    {
        $functions = array(
            "dump" => array("var_dump(", ")"),
            "escape" => array("htmlspecialchars(", $this->htmlsc_end),
            "json" => array("json_encode(", ")"),
            "lowercase" => array("mb_strtolower(", ", \"utf-8\")"),
            "trim" => array("trim(", ")"),
            "uppercase" => array("mb_strtoupper(", ", \"utf-8\")")
        );

        foreach ($functions as $name => $function) {
            $this->assertSame($function, $this->dairy->getModifierFunction($name));
        }
    }

    public function testErrors()
    {
        $dairy = $this->dairy;
        $template = file_get_contents("{$this->directory}/errors.html");

        foreach (array("parseForeachs", "parseIfs", "parseIncludes", "parseModifiers") as $method) {
            try {
                $dairy->$method($template);
            } catch (Syntax $e) {
                $this->assertInstanceOf("oscarpalmer\Yogurt\Exception\Syntax", $e);
            }
        }

        try {
            $dairy->parseIncludes(file_get_contents("{$this->directory}/includes_error.html"));
        } catch (\Exception $e) {
            $this->assertInstanceOf("LogicException", $e);
        }
    }

    public function testParseForeachs()
    {
        $dairy = $this->dairy;

        $template = file_get_contents("{$this->directory}/foreachs.html");
        $template = $dairy->parseForeachs($template);

        echo($template);

        $this->expectOutputString(
            "<?php foreach(\$items as \$items_index => \$item): ?>\n" .
            "<!-- item -->\n" .
            "<?php endforeach; ?>"
        );
    }

    public function testParseIfs()
    {
        $dairy = $this->dairy;

        # Read and parse the file.
        $template = file_get_contents("{$this->directory}/ifs.html");
        $template = $dairy->parseIfs($template);

        echo($template);

        $this->expectOutputString(
            "<?php if(isset(\$title)): ?><!-- body --><?php endif; ?>\n" .
            "<?php if(\$title == \"A\"): ?>A" .
            "<?php elseif(\$title === \"B\"): ?>B" .
            "<?php else: ?>C<?php endif; ?>"
        );
    }

    public function testParseIncludes()
    {
        $dairy = $this->dairy;

        $template = file_get_contents("{$this->directory}/includes.html");
        $template = $dairy->parseIncludes($template);

        echo($template);

        $this->expectOutputString(
            "<?php if(\$title == \"A\"): ?>A" .
            "<?php else: ?>!A<?php endif; ?>"
        );
    }

    public function testParseModifiers()
    {
        $dairy = $this->dairy;

        $template = file_get_contents("{$this->directory}/modifiers.html");
        $template = $dairy->parseModifiers($template);

        echo($template);

        $this->expectOutputString(
            "<?php echo(var_dump(\$variable)); ?>\n" .
            "{$this->htmlsc_start}\$variable{$this->htmlsc_end}); ?>\n" .
            "<?php echo(json_encode(\$variable)); ?>\n" .
            "<?php echo(mb_strtolower(\$variable, \"utf-8\")); ?>\n" .
            "<?php echo(\$variable); ?>\n" .
            "<?php echo(trim(\$variable)); ?>\n" .
            "<?php echo(trim(\$variable)); ?>\n" .
            "<?php echo(mb_strtoupper(\$variable, \"utf-8\")); ?>"
        );
    }

    public function testParseVariables()
    {
        $dairy = $this->dairy;

        $template = file_get_contents("{$this->directory}/simple.html");
        $template = $dairy->parseVariables($template);

        echo($template);

        $this->expectOutputString(
            "{$this->htmlsc_start}\$title{$this->htmlsc_end}); ?>; " .
            "{$this->htmlsc_start}\$object->title{$this->htmlsc_end}); ?>"
        );
    }

    /** Static functions. */

    /**
     * @covers oscarpalmer\Yogurt\Dairy::parse
     * @covers oscarpalmer\Yogurt\Dairy::displaySyntaxErrorMessage
     */
    public function testDisplaySyntaxErrorMessage()
    {
        $dairy = $this->dairy;

        $template = "{$this->directory}/errors.html";
        $error = "<!-- for error -->This is a foreach error.<!-- endfor -->";

        $prefix = "<div style=\"padding:0 1em;border:.5em solid red;font-size:1em;font-weight:normal\"><p><b>Syntax error!</b></p>";
        $middle = "<p>The syntax below is invalid and can be found in the template file <code>{$template}</code>.</p>";
        $suffix = "<pre>" . htmlspecialchars($error) . "</pre>\n</div>";

        $dairy->parse($template);
        $this->expectOutputString("{$prefix}\n{$middle}\n{$suffix}");
    }

    public function testGetObjectKey()
    {
        $this->assertSame("\$this->is->a->key", Dairy::getObjectKey("this.is.a.key"));
        $this->assertSame("\$so{0}->is{0}->this", Dairy::getObjectKey("so.0.is.0.this"));
    }

    public function testGetOperator()
    {
        # Regular comparison operators.
        foreach (array("===", "==", "!==", "!=", ">=", "<=", "<>", ">", "<") as $operator) {
            $this->assertSame(" {$operator} ", Dairy::getOperator($operator));
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
        $this->assertSame("\$so{0}->is{0}->this", Dairy::getValue("so.0.is.0.this"));
    }
}
