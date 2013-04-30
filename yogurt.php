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
  private static function parse($template, $variables) {
    $template = self::parse_ifs($template, $variables);
    $template = self::parse_loops($template, $variables);
    $template = self::parse_includes($template, $variables);
    $template = self::parse_variables($template, $variables);

    return $template;
  }

  /**
    * Parse all loops.
    *
    * @param string $template Read template file
    * @param array $variables Variables to render
    * @return string Parsed template
    */
  private static function parse_loops($template, $variables) {
    # Match all loops
    preg_match_all("/<!-- @loop[\s\S]*?endloop -->/", $template, $matches);

    foreach ($matches[0] as $match) {
      # Match essential info in loop
      preg_match("/<!-- @loop (\\$[\w]*) [-~] (\\$[\w]*) -->([\s\S]*)<!-- endloop -->/", $match, $info);

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

        foreach ($variables[$array] as $item => $value) {
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
  private static function parse_ifs($template, $variables) {
    # Match all if statements
    preg_match_all("/<!-- @if[\s\S]*?endif -->/", $template, $matches);

    foreach ($matches[0] as $match) {
      # Match essential info in if statement;
      # first one matches if statement with operator,
      # and the second matches an if exists statement
      preg_match("/<!-- @if (\\$[\w]*) (.*) \"(.*)\" -->([\s\S]*)<!-- endif -->/", $match, $if_operator);
      preg_match("/<!-- @if (\\$[\w]*) -->([\s\S]*)<!-- endif -->/", $match, $if_exists);

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

        if ((($operator == "is" || $operator == "==") && $variables[$variable] == $value) or
            (($operator == "isnt" || $operator == "!=") && $variables[$variable] != $value) or
            (isset($variables[$variable]) && is_string($variables[$variable]))) {
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
  private static function parse_includes($template, $variables) {
    # Match all includes
    preg_match_all("/<\!-- @include .+ -->/", $template, $matches);

    foreach ($matches[0] as $match) {
      # Remove unnescary info
      $partial = str_replace(array("<!-- @include ", " -->"), "", $match);
      # Partial file
      $partial = self::$settings["partial_dir"] . $partial;
      # Read partial file if it exists
      $partial = file_exists($partial) ? file_get_contents($partial) : self::$settings["no_partial"];
      # Replace include with partial
      $template = str_replace($match, $partial, $template); }

    return $template;
  }

  /**
    * Parse all variables.
    *
    * @param string $template Read template file
    * @param array $variables Variables to render
    * @return string Parsed template
    */
  private static function parse_variables($template, $variables) {
    # Convert array to a dot notation array
    $variables = self::dot_notation_array($variables);

    foreach ($variables as $key => $value) {
      if (is_string($value)) {
        # Replace variable with its value if it's a string
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
}