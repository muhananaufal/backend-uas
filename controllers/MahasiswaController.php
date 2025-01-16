<?php
require_once __DIR__ . '/../models/Mahasiswa.php';
require_once __DIR__ . '/../models/MataKuliah.php';
require_once __DIR__ . '/../models/Dosen.php';
require_once __DIR__ . '/../config/db.php';

class MahasiswaController
{
  private $mahasiswa;
  private $mataKuliah;
  private $dosen;

  public function __construct()
  {
    $this->mahasiswa = new Mahasiswa(connectDatabase());
    $this->mataKuliah = new MataKuliah(connectDatabase());
    $this->dosen = new Dosen(connectDatabase());
  }

  public function getMahasiswaByUserId($user_id)
  {
    return $this->mahasiswa->getMahasiswaByUserId($user_id);
  }

  public function getAllMataKuliahWithDosen()
  {
    $courses = $this->mataKuliah->getAllMataKuliah();
    foreach ($courses as &$course) {
      $course['dosen'] = $this->dosen->getDosenByMataKuliah($course['id']);
    }
    return $courses;
  }

  public function getAllMahasiswaWithDetails()
  {
    return $this->mahasiswa->getAllMahasiswaWithDetails();
  }


  public function getMahasiswaDetails($id)
  {
    return $this->mahasiswa->getMahasiswaDetails($id);
  }

  public function getDosenByMataKuliah($mataKuliahId)
  {
    return $this->dosen->getDosenByMataKuliah($mataKuliahId);
  }

  public function getMahasiswaByMataKuliahDosen($mataKuliahId, $dosenId)
  {
    return $this->mahasiswa->getMahasiswaByMataKuliahDosen($mataKuliahId, $dosenId);
  }


  public function getMahasiswaTanpaMataKuliah()
  {
    $result = $this->mahasiswa->getMahasiswaTanpaMataKuliah();

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
  }

  public function filterTable($mataKuliahId, $dosenId)
  {
    $result = $this->mahasiswa->filterTable($mataKuliahId, $dosenId);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
  }

  public function getMahasiswaIdByUserId($user_id)
  {
    return $this->mahasiswa->getMahasiswaIdByUserId($user_id);
  }

  public function getMataKuliahAndDosenByMahasiswa($mahasiswa_id)
  {
    return $this->mahasiswa->getMataKuliahAndDosenByMahasiswa($mahasiswa_id);
  }

  public function getJumlahKelasDanRataRataNilai($mahasiswa_id)
  {
    return $this->mahasiswa->getJumlahKelasDanRataRataNilai($mahasiswa_id);
  }

  public function getNilaiByMahasiswaId($mahasiswa_id)
  {
    return $this->mahasiswa->getNilaiByMahasiswaId($mahasiswa_id);
  }

  public function addMahasiswa()
  {
    $result = $this->mahasiswa->addMahasiswa();

    if ($result) {
      header('Location: /views/admin/mahasiswa_management.php');
      exit;
    } else {
      echo "Error: Tidak dapat menambahkan mahasiswa.";
    }
  }

  public function updateMahasiswa($id, $nim, $nama, $mata_kuliah_ids, $dosen_ids)
  {
    $result = $this->mahasiswa->updateMahasiswa($id, $nim, $nama, $mata_kuliah_ids, $dosen_ids);

    if ($result) {
      echo "Mahasiswa berhasil diperbarui.";
    } else {
      echo "Error: Tidak dapat memperbarui mahasiswa.";
    }
  }

  public function deleteMahasiswa($id)
  {
    $result = $this->mahasiswa->deleteMahasiswa($id);

    if ($result) {
      header('Location: /views/admin/mahasiswa_management.php?success=1');
      exit;
    } else {
      echo "Error: Tidak dapat menghapus mahasiswa.";
    }
  }
}

if (isset($_GET['action'])) {
  header('Content-Type: application/json');
  $action = $_GET['action'];


  // Menambahkan mahasiswa (fungsi tambahan jika diperlukan)
  if ($action === 'add') {
    $controller = new MahasiswaController();
  }

  if ($action === 'getAllMahasiswaWithDetails') {
    echo json_encode($controller->getAllMahasiswaWithDetails());
  }

  // Add new action for getting specific mahasiswa details
  if ($action === 'getMahasiswaDetails' && isset($_GET['id'])) {
    $id = $_GET['id'];
    echo json_encode($controller->getMahasiswaDetails($id));
  }

  // Mendapatkan dosen berdasarkan mata kuliah yang dipilih
  if ($action === 'getDosenByMataKuliah' && isset($_GET['mataKuliahId'])) {
    $mataKuliahId = $_GET['mataKuliahId'];
    echo json_encode($controller->getDosenByMataKuliah($mataKuliahId));
  }

  if ($action === 'getMahasiswaTanpaMataKuliah') {
    echo json_encode($controller->getMahasiswaTanpaMataKuliah());
  }

  // Filter tabel mahasiswa berdasarkan dosen dan mata kuliah yang dipilih
  if ($action === 'filterTable' && isset($_GET['mataKuliahId']) && isset($_GET['dosenId'])) {
    $mataKuliahId = (int)$_GET['mataKuliahId'];
    $dosenId = (int)$_GET['dosenId'];
    $controller->filterTable($mataKuliahId, $dosenId);
  }

  if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nim = $_POST['nim'];
    $nama = $_POST['nama'];
    $mata_kuliah_ids = $_POST['mata_kuliah']; // Pastikan form mengirimkan array id mata kuliah
    $dosen_ids = $_POST['dosen']; // Pastikan form mengirimkan array dosen terkait mata kuliah

    if ($controller->updateMahasiswa($id, $nim, $nama, $mata_kuliah_ids, $dosen_ids)) {
      header('Location: /views/admin/mahasiswa_management.php?status=updated');
    } else {
      header('Location: /views/admin/mahasiswa_management.php?status=error');
    }
    exit;
  }


  if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;  // konversi ke integer
    if ($id && $controller->deleteMahasiswa($id)) {
      header('Location: /views/admin/mahasiswa_management.php?status=deleted');
    } else {
      header('Location: /views/admin/mahasiswa_management.php?status=error');
    }
    exit;
  }
  exit;
}
