<?php

if (!defined("devsakura")) {

  exit("Попытка взлома!");
}

$mail = array(
  'enable' => false,
  'host' => 'host',
  'username' => 'username',
  'password' => 'password',
  'port' => 587,
  'from_email' => 'from_email',
  'from_name' => 'from_name'
);