<?php

# Root.
$root = dirname(__FILE__);

# Yogurt.
require_once "$root/../yogurt.php";

use oscarpalmer\Yogurt;

# Uncomment to check plain output.
#header("Content-Type: text/plain");

# Variables.
$vars = [
  "title"   => "Yogurt",
  "tag"     => "This is Yogurt, a tiny templating engine for Dagger &amp; others.",
  "body"    => "<p>This is regular HTML.</p>",
  "partial" => "_partial.html",
  "array"   => [
    ["title" => "#1", "body" => "This is the body for #1."],
    ["title" => "#2", "body" => "This is the body for #2."],
    ["title" => "#3", "body" => "This is the body for #3."]
  ]
];

# Render and echo.
echo Yogurt::render(file_get_contents("$root/template.html"), $vars);