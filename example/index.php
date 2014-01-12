<?php

# Load Courier's files -- I hope you use Composer.
spl_autoload_register(function($class) {
  require_once(__DIR__ . "/../src/" . str_replace("\\", "/", $class) . ".php");
});

# Uncomment to check plain output.
#header("Content-Type: text/plain");

$yogurt = new oscarpalmer\Yogurt\Yogurt();

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
echo $yogurt::render(file_get_contents("./template.html"), $vars);