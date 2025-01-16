<?php
// controllers/AdminController.php

require_once __DIR__ . '/../models/Admin.php'; // Corrected path to Admin.php
require_once __DIR__ . '/../config/db.php'; // Corrected path to db.php

class AdminController
{
  private $pdo;
  private $admin;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
    $this->admin = new Admin($pdo); // Instance of Admin model
  }

  public function getUserAdminDetails($user_id)
  {
    return $this->admin->getUserDetails($user_id);
  }

  public function updateProfile()
  {
    session_start();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['username'])) {
      $username = $_POST['username'];
      $currentPassword = $_POST['currentPassword'];
      $newPassword = $_POST['newPassword'];
      $confirmPassword = $_POST['confirmPassword'];

      try {
        $this->admin->updateProfile($_SESSION['username'], $username, $currentPassword, $newPassword, $confirmPassword);
        $_SESSION['username'] = $username; // Update username in session
        header('Location: /views/admin/admin_profile.php?success=1');
        exit;
      } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
      }
    }
  }
}

// Check if action `updateProfile` is called
if (isset($_GET['action'])) {
  // Initialize PDO connection
  $pdo = connectDatabase();

  // Pass $pdo to the AdminController instance
  $controller = new AdminController($pdo);

  if ($_GET['action'] === 'updateProfile') {
    $controller->updateProfile();
  }
}
