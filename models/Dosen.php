<?php
// models/Dosen.php

class Dosen
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  public function getDosenIdByUserId($user_id)
  {
    $query = "SELECT id FROM dosen WHERE user_id = :user_id";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn();
  }

  public function getDosenDetails($user_id)
  {
    $query = "
        SELECT u.username, d.nama, d.nidn
        FROM users u
        JOIN dosen d ON u.id = d.user_id
        WHERE u.id = :user_id
    ";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getAllDosen()
  {
    $stmt = $this->pdo->prepare("SELECT * FROM dosen ORDER BY nidn");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getMataKuliahByDosen($dosen_id)
  {
    $query = "
      SELECT mk.id, mk.kode_mk, mk.nama_mk, mk.sks
      FROM mata_kuliah AS mk
      JOIN dosen_mata_kuliah AS dmk ON mk.id = dmk.mata_kuliah_id
      WHERE dmk.dosen_id = :dosen_id
    ";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':dosen_id', $dosen_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getAllDosenWithMataKuliah()
  {
    $query = $this->pdo->prepare("
          SELECT d.id, d.nama, d.nidn, GROUP_CONCAT(mk.kode_mk SEPARATOR ', ') AS kode_mata_kuliah, GROUP_CONCAT(mk.nama_mk SEPARATOR ', ') AS mata_kuliah 
          FROM dosen d
          LEFT JOIN dosen_mata_kuliah dm ON d.id = dm.dosen_id
          LEFT JOIN mata_kuliah mk ON dm.mata_kuliah_id = mk.id
          GROUP BY d.id
      ");
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getDosenByMataKuliahId($mataKuliahId)
  {
    $stmt = $this->pdo->prepare("
          SELECT d.id, d.nama 
          FROM dosen d
          JOIN dosen_mata_kuliah dm ON d.id = dm.dosen_id
          WHERE dm.mata_kuliah_id = ?
      ");
    $stmt->execute([$mataKuliahId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }


  public function getDosenMataKuliah($dosenId)
  {
    $query = $this->pdo->prepare("SELECT mk.idFROM dosen_mata_kuliah dmJOIN mata_kuliah mk ON dm.mata_kuliah_id = mk.idWHERE dm.dosen_id = :dosen_id");
    $query->bindParam(':dosen_id', $dosenId, PDO::PARAM_INT);
    $query->execute();
    return array_column($query->fetchAll(PDO::FETCH_ASSOC), 'id');
  }

  public function getMataKuliah()
  {
    $query = $this->pdo->prepare("SELECT * FROM mata_kuliah");
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getJumlahMahasiswaByMataKuliah($dosen_id)
  {
    $sql = "SELECT mata_kuliah_id, COUNT(DISTINCT mahasiswa_id) AS jumlah_mahasiswa
              FROM nilai
              WHERE dosen_id = :dosen_id
              GROUP BY mata_kuliah_id";
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindParam(':dosen_id', $dosen_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getProfileDetailInformations($dosen_id)
  {
    $query = "SELECT COUNT(DISTINCT mata_kuliah_id) AS jumlah_mata_kuliah, COUNT(DISTINCT mahasiswa_id) AS jumlah_mahasiswa
              FROM nilai
              WHERE dosen_id = :dosen_id";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':dosen_id', $dosen_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function addDosen($nama, $nidn, $mataKuliahIds)
  {
    $username = strtolower(str_replace(' ', '_', $nama));
    $password = password_hash('defaultpassword', PASSWORD_DEFAULT);

    try {
      $this->pdo->beginTransaction();

      // Tambahkan pengguna ke tabel users
      $userQuery = $this->pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, 'dosen')");
      $userQuery->bindParam(':username', $username);
      $userQuery->bindParam(':password', $password);
      $userQuery->execute();
      $userId = $this->pdo->lastInsertId();

      // Masukkan data dosen ke tabel dosen
      $query = $this->pdo->prepare("INSERT INTO dosen (user_id, nidn, nama) VALUES (:user_id, :nidn, :nama)");
      $query->bindParam(':user_id', $userId);
      $query->bindParam(':nidn', $nidn);
      $query->bindParam(':nama', $nama);
      $query->execute();
      $dosenId = $this->pdo->lastInsertId();

      if ($dosenId && $mataKuliahIds) {
        foreach ($mataKuliahIds as $mataKuliahId) {
          $queryDosenMk = $this->pdo->prepare("INSERT INTO dosen_mata_kuliah (dosen_id, mata_kuliah_id) VALUES (:dosen_id, :mata_kuliah_id)");
          $queryDosenMk->bindParam(':dosen_id', $dosenId);
          $queryDosenMk->bindParam(':mata_kuliah_id', $mataKuliahId);
          $queryDosenMk->execute();
        }
      }

      $this->pdo->commit();
      return true;
    } catch (Exception $e) {
      $this->pdo->rollBack();
      throw $e;
    }
  }

  public function editDosen($id, $nama, $nidn, $mataKuliahIds)
  {
    try {
      $query = $this->pdo->prepare("UPDATE dosen SET nama = :nama, nidn = :nidn WHERE id = :id");
      $query->bindParam(':nama', $nama);
      $query->bindParam(':nidn', $nidn);
      $query->bindParam(':id', $id);
      $query->execute();

      $this->pdo->prepare("DELETE FROM dosen_mata_kuliah WHERE dosen_id = :dosen_id")
        ->execute([':dosen_id' => $id]);

      foreach ($mataKuliahIds as $mataKuliahId) {
        $query = $this->pdo->prepare("INSERT INTO dosen_mata_kuliah (dosen_id, mata_kuliah_id) VALUES (:dosen_id, :mata_kuliah_id)");
        $query->bindParam(':dosen_id', $id);
        $query->bindParam(':mata_kuliah_id', $mataKuliahId);
        $query->execute();
      }

      return true;
    } catch (Exception $e) {
      throw $e;
    }
  }

  public function deleteDosen($id)
  {
    try {
      $this->pdo->beginTransaction();

      $query = $this->pdo->prepare("DELETE FROM nilai WHERE dosen_id = :id");
      $query->bindParam(':id', $id);
      $query->execute();

      $query = $this->pdo->prepare("DELETE FROM dosen_mata_kuliah WHERE dosen_id = :id");
      $query->bindParam(':id', $id);
      $query->execute();

      $getUserIdQuery = $this->pdo->prepare("SELECT user_id FROM dosen WHERE id = :id");
      $getUserIdQuery->bindParam(':id', $id);
      $getUserIdQuery->execute();
      $userId = $getUserIdQuery->fetchColumn();

      if ($userId) {
        $deleteDosenQuery = $this->pdo->prepare("DELETE FROM dosen WHERE id = :id");
        $deleteDosenQuery->bindParam(':id', $id);
        $deleteDosenQuery->execute();

        $deleteUserQuery = $this->pdo->prepare("DELETE FROM users WHERE id = :user_id");
        $deleteUserQuery->bindParam(':user_id', $userId);
        $deleteUserQuery->execute();
      }

      $this->pdo->commit();
      return true;
    } catch (Exception $e) {
      $this->pdo->rollBack();
      throw $e;
    }
  }

  public function getCourses($dosenId)
  {
    $sql = "SELECT mk.id, mk.kode_mk, mk.nama_mk 
              FROM mata_kuliah mk
              JOIN dosen_mata_kuliah dmk ON mk.id = dmk.mata_kuliah_id
              WHERE dmk.dosen_id = :dosenId";
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindParam(':dosenId', $dosenId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getStudentsByCourse($dosenId, $courseId)
  {
    $sql = "SELECT m.id AS mahasiswa_id, m.nim, m.nama, n.kehadiran, n.tugas, n.kuis, 
              n.responsi, n.uts, n.uas, n.total_nilai, n.keterangan 
              FROM mahasiswa m
              JOIN mahasiswa_mata_kuliah mmk ON m.id = mmk.mahasiswa_id
              JOIN nilai n ON m.id = n.mahasiswa_id AND n.mata_kuliah_id = mmk.mata_kuliah_id
              WHERE mmk.mata_kuliah_id = :courseId AND n.dosen_id = :dosenId";
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindParam(':courseId', $courseId, PDO::PARAM_INT);
    $stmt->bindParam(':dosenId', $dosenId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function editNilai($dosenId, $studentId, $courseId, $grades)
  {
    $sql = "UPDATE nilai 
              SET kehadiran = :kehadiran, tugas = :tugas, kuis = :kuis, 
                  responsi = :responsi, uts = :uts, uas = :uas
              WHERE mahasiswa_id = :studentId AND mata_kuliah_id = :courseId AND dosen_id = :dosenId";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      ':kehadiran' => $grades['kehadiran'],
      ':tugas' => $grades['tugas'],
      ':kuis' => $grades['kuis'],
      ':responsi' => $grades['responsi'],
      ':uts' => $grades['uts'],
      ':uas' => $grades['uas'],
      ':studentId' => $studentId,
      ':courseId' => $courseId,
      ':dosenId' => $dosenId
    ]);
    return true;
  }

  public function hapusNilai($dosenId, $studentId, $courseId)
  {
    $sql = "UPDATE nilai 
              SET kehadiran = 0.0, tugas = 0.0, kuis = 0.0, responsi = 0.0, uts = 0.0, uas = 0.0
              WHERE mahasiswa_id = :studentId AND mata_kuliah_id = :courseId AND dosen_id = :dosenId";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      ':studentId' => $studentId,
      ':courseId' => $courseId,
      ':dosenId' => $dosenId
    ]);
    return true;
  }
}
