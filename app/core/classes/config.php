<?php
if (!defined("devsakura")) {
  exit("Попытка взлома!");
}

class Config {
  public $main = array();
  public $assets = array();
  public $db = array();

  public function __construct() {
    require_once(_App_Config . "/Main.php");
    $this->main = $main;
    
    $this->assets = require_once(_App_Config . "/Assets.php");
    
    require_once(_App_Config . "/Database.php");
    $this->db = $db;
    
  }
}