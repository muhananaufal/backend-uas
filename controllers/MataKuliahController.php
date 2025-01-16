<?php
require_once __DIR__ . '/../models/MataKuliah.php';
require_once __DIR__ . '/../config/db.php';

class MataKuliahController
{
  private $model;

  public function __construct()
  {
    $this->model = new MataKuliah(connectDatabase());
  }

  public function getAllMataKuliah()
  {
    return $this->model->getAllMataKuliah();
  }

  // Method untuk menambah mata kuliah baru
  public function addMataKuliah()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $namaMk = $_POST['nama_mk'];
      $nomorMk = $_POST['nomor_mk'];
      $sks = $_POST['sks'];

      // Generate kode mata kuliah otomatis
      $kodeMk = $this->generateKodeMataKuliah($namaMk, $nomorMk);

      if (strlen($kodeMk) > 10) {
        echo "Error: Kode mata kuliah terlalu panjang.";
        return;
      }

      if ($this->model->addMataKuliah($kodeMk, $namaMk, $nomorMk, $sks)) {
        header('Location: /views/admin/mata_kuliah_management.php?success=1');
      } else {
        echo "Error: Tidak dapat menambah mata kuliah.";
      }
    }
  }

  // Method untuk memperbarui mata kuliah
  public function updateMataKuliah()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $id = $_POST['id'];
      $namaMk = $_POST['nama_mk'];
      $nomorMk = $_POST['nomor_mk'];
      $sks = $_POST['sks'];

      // Generate kode mata kuliah otomatis
      $kodeMk = $this->generateKodeMataKuliah($namaMk, $nomorMk);

      if (strlen($kodeMk) > 10) {
        echo "Error: Kode mata kuliah terlalu panjang.";
        return;
      }

      if ($this->model->updateMataKuliah($id, $kodeMk, $namaMk, $nomorMk, $sks)) {
        header('Location: /views/admin/mata_kuliah_management.php?success=2');
      } else {
        echo "Error: Tidak dapat memperbarui mata kuliah.";
      }
    }
  }

  public function deleteMataKuliah()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
      $id = $_POST['id'];

      if ($this->model->deleteMataKuliahWithDependencies($id)) {
        header('Location: /views/admin/mata_kuliah_management.php?success=1');
        exit;
      } else {
        echo "Error: Tidak dapat menghapus mata kuliah.";
      }
    } else {
      echo "Invalid request method or missing ID.";
    }
  }

  private function generateKodeMataKuliah($namaMk, $nomorMk)
  {
    $kata = explode(' ', $namaMk);
    $kode = '';
    foreach ($kata as $k) {
      $kode .= substr($k, 0, 3);
    }

    return strtolower(substr($kode, 0, 6)) . str_pad($nomorMk, 2, '0', STR_PAD_LEFT);
  }
}

// Handle action berdasarkan parameter `action` pada URL
if (isset($_GET['action'])) {
  $controller = new MataKuliahController();

  if ($_GET['action'] === 'add') {
    $controller->addMataKuliah();
  } elseif ($_GET['action'] === 'update') {
    $controller->updateMataKuliah();
  } elseif ($_GET['action'] === 'delete') {
    $controller->deleteMataKuliah();
  }
}
