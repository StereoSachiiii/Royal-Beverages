<?php
declare(strict_types=1);

namespace App\Admin\Models;

use App\Core\Database;

class UserPreferenceModel {
    private $db;
    
    public function __construct() {
        $this->db = $GLOBALS['container']->get(\App\Core\Database::class);
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM user_preferences WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    public function findByUserId($userId) {
        $sql = "SELECT * FROM user_preferences WHERE user_id = ?";
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    public function create($data) {
        $sql = "INSERT INTO user_preferences (user_id, preference_type, preference_value) 
                VALUES (?, ?, ?)";
        return $this->db->insert($sql, [
            $data['user_id'],
            $data['preference_type'],
            $data['preference_value']
        ]);
    }
    
    public function update($id, $data) {
        $sql = "UPDATE user_preferences 
                SET user_id = ?, preference_type = ?, preference_value = ? 
                WHERE id = ?";
        return $this->db->update($sql, [
            $data['user_id'],
            $data['preference_type'],
            $data['preference_value'],
            $id
        ]);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM user_preferences WHERE id = ?";
        return $this->db->delete($sql, [$id]);
    }
    
    public function deleteByUserId($userId) {
        $sql = "DELETE FROM user_preferences WHERE user_id = ?";
        return $this->db->delete($sql, [$userId]);
    }
    
    public function findByUserAndType($userId, $preferenceType) {
        $sql = "SELECT * FROM user_preferences WHERE user_id = ? AND preference_type = ?";
        return $this->db->fetch($sql, [$userId, $preferenceType]);
    }
}
