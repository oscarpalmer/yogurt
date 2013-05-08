<?php

class Yogurt {
  /**
    * Settings for Yogurt.
    */
  private static $settings = array(
    "error_message" => "<!-- Sorry, check your syntax. -->",
    "no_partial"    => "<!-- Sorry, couldn't find that partial. -->",
    "partial_dir"   => "./"
  );

  /**
    * Initialise a new Yogurt renderer.
    *
    * @param array $settings An array of settings to override the defaults
    */
  function __construct($settings = array()) {
    foreach ($settings as $key => $value) {
      self::$settings[$key] = $value; }
  }

  /**
    * Render HTML using template and variables.
    *
    * @param string $template Read template file
    * @param array $variables Variables to render
    * @return string Rendered HTML
    */
  public static function render($template, $variables) {
    # We can't render blocks without settings
    if (empty(self::$settings)) {
      return; }

    return self::parse($template, $variables);
  }

  /**
    * Parse.
    *
    * @param string $template Read template file
    * @param array $variables Variables to render
    * @return string Parsed template
    */
  private static function parse($template, $variables, $dot_array = null) {
    # A dot array for nicer traversal
    $dot_array = self::dot_notation_array($variables);

    # Parse it!
    $template = self::parse_ifs($template, $variables, $dot_array);
    $template = self::parse_loops($template, $variables, $dot_array);
    $template = self::parse_includes($template, $variables, $dot_array);
    $template = self::parse_variables($template, $variables, $dot_array);

    return $template;
  }

  /**
    * Parse all loops.
    *
    * @param string $template Read template file
    * @param array $variables Variables to render
    * @return string Parsed template
    */
  private static function parse_loops($template, $variables, $dot_array) {
    # Match all loops
    preg_match_all("/<!-- @loop[\s\S]*?endloop -->/", $template, $matches);

    foreach ($matches[0] as $match) {
      # Match essential info in loop
      preg_match("/<!-- @loop (\\$[\S]*) [-~] (\\$[\S]*) -->([\s\S]*)<!-- endloop -->/", $match, $info);

      if (empty($info)) {
        # Render error message if we don't have all the necessary info
        $template = str_replace($match, self::$settings["error_message"], $template); }
      else {
        # Array to loop
        $array = str_replace("$", "", $info[1]);
        # Name of first-level children
        $variable = str_replace("$", "", $info[2]);
        # Block to render/parse
        $blk = $info[3];
        # Returned HTML
        $new = "";

        foreach (self::not_a_dot_key($variables, $array) as $item => $value) {
          # Add each rendered/parsed block to $new
          $new .= self::parse($blk, array($variable => $value)); }

        # Replace entire loop with or rendered/parsed HTML
        $template = str_replace($match, $new, $template); } }

    return $template;
  }

  /**
    * Parse all if statements.
    *
    * @param string $template Read template file
    * @param array $variables Variables to render
    * @return string Parsed template
    */
  private static function parse_ifs($template, $variables, $dot_array) {
    # Match all if statements
    preg_match_all("/<!-- @if[\s\S]*?endif -->/", $template, $matches);

    foreach ($matches[0] as $match) {
      # Match essential info in if statement;
      # first one matches if statement with operator,
      # and the second matches an if exists statement
      preg_match("/<!-- @if (\\$[\S]*) (.*) \"([\s\S]*)\" -->([\s\S]*)<!-- endif -->/", $match, $if_operator);
      preg_match("/<!-- @if (\\$[\S]*) -->([\s\S]*)<!-- endif -->/", $match, $if_exists);

      if (empty($if_operator) && empty($if_exists)) {
        # Render error message if we don't have all the necessary info
        $template = str_replace($match, self::$settings["error_message"], $template); }
      else if (!empty($if_operator) || !empty($if_exists)) {
        # Variable to check
        $variable = !empty($if_operator) ? $if_operator[1] : $if_exists[1];
        # Remove variable prefix
        $variable = str_replace("$", "", $variable);
        # Type of if statement; is or is not
        $operator = !empty($if_operator) ? $if_operator[2] : null;
        # Value to check against $key
        $value = !empty($if_operator) ? $if_operator[3] : null;
        # Block to render/parse
        $block = !empty($if_operator) ? $if_operator[4] : $if_exists[2];

        $it_is   = (($operator == "is" || $operator == "==") && $dot_array[$variable] == $value) ? true : false;
        $it_isnt = (($operator == "isnt" || $operator == "!=") && $dot_array[$variable] != $value) ? true : false;

        if ((!empty($if_operator) && ($it_is || $it_isnt)) ||
            (!empty($if_exists) && isset($dot_array[$variable]))) {
          # Return a proper block if operators are valid and statement is true or variable exists
          $block = $block; }
        else {
          # Return empty string as block if no matches
          $block = ""; }

        # Replace entire if statement with rendered/parsed HTML
        $template = str_replace($match, self::parse($block, $variables), $template); } }

    return $template;
  }

  /**
    * Parse all includes.
    *
    * @param string $template Read template file
    * @param array $variables Variables to render
    * @return string Parsed template
    */
  private static function parse_includes($template, $variables, $dot_array) {
    # Match all includes
    preg_match_all("/<\!-- @include .* -->/", $template, $matches);

    foreach ($matches[0] as $match) {
      # Remove unnescary info
      $partial = str_replace(array("<!-- @include ", " -->"), "", $match);
      # If partial is a variable
      if (strpos($partial, "$") === 0) {
        $partial = $dot_array[str_replace("$", "", $partial)]; }
      # Partial file
      $partial = self::$settings["partial_dir"] . $partial;
      # Read partial file if it exists
      $partial = file_exists($partial) ? file_get_contents($partial) : self::$settings["no_partial"];
      # Replace include with partial
      $template = str_replace($match, self::parse($partial, $variables), $template); }

    return $template;
  }

  /**
    * Parse all variables.
    *
    * @param string $template Read template file
    * @param array $variables Variables to render
    * @return string Parsed template
    */
  private static function parse_variables($template, $variables, $dot_array) {
    foreach ($dot_array as $key => $value) {
      if (is_string($value) || is_numeric($value)) {
        # Replace variable with its value if it's a string or numerical
        $template = str_replace("<!-- \$$key -->", $value, $template); } }

    return $template;
  }

  /**
    * Converts a normal array to a dot notation array.
    *
    * @param array $array Array to convert
    * @return array Dot notation array
    */
  private static function dot_notation_array($array) {
    $recurs = new RecursiveIteratorIterator(new RecursiveArrayIterator($array));
    # A new array to return
    $return = array();

    foreach ($recurs as $key) {
      # Temporary array
      $keys = array();

      foreach (range(0, $recurs->getDepth()) as $depth) {
        $keys[] = $recurs->getSubIterator($depth)->key(); }

      # Add to our new array
      $return[join(".", $keys)] = $key; }

    return $return;
  }

  /**
    * Uses a dot notation key to retrieve value in a multidimensional array.
    *
    * @param array $array Array to search
    * @param string $key String to use as key
    * @return Value found in array
    */
  private static function not_a_dot_key($array, $key) {
    $keys = explode(".", $key);
    $last = array_pop($keys);

    while ($array_key = array_shift($keys)) {
      if (!array_key_exists($array_key, $array)) {
        $array[$array_key] = array(); }
      $array = &$array[$array_key]; }

    return $array[$last];
  }
}