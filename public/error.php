<?php

require_once dirname(__FILE__)."/../config.php";

$code = array_pop(array_keys($_GET));

$reason = isset($_GET[$code]) ? ($_GET[$code] or " ") : " ";

static $ok = array(
  // 300 => "300 Multiple Choices",
  // 301 => "301 Moved Permanently",
  // 302 => "302 Found",
  // 303 => "303 See Other",
  // 304 => "304 Not Modified",
  // 305 => "305 Use Proxy",
  // 307 => "307 Temporary Redirect",
  400 => "400 Bad Request",
  // 401 => "401 Unauthorized",
  // 402 => "402 Payment Required",
  // 403 => "403 Forbidden",
  404 => "404 Not Found",
  405 => "405 Method Not Allowed",
  // 406 => "406 Not Acceptable",
  // 407 => "407 Proxy Authentication Required",
  // 408 => "408 Request Timeout",
  // 409 => "409 Conflict",
  // 410 => "410 Gone",
  // 411 => "411 Length Required",
  // 412 => "412 Precondition Failed",
  // 413 => "413 Request Entity Too Large",
  // 414 => "414 Request-URI Too Long",
  // 415 => "415 Unsupported Media Type",
  // 416 => "416 Requested Range Not Satisfiable",
  // 417 => "417 Expectation Failed",
  500 => "500 Internal Server Error",
  // 501 => "501 Not Implemented",
  // 502 => "502 Bad Gateway",
  503 => "503 Service Unavailable",
  // 504 => "504 Gateway Timeout",
  // 505 => "505 HTTP Version Not Supported"
);

if(!isset($ok[$code]))
  $code = 500;

header($reason, true, $code);

$view_dir = dirname(__FILE__)."/../view/error/";

$head = str_replace(array(
    '$HTTP_CODE'
  ), array(
    $ok[$code]
  ),
  file_get_contents("{$view_dir}head.html")
);

$body = str_replace(array(
    '$HOME_URL',
    '$REASON',
  ), array(
    BASE_URL,
    "$reason",
  ),
  file_get_contents("{$view_dir}{$code}.html")
);
die($head.$body);
