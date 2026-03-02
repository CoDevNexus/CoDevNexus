<?php
// ============================================================
// app/Models/ConfiguracionModel.php — get/set por clave, batch
// ============================================================

namespace App\Models;

use Core\Model;

class ConfiguracionModel extends Model
{
    protected string $table      = 'configuracion';
    protected string $primaryKey = 'clave';

    public function get(string $clave, string $default = ''): string
    {
        $stmt = $this->db->prepare("SELECT valor FROM configuracion WHERE clave = ?");
        $stmt->execute([$clave]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? (string) $val : $default;
    }

    public function set(string $clave, string $valor): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO configuracion (clave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );
        $stmt->execute([$clave, $valor]);
    }

    /** Obtener múltiples claves por prefijo o por lista */
    public function getBatch(array $claves): array
    {
        $holders = implode(',', array_fill(0, count($claves), '?'));
        $stmt    = $this->db->prepare(
            "SELECT clave, valor FROM configuracion WHERE clave IN({$holders})"
        );
        $stmt->execute($claves);
        return array_column($stmt->fetchAll(), 'valor', 'clave');
    }

    public function getByPrefix(string $prefix): array
    {
        $stmt = $this->db->prepare(
            "SELECT clave, valor FROM configuracion WHERE clave LIKE ?"
        );
        $stmt->execute([$prefix . '%']);
        return array_column($stmt->fetchAll(), 'valor', 'clave');
    }

    public function setBatch(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO configuracion (clave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );
        foreach ($data as $clave => $valor) {
            $stmt->execute([$clave, (string) $valor]);
        }
    }

    /**
     * Exponer solo claves permitidas (whitelist) — para endpoints públicos
     */
    public function getPublic(array $whitelist): array
    {
        return $this->getBatch($whitelist);
    }

    public function getAllGrouped(): array
    {
        $stmt = $this->db->query("SELECT clave, valor FROM configuracion ORDER BY clave");
        $all  = $stmt->fetchAll();
        $out  = [];
        foreach ($all as $row) {
            $out[$row['clave']] = $row['valor'];
        }
        return $out;
    }
}
