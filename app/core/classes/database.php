<?php
if (!defined("devsakura")) {
  exit("Попытка взлома!");
}

class db
{
  public ?PDO $pdo; // Объект PDO
  public PDOStatement|false $stmt = false; // Для подготовленных выражений
  public $core;
  private $config;
  public int $count_queries = 0;
  public int $count_queries_real = 0;

  /**
  * Конструктор класса db. Устанавливает соединение с базой данных.
  * @param string $host Хост базы данных.
  * @param string $user Имя пользователя базы данных.
  * @param string $pass Пароль пользователя базы данных.
  * @param string $base Имя базы данных.
  * @param int $port Порт базы данных.
  * @param array $core Дополнительные параметры ядра (например, для уведомлений).
  */
  public function __construct($host = '127.0.0.1', $user = 'root', $pass = '', $base = 'base', $port = 3306, $core = array()) {
    $this->core = $core;
    // Предполагаем, что $core->config доступен и содержит конфигурацию.
    // Если config отсутствует в $core, возможно, потребуется передать его явно или получить другим способом.
    $this->config = $core->config ?? null;
    $this->connect($host, $user, $pass, $base, $port);
  }

  /**
  * Устанавливает соединение с базой данных.
  * @param string $host Хост базы данных.
  * @param string $user Имя пользователя базы данных.
  * @param string $pass Пароль пользователя базы данных.
  * @param string $base Имя базы данных.
  * @param int $port Порт базы данных.
  * @return bool|void Возвращает true в случае успешного подключения, иначе вызывает notify и завершает выполнение.
  */
  public function connect($host = '127.0.0.1', $user = 'root', $pass = '', $base = 'base', $port = 3306) {
    $dsn = "mysql:host={$host};port={$port};dbname={$base};charset=utf8mb4";
    $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      // Выбрасывать исключения при ошибках
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      // По умолчанию получать ассоциативные массивы
      PDO::ATTR_EMULATE_PREPARES => false,
      // Отключаем эмуляцию подготовленных выражений
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"     // Устанавливаем кодировку при подключении
    ];

