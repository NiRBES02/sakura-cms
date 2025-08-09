<?php
if (!defined('devsakura')) {
  exit('Попытка взлома!');
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
    require_once(_App_Core_Classes.'/config.php');
    $this->config = new Config($this);

    require_once(_App_Core_Classes.'/database.php');
    $this->db = new db(
      $this->config->db['host'],
      $this->config->db['user'],
      $this->config->db['pass'],
      $this->config->db['base'],
      $this->config->db['port'],
      $this
    );

    require_once(_App_Core_Classes.'/user.php');
    $this->user = new User($this);

    require_once(_App_Core_Classes.'/menu.php');
    $this->menu = new Menu($this);

    $this->lang = $this->language('core');
  }


  /**
  * Проверяет, является ли текущий метод запроса POST.
  *
  * @return bool Возвращает true, если метод запроса POST, иначе false.
  */
  public function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
  }


  /**
  * Проверяет, является ли текущий метод запроса GET.
  *
  * @return bool Возвращает true, если метод запроса GET, иначе false.
  */
  public function isGet(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
  }


  /**
  * Рендерит HTML и возвращает результат в зависимости от типа запроса.
  *
  * Для GET-запроса функция возвращает сгенерированную HTML-строку.
  * Для POST-запроса функция отправляет JSON-ответ с HTML-кодом внутри и завершает выполнение скрипта.
  *
  * @param string $path Путь к файлу шаблона.
  * @param array  $data Ассоциативный массив данных, которые будут извлечены в переменные внутри шаблона.
  * @return string|void Возвращает HTML-строку для GET-запросов или ничего (void) для POST-запросов (т.к. выводит результат напрямую).
  */
  public function view(string $path, array $data = []):string {
    ob_start();
    extract($data);
    require_once($path);
    $pageContent = ob_get_clean();
    if ($this->isPost()) {
      header('Content-Type: application/json');
      echo json_encode(['content' => ['page' => $pageContent]]);
      exit;
    } else {
      return $pageContent;
    }
  }


  /**
  * Проверяет, яаляется ли текущий параметр controller указанному.
  *
  * @param string $controller Контроллер.
  * @return bool Возвращает true, если controller из запроса равен указанному, иначе false;
  */
  public function isController(string $controller): bool {
    return $this->getController() === $controller;
  }


  /**
  * Получает текущий controller.
  *
  * @return string В зависимости от конфигурации возвращает technical_work, иначе вернет текущий controller.
  */
  public function getController(): string {
    $controller = $_POST['controller'] ?? $_GET['controller'] ?? 'index';
    $controller = ($controller != '/') ? $controller : 'index';
    $isClose = $this->config->main['site_close'];
    return ($isClose) ? 'technical_work' : $controller;
  }


  /**
  * Загружает текущий controller.
  *
  * @param string $name Название контроллера.
  * @return string Возвращает загруженный контроллер.
  */
  public function controller(string $name): string {
    $path = _App_Modules."/$name/controllers/index.php";
    if (!file_exists($path)) {
      require_once(_App_Modules.'/404/controllers/index.php');
    } else {
      require_once($path);
    }
    $controller = new Controller($this);
    return $controller->load();
  }


  /**
  * Отображает уведомления в HTML.
  *
  * @param ?string $message Сообщение уведмоления.
  * @param string $string Предназначается для типа отображения уведомления [default|dnager|success|warning] - по умолчанию default.
  * @param array $opt Массив опций и данных для настроект поведения уведомлений.
  * @return void Отправляет JSON ответ.
  */
  public function notify(string $message, string $string = 'default', array $opt = []): void {
    $arg = $this->parseArgsFromString($string);
    $_SESSION['notify'] = [
      "message" => $message,
      "type" => $arg["unknown"][0],
      "opt" => $opt
    ];
    if ($this->isPost()) {
      header('Content-Type: application/json');
      echo json_encode(['notify' => $_SESSION['notify']]);
      unset($_SESSION['notify']);
      exit;
    }
  }


  /**
  * Загружает файл с языковыми строками и возвращает их.
  *
  * @param string $language Имя языкового файла.
  * @return array Массив с языковыми строками из файла или пустой массив, если файл не существует или имя языка недействительно.
  */
  public function language(string $language): array {
    $path = basename($language, '.php');
    if (empty($path)) {
      return [];
    }
    $path = _Resources_Language."/$path.php";
    if (!file_exists($path)) {
      return [];
    }
    return require_once($path);
  }


  /**
  * Заменяет плейсхолдеры в строке значениями из объекта.
  *
  * Плейсхолдеры должны быть в формате {key} или {key.nested_key}.
  *
  * @param string $str Строка с плейсхолдерами.
  * @param array $obj Объект (массив) с данными.
  * @return string Строка с заменёнными значениями.
  */
  public function placeholder(string $str, array $obj): string
  {
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
      return (string)($value ?? $match[0]);
    },
      $str);
    return $replace;
  }


  /**
  * Парсит строку с аргументами командной строки.
  *
  * Поддерживает флаги (--flag), флаги с коротким именем (-f) и значения (--key=value).
  *
  * @param string $input Строка с аргументами.
  * @return array Массив с флагами ('flags'), значениями ('values') и неизвестными аргументами ('unknown').
  */
  public function parseArgsFromString(string $input): array
  {
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


  /**
  * Возвращает IP-адрес пользователя, учитывая прокси-серверы.
  *
  * @return string IP-адрес, обрезанный до 16 символов.
  */
  public function ip(): string
  {
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


  /**
  * Генерирует MD5-хеш строки.
  *
  * @param string $string Строка для хеширования.
  * @return string MD5-хеш.
  */
  public function genPassword($string = ''): string {
    return md5($string);
  }


  /**
  * Генерирует случайную строку.
  *
  * @param int $length Длина генерируемой строки.
  * @param bool $safe Если true, строка будет содержать только буквы и цифры.
  * @return string Сгенерированная случайная строка.
  */
  public function random(int $length = 10, bool $safe = true): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789';
    if (!$safe) {
      $chars .= '$()#@!';
    }
    $string = '';
    $len = strlen($chars) - 1;
    while (strlen($string) < $length) {
      $string .= $chars[mt_rand(0, $len)];
    }
    return $string;
  }
}