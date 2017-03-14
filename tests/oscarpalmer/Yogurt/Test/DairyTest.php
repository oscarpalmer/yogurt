<?php

namespace oscarpalmer\Yogurt\Test;

use oscarpalmer\Yogurt\Dairy;
use oscarpalmer\Yogurt\Dairy\Foreachs;
use oscarpalmer\Yogurt\Dairy\Ifs;
use oscarpalmer\Yogurt\Dairy\Includes;
use oscarpalmer\Yogurt\Dairy\Modifiers;
use oscarpalmer\Yogurt\Dairy\Variables;
use oscarpalmer\Yogurt\Yogurt;
use oscarpalmer\Yogurt\Exception\Syntax;

class DairyTest extends \PHPUnit\Framework\TestCase
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
        $template = file_get_contents("{$this->directory}/errors.html");

        foreach (array(
            "oscarpalmer\Yogurt\Dairy\Foreachs",
            "oscarpalmer\Yogurt\Dairy\Ifs",
            "oscarpalmer\Yogurt\Dairy\Includes",
            "oscarpalmer\Yogurt\Dairy\Modifiers",
            "oscarpalmer\Yogurt\Dairy\Variables"
        ) as $class) {
            try {
                $parser = new $class($this->dairy, $template);
                $parser->parse();
            } catch (\Exception $e) {
                $this->assertInstanceOf("oscarpalmer\Yogurt\Exception\Syntax", $e);
            }
        }

        try {
            $parser = new Includes(
                $this->dairy,
                file_get_contents("{$this->directory}/includes_error.html")
            );

            $parser->parse();
        } catch (\Exception $e) {
            $this->assertInstanceOf("LogicException", $e);
        }
    }

    public function testParseForeachs()
    {
        $dairy = new Foreachs(
            $this->dairy,
            file_get_contents("{$this->directory}/foreachs.html")
        );

        echo($dairy->parse());

        $this->expectOutputString(
            "<?php foreach(\$items as \$items_index => \$item): ?>\n" .
            "<!-- item -->\n" .
            "<?php endforeach; ?>"
        );
    }

    public function testParseIfs()
    {
        $dairy = new Ifs(
            $this->dairy,
            file_get_contents("{$this->directory}/ifs.html")
        );

        echo($dairy->parse());

        $this->expectOutputString(
            "<?php if(isset(\$title)): ?><!-- body --><?php endif; ?>\n" .
            "<?php if(\$title == \"A\"): ?>A" .
            "<?php elseif(\$title === \"B\"): ?>B" .
            "<?php else: ?>C<?php endif; ?>"
        );
    }

    public function testParseIncludes()
    {
        $dairy = new Includes(
            $this->dairy,
            file_get_contents("{$this->directory}/includes.html")
        );

        echo($dairy->parse());

        $this->expectOutputString(
            "<?php if(\$title == \"A\"): ?>A" .
            "<?php else: ?>!A<?php endif; ?>"
        );
    }

    public function testParseModifiers()
    {
        $dairy = new Modifiers(
            $this->dairy,
            file_get_contents("{$this->directory}/modifiers.html")
        );

        echo($dairy->parse());

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
        $dairy = new Variables(
            $this->dairy,
            file_get_contents("{$this->directory}/simple.html")
        );

        echo($dairy->parse());

        $this->expectOutputString(
            "{$this->htmlsc_start}\$title{$this->htmlsc_end}); ?>; " .
            "{$this->htmlsc_start}\$object->title{$this->htmlsc_end}); ?>"
        );
    }

    /** Static functions. */

    public function testDisplayErrorMessage()
    {
        $dairy = $this->dairy;

        $template = "{$this->directory}/errors.html";
        $error = "<!-- for error -->This is a foreach error.<!-- endfor -->";

        $prefix = "<div style=\"padding:0 1em;border:.5em solid red;font-size:1em;font-weight:normal\"><h2>Syntax error!</h2>";
        $middle = "<p>The syntax below is invalid and can be found in the template file <code>{$template}</code>.</p>";
        $suffix = "<pre>" . htmlspecialchars($error) . "</pre></div>";

        $dairy->parse($template);
        $this->expectOutputString($prefix . $middle . $suffix);
    }

    public function testGetObjectKey()
    {
        $this->assertSame("\$this->is->a->key", Foreachs::getObjectKey("this.is.a.key"));
        $this->assertSame("\$so{0}->is{0}->this", Foreachs::getObjectKey("so.0.is.0.this"));
    }

    public function testGetOperator()
    {
        # Regular comparison operators.
        foreach (array("===", "==", "!==", "!=", ">=", "<=", "<>", ">", "<") as $operator) {
            $this->assertSame(" {$operator} ", Foreachs::getOperator($operator));
        }

        # Custom operators.
        $this->assertSame(" == ", Foreachs::getOperator("is"));
        $this->assertSame(" != ", Foreachs::getOperator("isnt"));
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
            $this->assertSame($items[1], Foreachs::getValue($items[0]));
        }

        # Regular strings.
        $this->assertSame("\"this is a string value\"", Foreachs::getValue("\"this is a string value\""));
        $this->assertSame("'and another'", Foreachs::getValue("'and another'"));

        # Keys and variables.
        $this->assertSame("\$this->is->a->key", Foreachs::getValue("this.is.a.key"));
        $this->assertSame("\$so{0}->is{0}->this", Foreachs::getValue("so.0.is.0.this"));
    }

    public function testIncludesErrorMessage()
    {
        $dairy = $this->dairy;

        $template = "{$this->directory}/includes_error.html";
        $error = "<!-- include not_a_file.html -->";

        $prefix = "<div style=\"padding:0 1em;border:.5em solid red;font-size:1em;font-weight:normal\"><h2>Error!</h2>";
        $middle = "<p>The file <code>{$this->directory}/not_a_file.html</code> could not be found when included from the template file <code>{$template}</code>.</p>";
        $suffix = "<pre>" . htmlspecialchars($error) . "</pre></div>";

        $dairy->parse($template);
        $this->expectOutputString($prefix . $middle . $suffix);
    }
}
