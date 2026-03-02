<?php
// ============================================================
// core/Model.php — Base: PDO, find(), findAll(), create(), update(), delete()
// ============================================================

namespace Core;

use PDO;

abstract class Model
{
    protected PDO    $db;
    protected string $table    = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll(string $orderBy = '', string $where = '', array $params = []): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($where)   $sql .= " WHERE {$where}";
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int|string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int|string
    {
        $cols    = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $holders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}` ({$cols}) VALUES ({$holders})"
        );
        $stmt->execute(array_values($data));
        return $this->db->lastInsertId();
    }

    public function update(int|string $id, array $data): bool
    {
        $set  = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $vals = array_values($data);
        $vals[] = $id;

        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET {$set} WHERE `{$this->primaryKey}` = ?"
        );
        return $stmt->execute($vals);
    }

    public function delete(int|string $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?"
        );
        return $stmt->execute([$id]);
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if ($where) $sql .= " WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
