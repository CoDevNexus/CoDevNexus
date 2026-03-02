<?php
namespace App\Models;

use Core\Model;

class AdminUserModel extends Model
{
    protected string $table = 'admin_users';

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updatePassword(int $id, string $hashedPassword): bool
    {
        return $this->update($id, ['password' => $hashedPassword]);
    }
}
