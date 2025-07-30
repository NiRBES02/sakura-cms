<?

function customError($errno, $errstr, $errfile, $errline) {
  echo "<b>Error:</b> [$errno] $errstr - $errfile:$errline";
}

function shutdownHandler() {
  $error = error_get_last();
  if ($error) {
    echo "<b>Fatal Error:</b> [{$error['type']}] {$error['message']} - {$error['file']}:{$error['line']}";
    // Здесь также можно записать фатальную ошибку в лог
  }
}

?>