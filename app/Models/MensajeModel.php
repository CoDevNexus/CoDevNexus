<?php
namespace App\Models;

use Core\Model;

class MensajeModel extends Model
{
    protected string $table = 'mensajes';

    public function getAll(): array
    {
        return $this->findAll('fecha DESC');
    }

    /**
     * Paginado con columna y dirección arbitraria.
     * Devuelve ['rows' => [], 'total' => int]
     */
    public function getPaginated(
        int    $page    = 1,
        int    $perPage = 20,
        string $sortCol = 'fecha',
        string $sortDir = 'DESC'
    ): array {
        $allowed  = ['id', 'nombre', 'correo', 'telefono', 'pais', 'asunto', 'fecha', 'leido', 'respondido'];
        $sortCol  = in_array($sortCol, $allowed, true) ? $sortCol : 'fecha';
        $sortDir  = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';
        $offset   = ($page - 1) * $perPage;
        $total    = $this->count();

        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` ORDER BY `{$sortCol}` {$sortDir} LIMIT ? OFFSET ?"
        );
        $stmt->bindValue(1, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset,  \PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    public function getUnread(): int
    {
        return $this->count('leido = 0');
    }

    public function markAsRead(int $id): void
    {
        $this->update($id, ['leido' => 1]);
    }

    public function markReplied(int $id): void
    {
        $this->update($id, ['respondido' => 1, 'leido' => 1]);
    }
}
