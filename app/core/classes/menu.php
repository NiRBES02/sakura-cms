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

  /**
  * Загружает меню
  *
  * @return void
  */
  private function loadMenuItems(): void {
    // Проверяем, существует ли директория с модулями
    if (!is_dir(_App_Modules)) {
      return;
    }

    // Сканируем директорию и получаем все папки модулей
    $moduleDirs = array_filter(glob(_App_Modules.'/*'), 'is_dir');

    foreach ($moduleDirs as $moduleDir) {
      // Изменили путь: теперь ищем menu.php в папке data
      $menuPath = $moduleDir . '/data/menu.php';

      // Проверяем, существует ли файл меню
      if (file_exists($menuPath)) {
        // Подключаем и получаем массив из файла
        $menuItems = require $menuPath;

        // Проверяем, есть ли в массиве ключ 'left'
        if (isset($menuItems['left'])) {
          // Если есть левое меню, добавляем его
          $this->leftMenu = array_merge($this->leftMenu, $menuItems['left']);
        }
        // Проверяем, есть ли в массиве ключ 'right'
        if (isset($menuItems['right'])) {
          // Если есть правое меню, добавляем его
          $this->rightMenu = array_merge($this->rightMenu, $menuItems['right']);
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
    $path = _Resources_Views . "/partials";
    return $this->core->view("{$path}/menu.phtml", [
      "left" => $this->core->view("{$path}/menu_Test2.phtml", [
        "menu" => $this->getLeftMenu()
      ]),
      "right" => $this->core->view("{$path}/menu_Test.phtml", [
        "menu" => $this->getRightMenu()
      ])
    ]);
  }

}