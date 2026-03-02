<?php
namespace App\Models;

use Core\Model;

class PortafolioModel extends Model
{
    protected string $table = 'portafolio';

    public function getAllVisible(bool $modoSeguro = false): array
    {
        $where = 'visible = 1';
        if ($modoSeguro) {
            $where .= ' AND modo_seguro = 0';
        }
        return $this->findAll('orden ASC, created_at DESC', $where);
    }

    public function getAll(): array
    {
        return $this->findAll('orden ASC, created_at DESC');
    }

    public function getByCategoria(string $categoria, bool $modoSeguro = false): array
    {
        $where  = 'categoria = ? AND visible = 1';
        $params = [$categoria];
        if ($modoSeguro) {
            $where .= ' AND modo_seguro = 0';
        }
        return $this->findAll('orden ASC', $where, $params);
    }
}
