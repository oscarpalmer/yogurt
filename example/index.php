<?php

# Load Yogurt's files; you should be using Composer.
spl_autoload_register(function ($class) {
    require_once(__DIR__ . "/../src/" . str_replace("\\", "/", $class) . ".php");
});

# New Yogurt.
$yogurt = new oscarpalmer\Yogurt\Yogurt(__DIR__, "html");

# New flavour.
$template = $yogurt->flavour("template");

# Variables to render.
$data = array(
    "items" => array(
        array("title" => "#1", "body" => "This is no. 1!"),
        array("title" => "#2", "body" => "This is no. 2!"),
        array("title" => "#3", "body" => "This is no. 3!")
    ),
    "body" => "<p>This is regular <abbr title=\"HyperText Markup Language\">HTML</abbr>.</p>",
    "tag" => "This is Yogurt, a tiny templating engine for Dagger &amp; others.",
    "title" => "Yogurt"
);

# Taste your delicious yogurt!
echo $template->taste($data);