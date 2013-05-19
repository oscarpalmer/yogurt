<?php

set_error_handler("Yogurt::error_handler");

class Yogurt {
  # Settings for Yogurt
  private static $settings = array(
    "error_message" => "<!-- Sorry, check your syntax. -->",
    "no_partial"    => "<!-- Sorry, couldn't find that partial. -->",
    "partial_dir"   => "./"
  );

  # Class-accessible array of variables
  private static $variables;

  # Start a new parser/renderer
  function __construct($settings = array()) {
    foreach ($settings as $key => $value) {
      self::$settings[$key] = $value; }
  }

  # Render HTML
  public static function render($template, $variables) {
    if (empty(self::$settings)) {
      return; }

    self::$variables = self::array_to_object($variables);

    $template = self::parse($template, $variables);

    ob_start() and extract(get_object_vars(self::$variables));
    eval("?>" . $template);
    return ob_get_clean();
  }

  # Parse template
  private static function parse($template) {
    $template = self::parse_ifs($template);
    $template = self::parse_foreach($template);
    $template = self::parse_includes($template);
    $template = self::parse_variables($template);

    return $template;
  }

  # Parse foreach-loops
  private static function parse_foreach($template) {
    preg_match_all("/<!--\s+?foreach[\s\S]+?endforeach\s+?-->/", $template, $matches);

    foreach ($matches[0] as $match) {
      preg_match("/<!--\s+?foreach\s+?(\\$\S+?)\s+?as\s+?(\\$\S+?)\s+?-->([\s\S]+?)<!--\s+?endforeach\s+?-->/", $match, $info);

      if (!empty($info)) {
        $key = self::dotkey_to_objkey($info[1]);
        $template = str_replace($match, "<?php foreach ($key as {$info[2]}): ?>{$info[3]}<?php endforeach; ?>", $template); }
      else {
        $template = str_replace($match, self::$settings["error_message"], $template); } }

    return $template;
  }

  # Parse if-statements
  private static function parse_ifs($template) {
    $global = array(
      "if"     => "/<!--\s+?if[\s\S]+?endif\s+?-->/",
      "elseif" => "/<!--\s+?else\s+?if[\s\S]+?-->/"
    );

    $regex = array(
      "if" => array(
        "exists"   => "/<!--\s+?if\s+?(\\$\S+?)\s+?-->([\s\S]+?)<!--\s+?endif\s+?-->/",
        "operator" => "/<!--\s+?if\s+?(\\$\S+?)\s+?(.+?)\s+?(\\$\S+?|\"[\s\S]+?\")\s+?-->([\s\S]+?)<!--\s+?endif\s+?-->/"),
      "elseif" => array(
        "exists"   => "/<!--\s+?else\s+?if\s+?(\\$\S+?)\s+?-->/",
        "operator" => "/<!--\s+?else\s+?if\s+?(\\$\S+?)\s+?(.+?)\s+?(\\$\S+?|\"[\s\S]+?\")\s+?-->/"));

    $template = self::parse_if_statements($template, $global, $regex);
    $template = preg_replace("/<!--\s+?else\s+?-->/", "<?php else: ?>", $template);

    return $template;
  }

  # Help parse if-statements
  private static function parse_if_statements($template, $global, $regex) {
    foreach ($global as $name => $pattern) {
      preg_match_all($pattern, $template, $matches);

      $open  = $name;
      $close = $name == "if" ? "<?php endif; ?>" : "";

      foreach ($matches[0] as $match) {
        preg_match($regex[$name]["exists"], $match, $exists);
        preg_match($regex[$name]["operator"], $match, $operator);

        if (empty($exists) && empty($operator)) {
          $replacement = str_replace($match, self::$settings["error_message"], $template); }
        else if (!empty($exists)) {
          $key = self::dotkey_to_objkey($exists[1]);
          $blk = $name == "if" ? $exists[2] : "";

          $template = str_replace($match, "<?php $open (isset($key) && $key != null): ?>$blk$close", $template); }
        else {
          $key = self::dotkey_to_objkey($operator[1]);
          $opr = ($operator[2] == "is" || $operator[2] == "==") ? "==" : "!=";
          $val = strpos($operator[3], "$") === 0 ? self::dotkey_to_objkey($operator[3]) : $operator[3];
          $blk = $name == "if" ? $operator[4] : "";

          $template = str_replace($match, "<?php $open ($key $opr $val): ?>$blk$close", $template);
        }
      }
    }

    return $template;
  }

  # Parse includes
  private static function parse_includes($template) {
    $variables = get_object_vars(self::$variables);

    preg_match_all("/<!--\s+?include\s+?.+?\s+?-->/", $template, $matches);

    foreach ($matches[0] as $match) {
      $file = preg_replace("/<!--\s+?include\s+?(.+?)\s+?-->/", "$1", $match);

      if (strpos($file, "$") === 0) {
        $file = $variables[self::dotkey_to_objkey(str_replace("$", "", $file))]; }

      $file = self::$settings["partial_dir"] . $file;

      if (file_exists($file)) {
        $partial = file_get_contents($file); }
      else {
        $partial = self::$settings["no_partial"]; }

      $template = str_replace($match, $partial, $template);
      $template = self::parse($template); }

    return $template;
  }

  # Parse variables
  private static function parse_variables($template) {
    preg_match_all("/<!--\s+?\\$\S+?\s+?-->/", $template, $matches);

    foreach ($matches[0] as $match) {
      $key = preg_replace("/<!--\s+?(\\$\S+?)\s+?-->/", "$1", $match);
      $key = self::dotkey_to_objkey($key);

      $template = str_replace($match, "<?php echo $key; ?>", $template); }

    return $template;
  }

  # Convert array to object
  private static function array_to_object($array) {
    $obj = new stdClass;

    foreach ($array as $key => $value) {
      if (strlen($key)) {
        if (is_array($value)) {
          $obj->{$key} = self::array_to_object($value); }
        else {
          $obj->{$key} = $value; } } }

    return $obj;
  }

  # Convert dot notation key to object key
  private static function dotkey_to_objkey($key) {
    return str_replace(".", "->", $key);
  }

  # Custom errors for Yogurt.
  public static function error_handler($number, $string, $file, $line, $variables) {
    if (strpos($file, __FILE__) === 0) {
      echo "<!-- Sorry, something went wrong. PHP says \"$string\" can be found on line $line. -->\n"; }
    else {
      return false; }
  }
}