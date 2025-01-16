<?php

class Auth
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Fungsi untuk login admin
  public function loginAdmin($username, $password)
  {
    $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username AND role = "admin"');
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['username'] = $username;
      $_SESSION['role'] = $user['role'];
      return true;
    }
    return false;
  }

  // Fungsi untuk login dosen
  public function loginDosen($nidn, $password)
  {
    $stmt = $this->pdo->prepare('
      SELECT users.* FROM users 
      JOIN dosen ON users.id = dosen.user_id 
      WHERE dosen.nidn = :nidn AND users.role = "dosen"
    ');
    $stmt->bindParam(':nidn', $nidn);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['nidn'] = $nidn;
      $_SESSION['role'] = 'dosen';
      return true;
    }
    return false;
  }

  // Fungsi untuk login mahasiswa
  public function loginMahasiswa($nim, $password)
  {
    $stmt = $this->pdo->prepare('
      SELECT users.* FROM users 
      JOIN mahasiswa ON users.id = mahasiswa.user_id 
      WHERE mahasiswa.nim = :nim AND users.role = "mahasiswa"
    ');
    $stmt->bindParam(':nim', $nim);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['nim'] = $nim;
      $_SESSION['role'] = 'mahasiswa';
      return true;
    }
    return false;
  }

  // Method untuk mendaftar user baru
  public function register($username, $password, $role)
  {
    // Cek apakah username sudah ada di database
    $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
      // Username sudah ada di database
      return false;
    }

    // Hash password sebelum disimpan
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $this->pdo->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $passwordHash);
    $stmt->bindParam(':role', $role);

    // Jika registrasi berhasil, lakukan login otomatis
    if ($stmt->execute()) {
      return $this->login($username, $password);
    }

    return false;
  }

  // Method untuk login
  public function login($username, $password)
  {
    $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      // Verifikasi password
      if (password_verify($password, $user['password'])) {
        // Set session atau token untuk login
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        return true;
      }
    }

    return false;
  }
}
