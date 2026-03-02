<?php
namespace App\Models;

use Core\Model;

class SeccionModel extends Model
{
    protected string $table = 'secciones';

    public function getAllVisible(bool $modoSeguro = false): array
    {
        $sql = 'SELECT *, tipo_seccion AS tipo, tipo_seccion AS slug
                FROM secciones
                WHERE visible = 1';
        if ($modoSeguro) {
            $sql .= ' AND modo_seguro = 0';
        }
        $sql .= ' ORDER BY orden ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAll(): array
    {
        return $this->findAll('orden ASC');
    }
}
