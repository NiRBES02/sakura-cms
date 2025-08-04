<?php
if (!defined("devsakura")) {
  exit("Попытка взлома!");
}

class Config {
  public $main = [];
  public $assets = [];
  public $db = [];

  public function __construct() {
    require_once(_App_Configs."/main.php");
    $this->main = $main;
    
    $this->assets = require_once(_App_Configs."/assets.php");
    
    require_once(_App_Configs."/database.php");
    $this->db = $db;
    
  }
}