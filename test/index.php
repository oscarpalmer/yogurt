<?php

$root = dirname(__FILE__);

//

require_once "$root/../yogurt.php";

//

$vars = array(
  "title" => "Yogurt",
  "tag"   => "This is Yogurt, a tiny templating engine for Dagger, Lana, &amp; others.",
  "body"  => "<p>This is regular HTML.</p>",
  "array" => array(array("title" => "#1", "body" => "This is the body for #1."),
                   array("title" => "#2", "body" => "This is the body for #2."),
                   array("title" => "#3", "body" => "This is the body for #3.")));

//

$yogurt = new Yogurt();

echo $yogurt::render(file_get_contents("$root/template.html"), $vars);