<?php

if (!defined("devsakura")) {
  exit("Попытка взлома!");
}

$publicCss = "/Public/Assets/Css";
$publicJs = "/Public/Assets/Js";

$css = [
  "{$publicCss}/bootstrap.css",
  "{$publicCss}/bootstrap-custom.css",
  "{$publicCss}/nprogress.css"
];

$js = [
  "{$publicJs}/jquery.js",
  "{$publicJs}/bootstrap.bundle.js",
  "{$publicJs}/nprogress.js",
  "{$publicJs}/uaparser.min.js",
  "{$publicJs}/ds.js"
];

return [
  "css" => $css,
  "js" => $js
];