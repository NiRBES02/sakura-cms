<?php

define('devsakura', '');
require_once('./init.php');

if ($core->isController("ajax")) {
  $core->controller("ajax");
}

if ($core->isPost()) {
  $core->controller(getController());
}

$data = [
  "header" => $core->page(_Resources_Views . "/Layouts/Header.phtml"),
  "menu" => $core->menu->load(),
  "content" => $core->controller($core->getController()),
  "footer" => $core->page(_Resources_Views . "/Layouts/Footer.phtml")
];


echo $core->page(_Resources_Views . "/Layouts/Main.phtml", $data);