    try {
      $this->pdo = new PDO($dsn, $user, $pass, $options);
      $this->count_queries_real = 1; // Одно соединение
      return true;
    } catch (PDOException $e) {
      error_log("PDO Connection Error: " . $e->getMessage());
      if (isset($this->core) && method_exists($this->core, 'notify')) {
        // Замените $this->core->lang["error"]["sql_error_connection"] на фактический текст, если lang не определен
        return $this->core->notify($this->core->lang["error"]["sql_error_connection"] ?? "Ошибка подключения к базе данных.", 'danger');
      } else {
        die("Ошибка подключения к базе данных: " . $e->getMessage());
      }
    }
  }

  /**
  * Выполняет SQL-запрос.
  * Для запросов с параметрами используйте prepare() и execute().
  * Для простых запросов (без пользовательского ввода) можно использовать этот метод.
  * @param string $sql SQL-запрос.
  * @return PDOStatement|false Результат запроса (объект PDOStatement) или false в случае ошибки.
  */
  public function query(string $sql): PDOStatement|false {
    $this->count_queries += 1;
    $this->count_queries_real += 1;
    try {
      // PDO::query возвращает PDOStatement для SELECT, SHOW, DESCRIBE
      // и для других типов запросов (INSERT, UPDATE, DELETE) также.
      return $this->pdo->query($sql);
    } catch (PDOException $e) {
      error_log("SQL Error in query: " . $e->getMessage() . " Query: " . $sql);
      return false;
    }
  }

  /**
  * Подготавливает SQL-запрос для выполнения.
  * Используйте плейсхолдеры (:name или ?).
  * @param string $sql SQL-запрос с плейсхолдерами.
  * @return db|false Возвращает объект db для цепочки вызовов или false в случае ошибки.
  */
  public function prepare(string $sql): self|false {
    $this->count_queries += 1;
    try {
      $this->stmt = $this->pdo->prepare($sql);
      return $this; // Возвращаем $this для цепочки вызовов
    } catch (PDOException $e) {
      error_log("Prepare statement failed: " . $e->getMessage() . " SQL: " . $sql);
      $this->stmt = false; // Сбрасываем stmt в случае ошибки
      return false;
    }
  }

  /**
  * Выполняет подготовленный запрос с привязкой параметров.
  * @param array $params Ассоциативный массив или индексированный массив параметров для привязки.
  * Используйте именованные плейсхолдеры (e.g., [':id' => $id]) или позиционные (e.g., [$id, $name]).
  * @return bool True в случае успеха, false в случае ошибки.
  */
  public function execute(array $params = []): bool {
    if (!$this->stmt) {
      error_log("Attempted to execute without a prepared statement.");
      return false;
    }

    $this->count_queries_real += 1;
    try {
      return $this->stmt->execute($params);
    } catch (PDOException $e) {
      error_log("Execute statement failed: " . $e->getMessage());
      return false;
    }
  }

  /**
  * Извлекает следующую строку из результирующего набора.
  * @param int $fetch_style Способ извлечения данных (например, PDO::FETCH_ASSOC, PDO::FETCH_NUM, PDO::FETCH_BOTH).
  * @return array|false Ассоциативный, нумерованный или смешанный массив, представляющий извлеченную строку, или false, если строк больше нет.
  */
  public function fetch(int $fetch_style = PDO::FETCH_ASSOC): array|false {
    if (!$this->stmt) {
      error_log("Attempted to fetch without a prepared statement.");
      return false;
    }
    return $this->stmt->fetch($fetch_style);
  }

  /**
  * Извлекает все оставшиеся строки из результирующего набора в виде массива.
  * @param int $fetch_style Способ извлечения данных (например, PDO::FETCH_ASSOC, PDO::FETCH_NUM, PDO::FETCH_BOTH).
  * @return array Массив всех строк.
  */
  public function fetchAll(int $fetch_style = PDO::FETCH_ASSOC): array {
    if (!$this->stmt) {
      error_log("Attempted to fetchAll without a prepared statement.");
      return [];
    }
    return $this->stmt->fetchAll($fetch_style);
  }

  /**
  * Извлекает значение одного столбца из следующей строки результирующего набора.
  * @param int $column_number Номер столбца (0-индексированный), который необходимо извлечь. По умолчанию 0.
  * @return mixed|false Значение столбца или false, если строк больше нет.
  */
  public function fetchColumn(int $column_number = 0): mixed {
    if (!$this->stmt) {
      error_log("Attempted to fetchColumn without a prepared statement.");
      return false;
    }
    return $this->stmt->fetchColumn($column_number);
  }

  /**
  * Возвращает количество строк, затронутых последним SQL-запросом (INSERT, UPDATE, DELETE).
  * Для SELECT запросов, это может не дать ожидаемого результата до полного извлечения данных,
  * или быть ненадежным в зависимости от драйвера БД.
  * @return int Количество затронутых строк.
  */
  public function affectedRows(): int {
    if (!$this->stmt) {
      return 0;
    }
    return $this->stmt->rowCount();
  }

  /**
  * Возвращает ID, сгенерированный запросом INSERT в таблицу с AUTO_INCREMENT столбцом.
  * @return string ID последней вставленной строки. Возвращается как строка, так как может быть большим числом.
  */
  public function insertId(): string {
    return $this->pdo->lastInsertId();
  }

  /**
  * Закрывает курсор, освобождая ресурсы.
  * Важно вызывать после выполнения запроса и получения всех результатов,
  * особенно если вы планируете повторно использовать тот же подготовленный оператор.
  */
  public function close(): void {
    if ($this->stmt) {
      $this->stmt->closeCursor();
      $this->stmt = false; // Сбрасываем, чтобы избежать случайного повторного использования
    }
  }

  /**
  * Экранирует специальные символы в строке для использования в SQL-запросе.
  * Использовать только для строковых литералов, которые НЕ передаются через prepare/execute.
  * ВНИМАНИЕ: Предпочтительно использовать подготовленные выражения (`prepare`/`execute`) для всех пользовательских данных.
  * Эта функция является крайней мерой и должна использоваться с осторожностью.
  * @param string $string Исходная строка.
  * @return string Экранированная строка (с добавленными кавычками PDO).
  */
  public function safesql(string $string): string {
    // В PDO нет прямого аналога mysqli_real_escape_string без добавления кавычек.
    // PDO::quote() экранирует строку и заключает ее в кавычки.
    // Это подходит для строковых ЛИТЕРАЛОВ в запросах, но не для частей запроса (как названия таблиц/столбцов).
    error_log("Warning: Using safesql(). Always prefer prepared statements for user input.");
    return $this->pdo->quote($string);
  }

  /**
  * Преобразует специальные символы в HTML-сущности.
  * Используется для вывода пользовательского ввода на страницу.
  * @param string $string Исходная строка.
  * @return string Строка с преобразованными символами.
  */
  public function HSC(?string $string = ''): string {
    if($string === null) {
      return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
  }

  /**
  * Возвращает текстовое описание последней ошибки PDO.
  * @return string Сообщение об ошибке.
  */
  public function error(): string {
    $errorInfo = $this->pdo->errorInfo();
    if ($errorInfo[2]) {
      // errorInfo[2] содержит сообщение об ошибке
      return "PDO Error (SQLSTATE: {$errorInfo[0]}, Driver Code: {$errorInfo[1]}): " . $errorInfo[2];
    }
    return "Неизвестная ошибка базы данных.";
  }

  /**
  * Деструктор класса. Закрывает соединение с базой данных при уничтожении объекта.
  * В PDO соединение автоматически закрывается при уничтожении объекта PDO.
  */
  public function __destruct() {
    $this->pdo = null; // Явно сбрасываем объект PDO для освобождения ресурсов
  }
}
