<?php
if (!defined("devsakura")) {
  exit("Попытка взлома!");
}

class menu {
  private $core;
  private $db;

  public function __construct($core) {
    $this->core = $core;
    $this->db = $this->core->db;
  }

  private function left() {
    $stmt = $this->db->prepare("
        SELECT
            mc.name AS cat_name,
            mi.icon AS icon,
            mi.name AS item_name,
            mi.url AS url
        FROM
            menu_categories AS mc
        JOIN
            menu_items mi ON mc.id = mi.cat_id
        WHERE
            mc.selector = 'left'
        ORDER BY
            mc.position, mi.position
    ");
    $this->db->execute();
    $menu = [];
    while ($row = $stmt->fetch()) {
      $categoryName = $row['cat_name'];
      if (!isset($menu[$categoryName])) {
        $menu[$categoryName] = [];
      }
      $menu[$categoryName][] = [
        'item_name' => $row['item_name'],
        'icon' => $row["icon"],
        "url" => $row["url"]
      ];
    }

    return $menu;
  }



  public function load() {
    $path = _Resources_Views . "/Partials";
    return $this->core->view("{$path}/Menu.phtml", [
      "left" => $this->core->view("{$path}/Menu_Left.phtml", [
        "menu" => $this->left()
        ]),
      "right" => $this->core->view("{$path}/Menu_Right.phtml")
    ]);
  }
}