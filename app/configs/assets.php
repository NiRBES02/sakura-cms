<?php

if (!defined("devsakura")) {
  exit("Попытка взлома!");
}

$publicCss = "/public/assets/css";
$publicJs = "/public/assets/js";

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
  "{$publicJs}/ds.js",
  "{$publicJs}/ds-bundle.js"
];

return [
  "css" => $css,
  "js" => $js
];