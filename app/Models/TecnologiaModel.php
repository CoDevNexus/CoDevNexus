<?php
namespace App\Models;

use Core\Model;

class TecnologiaModel extends Model
{
    protected string $table = 'tecnologias';

    public function getAllVisible(bool $modoSeguro = false): array
    {
        return $this->findAll('orden ASC', 'visible = 1');
    }

    public function getAll(): array
    {
        return $this->findAll('orden ASC');
    }

    public function getByCategoria(string $categoria): array
    {
        return $this->findAll('orden ASC', 'categoria = ? AND visible = 1', [$categoria]);
    }
}
