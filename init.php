<?php
if (!defined("devsakura")) {
  exit("Попытка взлома!");
}

@ini_set("session.cookie_httponly", 1);
@ini_set("session.use_only_cookies", 1);

if (!session_start()) {
  session_start();
}
header("Content-Type: text/html; charset=UTF-8");

// Define каталогов

define("_Root", dirname(__FILE__));

define("_App", _Root."/App");
define("_App_Config", _App."/Config");
define("_App_Core", _App."/Core");
define("_App_Core_Classes", _App_Core."/Classes");
define("_App_Core_Libs", _App_Core."/Libs");
define("_App_Modules", _App."/Modules");

define("_Public", _Root."/Public");
define("_Public_Assets", _Public."/Assets");
define("_Public_Assets_Css", _Public_Assets."/Css");
define("_Public_Assets_Js", _Public_Assets."/Js");
define("_Public_Assets_Img", _Public_Assets."/Img");
define("_Public_Uploads", _Public."/Uploads");

define("_Resources", _Root."/Resources");
define("_Resources_Language", _Resources."/Language");
define("_Resources_Views", _Resources."/Views");

define("_Node", _Root."/Node");

require_once(_Root."/vendor/autoload.php");
require_once(_App_Core_Classes."/Core.php");

$core = new Core();