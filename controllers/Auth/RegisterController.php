<?php
require_once __DIR__ . '/../../models/Auth.php'; // Corrected path to Admin.php

class RegisterController
{
  private $auth;

  public function __construct($pdo)
  {
    $this->auth = new Auth($pdo);
  }

  public function register($username, $password, $role)
  {
    return $this->auth->register($username, $password, $role);
  }
}
