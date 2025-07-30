<?php
if (!defined("devsakura")) {
  exit("Попытка взлома!");
}

class Core {
  public $header;
  public $footer;
  public $menu;
  public $db;
  public $user;
  public $config = false;
  public $lang = array();

  public function __construct() {
    require_once(_App_Core_Classes . "/Config.php");
    $this->config = new Config($this);

    require_once(_App_Core_Classes . "/DataBase.php");
    $this->db = new db(
      $this->config->db['host'],
      $this->config->db['user'],
      $this->config->db['pass'],
      $this->config->db['base'],
      $this->config->db['port'],
      $this
    );

    require_once(_App_Core_Classes . "/User.php");
    $this->user = new User($this);

    require_once(_App_Core_Classes . "/Menu.php");
    $this->menu = new Menu($this);

    $this->lang = $this->language("Core");
  }

  public function isPost() {
    return ($_SERVER["REQUEST_METHOD"] === "POST") ? true : false;
  }
  public function isGet() {
    return ($_SERVER["REQUEST_METHOD"] === "GET") ? true : false;
  }

  public function loadPageJs($path = "", $data = array()) {
    $arg = array(
      "_page" => $this->loadPage($path, $data)
    );
    echo json_encode($arg);
    exit;
  }


  public function view($path = "", $data = array()) {
    ob_start();
    extract($data);
    require_once($path);
    $pageContent = ob_get_clean();

    if ($this->isPost()) {
      header('Content-Type: application/json');
      // В $pageContent будет HTML модуля, который вызвал эту функцию
      echo json_encode(["content" => ["_page" => $pageContent]]);
      exit;
    } else {
      return $pageContent;
    }
  }

  public function isController(string $controller) {
    if ($this->getController() === $controller) {
      return true;
    } else {
      return false;
    }
  }

  public function getController() {
    $controller = $_POST["controller"] ?? $_GET["controller"] ?? "index";
    $controller = ($controller != "/") ? $controller : "index";
    $isClose = $this->config->main['site_close'];
    return ($isClose) ? 'technical_work' : $controller;
  }

  public function controller(string $module) {
    $path = _App_Modules."/{$module}/Controllers/Index.php";

    if (!file_exists($path)) {
      require_once(_App_Modules."/404/Controllers/Index.php");
    } else {
      require_once($path);
    }
    $controller = new Controller($this);
    return $controller->load();
  }

  public function notify($message = '', $string = 'default', $opt = array()) {
    $arg = $this->parseArgsFromString($string);
    $send = $arg["flags"]["send"] ?? false;


    $_SESSION['notify'] = [
      "message" => $message,
      "type" => $arg["unknown"][0],
      "opt" => $opt
    ];

    if ($this->isPost()) {
      header('Content-Type: application/json');
      echo json_encode(["notify" => $_SESSION["notify"]]);
      unset($_SESSION["notify"]);
      exit;
    }
  }


  public function language($language) {
    $path = _Resources_Language."/{$language}.php";
    if (!file_exists($path)) {
      return array();
    }
    return require_once($path);
  }

  public function placeholder(string $str, array $obj): string {
    $replace = preg_replace_callback('/\{(.*?)\}/', function ($match) use ($obj) {
      $keys = explode('.', $match[1]);
      $value = $obj;
      foreach ($keys as $k) {
        if (isset($value[$k])) {
          $value = $value[$k];
        } else {
          return $match[0];
        }
      }
      return (string) ($value ?? $match[0]);
    },
      $str);
    return $replace;
  }


  public function parseArgsFromString(string $input): array {
    $parsed = array(
      "flags" => [],
      "values" => [],
      "unknown" => []
    );
    $args = explode(" ",
      $input);
    foreach ($args as $arg) {
      if (str_starts_with($arg, '--')) {
        $parts = explode('=', substr($arg, 2), 2);
        $key = $parts[0];
        if (isset($parts[1])) {
          $parsed['values'][$key] = $parts[1];
        } else {
          $parsed['flags'][$key] = true;
        }
      } elseif (str_starts_with($arg, '-')) {
        $key = substr($arg, 1);
        $parsed['flags'][$key] = true;
      } else {
        $parsed['unknown'][] = $arg;
      }
    }
    return $parsed;
  }

  public function ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
      $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
      $ip = $_SERVER['HTTP_X_REAL_IP'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return mb_substr($ip, 0, 16, "UTF-8");
  }

  public function genPassword($string = '', $salt = '', $crypt = false) {
    if ($crypt === false) {
      $crypt = $this->config->main['site_crypt'];
    }
    return md5($string);
  }

  public function random($length = 10, $safe = true) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
    if (!$safe) {
      $chars .= '$()#@!';
    }
    $string = "";
    $len = strlen($chars) - 1;
    while (strlen($string) < $length) {
      $string .= $chars[mt_rand(0, $len)];
    }
    return $string;
  }
}