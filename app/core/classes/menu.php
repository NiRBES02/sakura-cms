<?php
if (!defined("devsakura")) {
  exit("Попытка взлома!");
}

class menu {
  private $core;
  private $db;
  private array $leftMenu = [];
  private array $rightMenu = [];

  public function __construct($core) {
    $this->core = $core;
    $this->db = $this->core->db;
    $this->loadMenuItems();
  }

  private function loadMenuItems(): void
  {
    // Проверяем, существует ли директория с модулями
    if (!is_dir(_App_Modules)) {
      return;
    }

    // Сканируем директорию и получаем все папки модулей
    $moduleDirs = array_filter(glob(_App_Modules.'/*'), 'is_dir');

    foreach ($moduleDirs as $moduleDir) {
      $configPath = $moduleDir . '/config.php';

      // Проверяем, существует ли файл конфигурации
      if (file_exists($configPath)) {
        // Подключаем и получаем массив из файла
        $config = require $configPath;

        // Проверяем, есть ли в конфиге ключ 'menu_items'
        if (isset($config['menu_items'])) {
          // Если есть левое меню, добавляем его
          if (isset($config['menu_items']['left'])) {
            $this->leftMenu = array_merge($this->leftMenu, $config['menu_items']['left']);
          }
          // Если есть правое меню, добавляем его
          if (isset($config['menu_items']['right'])) {
            $this->rightMenu = array_merge($this->rightMenu, $config['menu_items']['right']);
          }
        }
      }
    }

    // Сортируем оба меню по 'position'
    $this->sortMenuItems($this->leftMenu);
    $this->sortMenuItems($this->rightMenu);
  }
  private function sortMenuItems(array &$menu): void
  {
    usort($menu, function($a, $b) {
      return $a['position'] <=> $b['position'];
    });
  }

  public function getLeftMenu(): array
  {
    return $this->leftMenu;
  }

  public function getRightMenu(): array
  {
    return $this->rightMenu;
  }



  public function load() {
    $path = _Resources_Views . "/Partials";
    return $this->core->view("{$path}/Menu.phtml", [
      "left" => $this->core->view("{$path}/Menu_Test2.phtml", [
        "menu" => $this->getLeftMenu()
      ]),
      "right" => $this->core->view("{$path}/Menu_Test.phtml", [
        "menu" => $this->getRightMenu()
      ])
    ]);
  }
}