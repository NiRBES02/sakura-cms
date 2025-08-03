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

define("_Root", dirname(__FILE__));

define("_App", _Root."/app");
define("_App_Configs", _App."/configs");
define("_App_Core", _App."/core");
define("_App_Core_Classes", _App_Core."/classes");
define("_App_Core_Libs", _App_Core."/libs");
define("_App_Modules", _App."/modules");

define("_Public", _Root."/public");
define("_Public_Assets", _Public."/assets");
define("_Public_Assets_Css", _Public_Assets."/css");
define("_Public_Assets_Js", _Public_Assets."/js");
define("_Public_Assets_Img", _Public_Assets."/img");
define("_Public_Uploads", _Public."/uploads");

define("_Resources", _Root."/resources");
define("_Resources_Language", _Resources."/language");
define("_Resources_Views", _Resources."/views");

define("_Node", _Root."/node");

require_once(_Root."/vendor/autoload.php");
require_once(_App_Core_Classes."/core.php");

$core = new Core();