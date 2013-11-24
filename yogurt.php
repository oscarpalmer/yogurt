<?php

namespace oscarpalmer;

set_error_handler("oscarpalmer\Yogurt::error_handler");

class Yogurt {
  # Settings for Yogurt.
  private static $settings = [
    "error_message" => "<!-- Sorry, check your syntax. -->",
    "no_partial"    => "<!-- Sorry, couldn't find that partial. -->",
    "partial_dir"   => "./"
  ];

  # Start a new parser/renderer.
  function __construct($settings = []) {
    foreach ($settings as $key => $value) {
      self::$settings[$key] = $value;
    }
  }

  # Render HTML.
  public static function render($__template, $__variables) {
    ob_start() && extract(get_object_vars(self::array_to_object($__variables)));
    eval("?>" . self::parse($__template));
    return ob_get_clean();
  }

  # Parse template.
  private static function parse($template) {
    $template = self::parse_ifs($template);
    $template = self::parse_foreach($template);
    $template = self::parse_includes($template);
    $template = self::parse_variables($template);

    return $template;
  }

  # Parse foreach-loops.
  private static function parse_foreach($template) {
    preg_match_all("/<!--\s*(\\$[\w\-\.]+?)[\s\S]+?\g{1};\s*-->/", $template, $matches);

    foreach ($matches[0] as $match) {
      preg_match("/<!--\s*(\\$[\w\-\.]+?)\s*?:\s*?(\\$[\w\-\.]+?)\s*-->([\s\S]+?)<!--\s*\g{1};\s*-->/", $match, $info);

      if (!empty($info)) {
        $key   = self::dotkey_to_objkey($info[1]);
        $value = $info[2];
        $block = $info[3];

        $template = str_replace($match, "<?php foreach ($key as $value): ?>$block<?php endforeach; ?>", $template);
      } else {
        $template = str_replace($match, self::$settings["error_message"], $template);
      }
    }

    return $template;
  }

  # Parse if-statements.
  private static function parse_ifs($template) {
    $template = preg_replace("/<!--\s*else\s*-->/", "<?php else: ?>", $template);

    $template = self::parse_if_statements($template, [
      "/<!--\s*if[\s\S]+?endif\s*-->/",
      "/<!--\s*else\s*if[\s\S]+?-->/"
    ], [[
      "/<!--\s*if\s*(\\$[\w\-\.]+?)\s*-->([\s\S]+?)<!--\s*endif\s*-->/",
      "/<!--\s*if\s*(\\$[\w\-\.]+?)\s*(is|isnt)\s*(\\$[\w\-\.]+?|\"[\s\S]+?\")\s*-->([\s\S]+?)<!--\s*endif\s*-->/"
    ],[
      "/<!--\s*else\s*if\s*(\\$[\w\-\.]+?)\s*-->/",
      "/<!--\s*else\s*if\s*(\\$[\w\-\.]+?)\s*(is|isnt)\s*(\\$[\w\-\.]+?|\"[\s\S]+?\")\s*-->/"
    ]]);

    return $template;
  }

  # Actually, this will parse if-statements.
  private static function parse_if_statements($template, $global, $regex) {
    foreach ($global as $index => $pattern) {
      preg_match_all($pattern, $template, $matches);

      $zero  = $index == 0;
      $open  = $zero ? "if" : "elseif";
      $close = $zero ? "<?php endif; ?>" : "";

      foreach ($matches[0] as $match) {
        preg_match($regex[$index][0], $match, $exists);
        preg_match($regex[$index][1], $match, $operator);

        if (empty($exists) && empty($operator)) {
          $template = str_replace($match, self::$settings["error_message"], $template);
        } else {
          $empty = empty($exists);

          $key   = self::dotkey_to_objkey($empty ? $operator[1] : $exists[1]);
          $opera = $empty ? ($operator[2] == "is" ? "==" : "!=") : "";
          $value = $empty ? $operator[3] : "";
          $block = $empty ? ($zero ? $operator[4] : "") : ($zero ? $exists[2] : "");

          $replacement = $empty ? "$key $opera $value" : "isset($key)";

          $template = str_replace($match, "<?php $open ($replacement): ?>$block$close", $template);
        }
      }
    }

    return $template;
  }

  # Parse includes.
  private static function parse_includes($template) {
    preg_match_all("/<!--\s*include\s*.+?\s*-->/", $template, $matches);

    foreach ($matches[0] as $match) {
      $file = preg_replace("/<!--\s*include\s*(.+?)\s*-->/", "$1", $match);
      $file = self::$settings["partial_dir"] . $file;

      $template = str_replace($match, self::parse(@file_get_contents($file)), $template);
    }

    return $template;
  }

  # Parse variables.
  private static function parse_variables($template) {
    preg_match_all("/<!--\s*\\$[\w\-\.]+?\s*-->/", $template, $matches);

    foreach ($matches[0] as $match) {
      $key = preg_replace("/<!--\s*(\\$[\w\-\.]+?)\s*-->/", "$1", $match);
      $key = self::dotkey_to_objkey($key);

      $template = str_replace($match, "<?php echo($key); ?>", $template);
    }

    return $template;
  }

  # Convert array to object.
  private static function array_to_object($array) {
    $obj = new \stdClass;

    foreach ($array as $key => $value) {
      if (strlen($key)) {
        if (is_array($value)) {
          $obj->{$key} = self::array_to_object($value);
        } else {
          $obj->{$key} = $value;
        }
      }
    }

    return $obj;
  }

  # Convert dot notation key to object key.
  private static function dotkey_to_objkey($key) {
    return str_replace(".", "->", $key);
  }

  # Custom errors for Yogurt.
  public static function error_handler($number, $string, $file, $line, $variables) {
    if (strpos($file, __FILE__) === 0) {
      echo "<!-- Sorry, something went wrong. PHP says \"$string\" can be found on line $line. -->\n";
    } else {
      return false;
    }
  }
}