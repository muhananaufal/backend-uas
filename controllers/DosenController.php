<?php
require_once __DIR__ . '/../config/db.php'; // Corrected path to db.php
require_once __DIR__ . '/../models/Dosen.php'; // Include Dosen model

class DosenController
{
  private $pdo;
  private $dosenModel;

  public function __construct()
  { 
    $this->pdo = connectDatabase();
    $this->dosenModel = new Dosen($this->pdo);
  }

  public function getDosenIdByUserId($user_id)
  {
    return $this->dosenModel->getDosenIdByUserId($user_id);
  }

  public function getDosenDetails($user_id)
  {
    return $this->dosenModel->getDosenDetails($user_id);
  }

  public function getAllDosen()
  {
    return $this->dosenModel->getAllDosen();
  }

  public function getMataKuliahByDosen($dosen_id)
  {
    return $this->dosenModel->getMataKuliahByDosen($dosen_id);
  }

  public function getAllDosenWithMataKuliah()
  {
    return $this->dosenModel->getAllDosenWithMataKuliah();
  }

  public function getDosenMataKuliah($dosenId)
  {
    return $this->dosenModel->getDosenMataKuliah($dosenId);
  }

  public function getMataKuliah()
  {
    return $this->dosenModel->getMataKuliah();
  }


  public function getJumlahMahasiswaByMataKuliah($dosen_id)
  {
    return $this->dosenModel->getJumlahMahasiswaByMataKuliah($dosen_id);
  }


  public function getProfileDetailInformations($dosen_id)
  {
    return $this->dosenModel->getProfileDetailInformations($dosen_id);
  }

  // Add a new lecturer
  public function addDosen()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $nama = $_POST['nama'];
      $nidn = $_POST['nidn'];
      $mataKuliahIds = $_POST['mata_kuliah_ids'];

      try {
        $this->dosenModel->addDosen($nama, $nidn, $mataKuliahIds);
        header('Location: /views/admin/dosen_management.php?success=1');
        exit;
      } catch (Exception $e) {
        echo "Failed to add dosen: " . $e->getMessage();
      }
    }
  }

  // Edit an existing lecturer
  public function editDosen()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $id = $_POST['id'];
      $nama = $_POST['nama'];
      $nidn = $_POST['nidn'];
      $mataKuliahIds = $_POST['mata_kuliah_ids'];

      try {
        $this->dosenModel->editDosen($id, $nama, $nidn, $mataKuliahIds);
        header('Location: /views/admin/dosen_management.php?success=1');
        exit;
      } catch (Exception $e) {
        echo "Failed to edit dosen: " . $e->getMessage();
      }
    }
  }

  // Delete a lecturer
  public function deleteDosen()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
      $id = $_POST['id'];

      try {
        $this->dosenModel->deleteDosen($id);
        header('Location: /views/admin/dosen_management.php?success=1');
        exit;
      } catch (Exception $e) {
        echo "Failed to delete dosen: " . $e->getMessage();
      }
    }
  }

  // Get courses associated with a lecturer
  public function getCourses()
  {
    $dosenId = $_SESSION['user_id'];

    try {
      return $this->dosenModel->getCourses($dosenId);
    } catch (Exception $e) {
      echo "Failed to retrieve courses: " . $e->getMessage();
      return [];
    }
  }

  // Get students by course
  public function getStudentsByCourse($courseId)
  {
    $dosenId = $_SESSION['user_id'];
    return $this->dosenModel->getStudentsByCourse($dosenId, $courseId);
  }

  // Edit student grades
  public function editNilai($dosenId, $studentId, $courseId, $grades)
  {
    $this->dosenModel->editNilai($dosenId, $studentId, $courseId, $grades);
    header('Location: /views/dosen/mahasiswa_management.php');
    exit;
  }

  // Reset student grades
  public function hapusNilai($dosenId, $studentId, $courseId)
  {
    $this->dosenModel->hapusNilai($dosenId, $studentId, $courseId);
    header('Location: /views/dosen/mahasiswa_management.php');
    exit;
  }
}

// Menentukan tindakan berdasarkan parameter `action` pada URL
if (isset($_GET['action'])) {
  $controller = new DosenController();

  if ($_GET['action'] === 'add') {
    $controller->addDosen();
  } elseif ($_GET['action'] === 'edit') {
    $controller->editDosen();
  } elseif ($_GET['action'] === 'delete') {
    $controller->deleteDosen();
  } elseif ($_GET['action'] === 'editNilai') {
    // Memeriksa apakah semua data dari form tersedia
    if (!isset($_POST['student_id'], $_POST['course_id'], $_POST['id'])) {
      throw new Exception("ID Mahasiswa atau ID Mata Kuliah tidak ditemukan dalam permintaan.");
    }


    // Mengambil data yang diperlukan dari $_POST
    $dosenId = $_POST['id'];
    $studentId = $_POST['student_id'];
    $courseId = $_POST['course_id'];
    $grades = [
      'kehadiran' => $_POST['kehadiran'] ?? 0,
      'tugas' => $_POST['tugas'] ?? 0,
      'kuis' => $_POST['kuis'] ?? 0,
      'responsi' => $_POST['responsi'] ?? 0,
      'uts' => $_POST['uts'] ?? 0,
      'uas' => $_POST['uas'] ?? 0
    ];

    // Memperbarui nilai
    $controller->editNilai($dosenId, $studentId, $courseId, $grades);
  } elseif ($_GET['action'] === 'hapusNilai') {
    // Memeriksa apakah semua data dari form tersedia
    if (!isset($_POST['student_id'], $_POST['course_id'], $_POST['id'])) {
      throw new Exception("ID Mahasiswa atau ID Mata Kuliah tidak ditemukan dalam permintaan.");
    }


    // Mengambil data yang diperlukan dari $_POST
    $dosenId = $_POST['id'];
    $studentId = $_POST['student_id'];
    $courseId = $_POST['course_id'];

    // Memperbarui nilai
    $controller->hapusNilai($dosenId, $studentId, $courseId);
  }
}
