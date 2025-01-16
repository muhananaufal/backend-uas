<?php

require_once __DIR__ . '/../../models/Auth.php'; // Corrected path to Admin.php


class LoginController
{
  private $pdo;
  private $auth;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
    $this->auth = new Auth($pdo); // Inisialisasi objek Auth
  }

  // Login untuk admin
  public function loginAdmin($username, $password)
  {
    if ($this->auth->loginAdmin($username, $password)) {
      return true;
    }
    return false;
  }

  // Login untuk dosen
  public function loginDosen($nidn, $password)
  {
    if ($this->auth->loginDosen($nidn, $password)) {
      return true;
    }
    return false;
  }

  // Login untuk mahasiswa
  public function loginMahasiswa($nim, $password)
  {
    if ($this->auth->loginMahasiswa($nim, $password)) {
      return true;
    }
    return false;
  }
}
