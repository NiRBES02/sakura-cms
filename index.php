<?php
define("devsakura", "");
require_once("./init.php");

if ($core->isController("ajax")) {
  $core->controller("ajax");
}

if ($core->isPost()) {
  $core->controller($core->getController());
}

echo $core->view(_Resources_Views . "/Layouts/Main.phtml", [
  "header" => $core->view(_Resources_Views . "/Layouts/Header.phtml"),
  "menu" => $core->menu->load(),
  "content" => $core->controller($core->getController()),
  "footer" => $core->view(_Resources_Views . "/Layouts/Footer.phtml")
]);