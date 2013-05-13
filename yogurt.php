<?php

class Yogurt {
  # Settings for Yogurt
  private static $settings = array(
    "error_message" => "<!-- Sorry, check your syntax. -->",
    "no_partial"    => "<!-- Sorry, couldn't find that partial. -->",
    "partial_dir"   => "./"
  );

  # Start a new parser/renderer
  function __construct($settings = array()) {
    foreach ($settings as $key => $value) {
      self::$settings[$key] = $value; }
  }

  # Render HTML
  public static function render($template, $variables) {
    if (empty(self::$settings)) {
      return; }

    return self::parse($template, $variables);
  }

  # Parse template
  private static function parse($template, $variables) {
    $variables = self::array_to_object($variables);

    $template = self::parse_ifs($template);
    $template = self::parse_foreach($template);
    $template = self::parse_includes($template);
    $template = self::parse_variables($template);

    ob_start() and extract(get_object_vars($variables));
    eval("?>" . $template);
    return ob_get_clean();
  }

  # Parse foreach-loops
  private static function parse_foreach($template) {
    preg_match_all("/<!--\s+?foreach[\s\S]+?endforeach\s+?-->/", $template, $matches);

    foreach ($matches[0] as $match) {
      preg_match("/<!--\s+?foreach\s+?(\\$\S+?)\s+?as\s+?(\\$\S+?)\s+?-->([\s\S]+?)<!--\s+?endforeach\s+?-->/", $match, $info);

      if (!empty($info)) {
        $template = str_replace($match, "<?php foreach ({$info[1]} as {$info[2]}): ?>{$info[3]}<?php endforeach; ?>", $template); }
      else {
        $template = str_replace($match, self::$settings["error_message"], $template); } }

    return $template;
  }

  # Parse if-statements
  private static function parse_ifs($template) {
    $template = preg_replace("/<!--\s+?else\s+?-->/", "<?php else: ?>", $template);

    preg_match_all("/<!--\s+?if[\s\S]+?endif\s+-->/", $template, $if_matches);
    preg_match_all("/<!--\s+?else\s+?if[\s\S]+?-->/", $template, $if_else_matches);

    foreach ($if_matches[0] as $match) {
      preg_match("/<!--\s+?if\s+?(\\$\S+?)\s+?(.+?)\s+?(\\$\S+?|\"[\s\S]+?\")\s+?-->([\s\S]+?)<!--\s+?endif\s+-->/", $match, $if_operator);
      preg_match("/<!--\s+?if\s+?(\\$\S+?)\s+?-->([\s\S]+?)<!--\s+?endif\s+-->/", $match, $if_exists);

      if (!empty($if_operator) || !empty($if_exists)) {
        $key   = str_replace(".", "->", empty($if_operator) ? $if_exists[1] : $if_operator[1]);
        $value = empty($if_operator) ? "" : "\"" . str_replace(array("$", "\""), "", $if_operator[3]) . "\"";
        $opera = empty($if_operator) ? "" : (($if_operator[2] == "is" || $if_operator[2] == "==") ? " == " : " != ");
        $block = empty($if_operator) ? $if_exists[2] : $if_operator[4];

        $template = str_replace($match, "<?php if ($key$opera$value): ?>$block<?php endif; ?>", $template); }
      else {
        $template = str_replace($match, self::$settings["error_message"], $template); } }

    foreach ($if_else_matches[0] as $match) {
      preg_match("/<!--\s+?else\s+?if\s+(\\$\S+?)\s+?(.+?)\s+?(\\$\S+?|\"[\s\S]+?\")\s+?-->/", $match, $if_operator);
      preg_match("/<!--\s+?else\s+?if\s+?(\\$\S+?)\s+?-->/", $match, $if_exists);

      if (!empty($if_operator) || !empty($if_exists)) {
        $key   = str_replace(".", "->", empty($if_operator) ? $if_exists[1] : $if_operator[1]);
        $value = empty($if_operator) ? "" : "\"" . str_replace(array("$", "\""), "", $if_operator[3]) . "\"";
        $opera = empty($if_operator) ? "" : (($if_operator[2] == "is" || $if_operator[2] == "==") ? " == " : " != ");

        $template = str_replace($match, "<?php elseif ($key$opera$value): ?>", $template); }
      else {
        $template = str_replace($match, self::$settings["error_message"], $template); } }

    return $template;
  }

  # Parse includes
  private static function parse_includes($template) {
    preg_match_all("/<\!--\s+?include\s+?.+?\s+?-->/", $template, $matches);

    foreach ($matches[0] as $match) {
      $file = preg_replace("/<!--\s+?include\s+?(.+?)\s+?-->/", "$1", $match);
      $file = self::$settings["partial_dir"] . $file;

      if (file_exists($file)) {
        $partial = file_get_contents($file); }
      else {
        $partial = self::$settings["no_partial"]; }

      $template = str_replace($match, $partial, $template); }

    return $template;
  }

  # Parse variables
  private static function parse_variables($template) {
    preg_match_all("/<!--\s+?\\$\S+?\s+?-->/", $template, $matches);

    foreach ($matches[0] as $match) {
      $key = preg_replace("/<!--\s+?(\\$\S+?)\s+?-->/", "$1", $match);
      $key = str_replace(".", "->", $key);

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
}