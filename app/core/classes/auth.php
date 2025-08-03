<?php

if (!defined("devsakura")) {
  exit("Hacking Attempt!");
}

class Auth {
  private $core;

  public function __construct($core) {
    $this->core = $core;
  }

  public function createTmp() {
    return $this->core->random(16);
  }

  public function createHash($password, $salt = '') {

    return $this->core->genPassword($password, $salt);
  }

  public function authentificate($post_password, $password, $salt = '') {
    $post_password = $this->createHash($post_password, $salt);

    return ($post_password === $password) ? true : false;
  }
}

?>