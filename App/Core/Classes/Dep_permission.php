<?php

class AccessControl
{
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  // --- Управление ролями ---

  /**
  * Создает новую роль.
  */
  public function createRole(string $name, string $displayName, int $position = 0): int
  {
    $stmt = $this->pdo->prepare("INSERT INTO roles (name, display_name, position) VALUES (?, ?, ?)");
    $stmt->execute([$name, $displayName, $position]);
    return (int)$this->pdo->lastInsertId();
  }

  /**
  * Получает информацию о роли по ID или имени.
  */
  public function getRole(int|string $identifier): array|false
  {
    $field = is_int($identifier) ? 'id' : 'name';
    $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE {$field} = ?");
    $stmt->execute([$identifier]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
  * Обновляет информацию о роли.
  */
  public function updateRole(int $roleId, string $displayName = null, int $position = null): bool
  {
    $updates = [];
    $params = [];

    if ($displayName !== null) {
      $updates[] = "display_name = ?";
      $params[] = $displayName;
    }
    if ($position !== null) {
      $updates[] = "position = ?";
      $params[] = $position;
    }

    if (empty($updates)) {
      return false;
    }

    $sql = "UPDATE roles SET " . implode(", ", $updates) . " WHERE id = ?";
    $params[] = $roleId;

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute($params);
  }

  /**
  * Удаляет роль.
  */
  public function deleteRole(int $roleId): bool
  {
    $stmt = $this->pdo->prepare("DELETE FROM roles WHERE id = ?");
    return $stmt->execute([$roleId]);
  }

  /**
  * Получает все роли, отсортированные по позиции.
  */
  public function getAllRoles(): array
  {
    $stmt = $this->pdo->query("SELECT * FROM roles ORDER BY position ASC, name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // --- Управление привилегиями ---

  /**
  * Создает новую привилегию.
  */
  public function createPermission(string $name, string $description = null): int
  {
    $stmt = $this->pdo->prepare("INSERT INTO permissions (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
    return (int)$this->pdo->lastInsertId();
  }

  /**
  * Получает информацию о привилегии по ID или имени.
  */
  public function getPermission(int|string $identifier): array|false
  {
    $field = is_int($identifier) ? 'id' : 'name';
    $stmt = $this->pdo->prepare("SELECT * FROM permissions WHERE {$field} = ?");
    $stmt->execute([$identifier]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
  * Удаляет привилегию.
  */
  public function deletePermission(int $permissionId): bool
  {
    $stmt = $this->pdo->prepare("DELETE FROM permissions WHERE id = ?");
    return $stmt->execute([$permissionId]);
  }

  /**
  * Получает все привилегии.
  */
  public function getAllPermissions(): array
  {
    $stmt = $this->pdo->query("SELECT * FROM permissions ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // --- Назначение привилегий ролям ---

  /**
  * Добавляет привилегию к роли.
  */
  public function addPermissionToRole(int $roleId, int $permissionId): bool
  {
    try {
      $stmt = $this->pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
      return $stmt->execute([$roleId, $permissionId]);
    } catch (PDOException $e) {
      // Игнорируем ошибку, если запись уже существует (PRIMARY KEY violation)
      if ($e->getCode() == 23000) {
        // SQLSTATE for Integrity Constraint Violation
        return false; // Или true, в зависимости от желаемого поведения
      }
      throw $e;
    }
  }

  /**
  * Удаляет привилегию у роли.
  */
  public function removePermissionFromRole(int $roleId, int $permissionId): bool
  {
    $stmt = $this->pdo->prepare("DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?");
    return $stmt->execute([$roleId, $permissionId]);
  }

  /**
  * Получает все привилегии для конкретной роли.
  */
  public function getRolePermissions(int $roleId): array
  {
    $stmt = $this->pdo->prepare("SELECT p.name FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id WHERE rp.role_id = ?");
    $stmt->execute([$roleId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }

  // --- Назначение ролей пользователям ---

  /**
  * Назначает роль пользователю.
  */
  public function assignRoleToUser(int $userId, int $roleId): bool
  {
    try {
      $stmt = $this->pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
      return $stmt->execute([$userId, $roleId]);
    } catch (PDOException $e) {
      if ($e->getCode() == 23000) {
        return false;
      }
      throw $e;
    }
  }

  /**
  * Удаляет роль у пользователя.
  */
  public function removeRoleFromUser(int $userId, int $roleId): bool
  {
    $stmt = $this->pdo->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = ?");
    return $stmt->execute([$userId, $roleId]);
  }

  /**
  * Получает все роли пользователя.
  */
  public function getUserRoles(int $userId): array
  {
    $stmt = $this->pdo->prepare("SELECT r.name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ? ORDER BY r.position ASC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }

  // --- Выдача привилегий напрямую пользователю ---

  /**
  * Выдает привилегию напрямую пользователю.
  */
  public function assignPermissionToUser(int $userId, int $permissionId): bool
  {
    try {
      $stmt = $this->pdo->prepare("INSERT INTO user_permissions (user_id, permission_id) VALUES (?, ?)");
      return $stmt->execute([$userId, $permissionId]);
    } catch (PDOException $e) {
      if ($e->getCode() == 23000) {
        return false;
      }
      throw $e;
    }
  }

  /**
  * Удаляет привилегию, выданную напрямую пользователю.
  */
  public function removePermissionFromUser(int $userId, int $permissionId): bool
  {
    $stmt = $this->pdo->prepare("DELETE FROM user_permissions WHERE user_id = ? AND permission_id = ?");
    return $stmt->execute([$userId, $permissionId]);
  }

  /**
  * Получает все привилегии, выданные напрямую пользователю.
  */
  public function getUserDirectPermissions(int $userId): array
  {
    $stmt = $this->pdo->prepare("SELECT p.name FROM permissions p JOIN user_permissions up ON p.id = up.permission_id WHERE up.user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }

  // --- Проверка привилегий ---

  /**
  * Получает все уникальные привилегии пользователя (из ролей и напрямую).
  * Учитывает приоритет: привилегии, выданные напрямую, всегда имеют приоритет.
  */
  public function getUserAllPermissions(int $userId): array
  {
    // Получаем привилегии из ролей
    $rolePermissions = [];
    $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.name
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = ?
        ");
    $stmt->execute([$userId]);
    $rolePermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Получаем привилегии, выданные напрямую
    $directPermissions = $this->getUserDirectPermissions($userId);

    // Объединяем и убираем дубликаты.
    // Привилегии, выданные напрямую, по сути, "перекрывают" (или просто добавляются к) привилегиям из ролей.
    return array_unique(array_merge($rolePermissions, $directPermissions));
  }


  /**
  * Проверяет, есть ли у пользователя определенная привилегия.
  * Приоритет: привилегия, выданная напрямую, имеет приоритет.
  * Если у пользователя есть хоть одна роль, содержащая привилегию, она учитывается.
  */
  public function hasPermission(int $userId, string $permissionName): bool
  {
    // 1. Проверяем привилегии, выданные напрямую пользователю
    $stmt = $this->pdo->prepare("SELECT 1 FROM user_permissions up JOIN permissions p ON up.permission_id = p.id WHERE up.user_id = ? AND p.name = ? LIMIT 1");
    $stmt->execute([$userId, $permissionName]);
    if ($stmt->fetch()) {
      return true;
    }

    // 2. Если напрямую не найдено, проверяем привилегии через роли
    $stmt = $this->pdo->prepare("
            SELECT 1
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ? AND p.name = ?
            LIMIT 1
        ");
    $stmt->execute([$userId, $permissionName]);
    return (bool)$stmt->fetch();
  }

  /**
  * Проверяет, есть ли у пользователя хотя бы одна из перечисленных привилегий.
  * Приоритет: привилегия, выданная напрямую, имеет приоритет.
  */
  public function hasAnyPermission(int $userId, array $permissionNames): bool
  {
    if (empty($permissionNames)) {
      return false;
    }

    // Подготавливаем плейсхолдеры для IN clause
    $placeholders = implode(',', array_fill(0, count($permissionNames), '?'));

    // 1. Проверяем привилегии, выданные напрямую
    $sqlDirect = "SELECT 1 FROM user_permissions up JOIN permissions p ON up.permission_id = p.id WHERE up.user_id = ? AND p.name IN ({$placeholders}) LIMIT 1";
    $paramsDirect = array_merge([$userId], $permissionNames);
    $stmtDirect = $this->pdo->prepare($sqlDirect);
    $stmtDirect->execute($paramsDirect);
    if ($stmtDirect->fetch()) {
      return true;
    }

    // 2. Если напрямую не найдено, проверяем через роли
    $sqlRoles = "
            SELECT 1
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ? AND p.name IN ({$placeholders})
            LIMIT 1
        ";
    $paramsRoles = array_merge([$userId], $permissionNames);
    $stmtRoles = $this->pdo->prepare($sqlRoles);
    $stmtRoles->execute($paramsRoles);

    return (bool)$stmtRoles->fetch();
  }


  /**
  * Проверяет, есть ли у пользователя все перечисленные привилегии.
  * Приоритет: привилегия, выданная напрямую, имеет приоритет.
  * Эта функция менее эффективна, чем hasAnyPermission, так как собирает все привилегии пользователя.
  */
  public function hasAllPermissions(int $userId, array $permissionNames): bool
  {
    if (empty($permissionNames)) {
      return true; // Если нет требуемых привилегий, считаем, что условие выполнено
    }

    $userPermissions = $this->getUserAllPermissions($userId);

    foreach ($permissionNames as $requiredPermission) {
      if (!in_array($requiredPermission, $userPermissions)) {
        return false;
      }
    }
    return true;
  }

  /**
  * Проверяет, имеет ли пользователь определенную роль.
  */
  public function hasRole(int $userId, string $roleName): bool
  {
    $stmt = $this->pdo->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ? AND r.name = ? LIMIT 1");
    $stmt->execute([$userId, $roleName]);
    return (bool)$stmt->fetch();
  }
}