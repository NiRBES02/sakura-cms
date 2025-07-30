<?php

if (!defined("devsakura")) {
  exit("Попытка взлома!");
}

class Controller {
  private $core;
  public function __construct($core) {
    $this->core = $core;
  }

  public function load() {
    return $this->core->view(_App_Modules . "/Index/Views/Index.phtml");
  }
}