<?php
if (!defined("devsakura")) {
  exit("Попытка взлома!");
}

class user {
  // Главное
  private $core;
  private $db;
  private $config;


  // Свойства пользователя
  public $login = "Гость";
  public $tag = "undefined";
  public $password = null;
  public $id = null;
  public $email = null;
  public $salt;
  public $tmp;
  public $time_create = "0";
  public $time_last = "0";
  public $ip_create;
  public $ip_last;
  public $firstname;
  public $lastname;
  public $session = null;
  public $avatarUrl;
  public $bannerUrl;
  public $about;
  public $avatar;
  public $banner;

  // Другое
  public $ip;
  public $isAuth = false;
  public $auth;

  public function __construct($core) {
    $this->core = $core;
    $this->db = $core->db;
    $this->config = $core->config;

    $this->ip = $this->core->ip();

    if (!isset($_COOKIE['ds_user'])) {
      return false;
    }

    $cookie = explode("_", $_COOKIE['ds_user']);

    if (!isset($cookie[0], $cookie[1])) {
      $this->setUnauth();
      $this->core->notify();
    }

    $uid = intval($cookie[0]);
    $hash = $cookie[1];

    $prepared_select = $this->db->prepare("
    SELECT *
    FROM users
    WHERE id = :id
    ");

    if ($prepared_select === false) {
      return $this->core->notify($this->core->lang["error"]["sql"], "danger --send");
    }

    $executed_select = $prepared_select->execute([
      "id" => $uid
    ]);

    if ($executed_select === false) {
      return $this->core->notify($this->core->lang["error"]["sql"], "danger --send");
    }

    $arg = $this->db->fetch();

    $this->db->close();

    if ($arg === false) {
      $this->setUnauth();
      $this->core->notify();
    }

    $this->isAuth = true;

    $tmp = $this->db->HSC($arg['tmp']);
    $password = $this->db->HSC($arg['password']);
    $new_hash = $uid . $tmp . $this->ip . md5($this->config->main['site_secury']);
    $arg_hash = $uid . '_' . md5($new_hash);

    $login = $this->db->HSC($arg['login']);
    $this->id = $uid;
    $this->login = $login;
    $this->tag = $this->db->HSC($arg['tag']);
    $this->email = $this->db->HSC($arg['email']);
    $this->password = $password;
    $this->salt = $arg['salt'];
    $this->tmp = $tmp;
    $this->ip_create = $this->db->HSC($arg['ip_create']);
    $this->firstname = $this->db->HSC($arg['firstname']);
    $this->lastname = $this->db->HSC($arg['lastname']);
    $this->about = $this->db->HSC($arg['about']);
    $this->avatar = $arg["avatar"];
    $this->banner = $arg["banner"];

    $this->avatarUrl = $this->avatarUrl($this->id, $this->avatar);
    $this->bannerUrl = $this->bannerUrl($this->id, $this->banner);

    $this->time_create = intval($arg['time_create']);
    $this->time_last = intval($arg['time_last']);

    $this->session = $this->db->HSC($cookie[1]);
    $this->checkSession($cookie[1]);
  }


  /**
  * Загружает класс аутентификаци.
  *
  * @return Auth.
  */
  private function loadAuth(): Auth {
    $authType = Root."/core/libs/auth/usual.php";
    if (!file_exists($authType)) {
      exit('Auth Type Error!');
    }
    require_once($authType);
    return new Auth($this->core);
  }


  /**
  * Проверяет активную сессию.
  *
  * @param string $cookie Куки пользователя.
  * @return bool
  */
  private function checkSession(string $cookie = ""): bool {
    $prepared_select = $this->db->prepare("
    SELECT *
    FROM sessions
    WHERE hash = :hash
    ");
    if ($prepared_select === false) {
      return $this->core->notify($this->core->lang["error"]["sql"], "error");
    }
    $executed_select = $prepared_select->execute([
      "hash" => $cookie
    ]);
    if ($executed_select === false) {
      return $this->core->notify($this->core->lang["error"]["sql"], "error");
    }
    // $session_arg = $this->db->fetch_assoc($query);
    // if ($session_arg["hash"] != $cookie[1]) {
    //     $this->setUnauth();
    //     $this->core->notify();
    // }
    $fetch_select = $this->db->fetch();
    $this->db->close();
    if ($fetch_select === false) {
      $this->setUnauth();
      $this->core->notify();
    }
    return true;
  }


  public function setUnauth() {
    if (isset($_COOKIE['ds_user'])) {
      setcookie('ds_user', '', time() - 3600, '/');
    }
    return true;
  }


  public function checkAuth() {
    if (!$this->isAuth) {
      if ($this->core->requestMethod()) {
        return $this->core->notify($this->core->lang['auth']['login']['exist'], 'danger');
      } else {
        return $this->core->notify($this->core->lang['auth']['login']['exist'], 'danger', "/");
      }
    }
  }


  //--- РАЗДЕЛ МЕТОДОВ АВТОРИЗАЦИИ

  public function createTmp() {
    return $this->core->random(16);
  }

  public function createHash($pswd, $salt = '') {
    return $this->core->genPassword($pswd, $salt);
  }

  public function authentificate($post_pswd, $pswd, $salt = '') {
    $post_pswd = $this->createHash($post_pswd, $salt);
    return ($post_pswd === $pswd) ? true : false;
  }


  // --- РАЗДЕЛ МЕТОДОВ РОЛЕЙ

  public function hasPermission(?int $uid, string $permission): bool {
    $stmt = $this->db->prepare("SELECT 1 FROM user_permissions up JOIN permissions p ON up.permission_id = p.id WHERE up.user_id = ? AND p.name = ? LIMIT 1");
    $stmt->execute([$uid, $permission]);
    if ($stmt->fetch()) {
      return true;
    }
    $stmt = $this->db->prepare("
    SELECT 1
    FROM user_roles ur
    JOIN role_permissions rp ON ur.role_id = rp.role_id
    JOIN permissions p ON rp.permission_id = p.id
    WHERE ur.user_id = ? AND p.name = ?
    LIMIT 1
    ");
    $stmt->execute([$uid, $permission]);
    return (bool)$stmt->fetch();
  }

  public function getUserRoles(int $userId): array {
    $stmt = $this->db->prepare("SELECT * FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ? ORDER BY r.position ASC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // --- РАЗДЕЛ РЕДАКТИРОВАНИЯ ДАННЫХ

  // About
  public function setAbout(int $uid, string $txt): bool {
    $stmt = $this->db->prepare("
    UPDATE users
    SET about = :about
    WHERE id = :id
    ");
    $stmt->execute([
      "id" => $uid,
      "about" => $txt
    ]);
    return true; // TODO
  }

  // --- Раздел ПЕРСОНАЛИЗАЦИИ
  public function avatarUrl(int $uid, ?string $avatar): string {
    $pathNoAvatar = "/Public/Assets/Img/no-image.png";
    $pathAvatar = "/Public/Uploads/{$uid}/{$avatar}";
    if (!$avatar) {
      return $pathNoAvatar;
    }
    if (!file_exists(_Root."/".$pathAvatar)) {
      return $pathNoAvatar;
    }
    return $pathAvatar;
  }

  public function bannerUrl(int $uid, ?string $banner): ?string {
    $url = "/cdn/{$uid}/{$banner}";
    return $url;
  }

}