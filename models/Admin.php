<?php
// models/Admin.php

class Admin
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Mendapatkan data pengguna berdasarkan ID
  public function getUserDetails($user_id)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Mendapatkan data admin berdasarkan username
  public function getAdminByUsername($username)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Memverifikasi password admin
  public function verifyPassword($username, $password)
  {
    $user = $this->getAdminByUsername($username);
    if (!$user || !password_verify($password, $user['password'])) {
      throw new Exception("Current password is incorrect.");
    }
    return true;
  }

  // Memperbarui username dan password admin
  public function updateProfile($oldUsername, $newUsername, $currentPassword, $newPassword, $confirmPassword)
  {
    // Verifikasi password saat ini
    $this->verifyPassword($oldUsername, $currentPassword);

    // Validasi konfirmasi password baru
    if ($newPassword && $newPassword !== $confirmPassword) {
      throw new Exception("New passwords do not match.");
    }

    $this->pdo->beginTransaction();

    try {
      // Update username
      $stmt = $this->pdo->prepare("UPDATE users SET username = :newUsername WHERE username = :oldUsername");
      $stmt->bindParam(':newUsername', $newUsername);
      $stmt->bindParam(':oldUsername', $oldUsername);
      $stmt->execute();

      // Update password jika password baru disediakan
      if ($newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = :password WHERE username = :newUsername");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':newUsername', $newUsername);
        $stmt->execute();
      }

      $this->pdo->commit();
    } catch (Exception $e) {
      $this->pdo->rollBack();
      throw $e;
    }
  }
}
