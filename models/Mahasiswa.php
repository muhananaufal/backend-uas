<?php
// models/Mahasiswa.php

class Mahasiswa
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Mendapatkan semua data mahasiswa
  public function getAllMahasiswa()
  {
    $stmt = $this->pdo->prepare("SELECT * FROM mahasiswa");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Mendapatkan data mahasiswa berdasarkan ID
  public function getMahasiswaById($id)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM mahasiswa WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getMahasiswaByUserId($user_id)
  {
    $query = "
        SELECT u.username, m.nama, m.nim
        FROM users u
        JOIN mahasiswa m ON u.id = m.user_id
        WHERE u.id = :user_id
    ";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getAllMahasiswaWithDetails()
  {
    $query = "
    SELECT 
        m.id,
        m.nim, 
        m.nama AS nama_mahasiswa, 
        GROUP_CONCAT(
            DISTINCT CONCAT(mk.kode_mk, ' - ', d.nama) 
            ORDER BY mk.kode_mk, d.nama SEPARATOR ', '
        ) AS mata_kuliah_dosen
    FROM mahasiswa m
    LEFT JOIN nilai n ON m.id = n.mahasiswa_id
    LEFT JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
    LEFT JOIN dosen d ON n.dosen_id = d.id
    GROUP BY m.id, m.nim, m.nama
    ORDER BY m.nim;
    ";
    $stmt = $this->pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getMahasiswaDetails($id)
  {
    $stmt = $this->pdo->prepare("
          SELECT m.id, m.nim, m.nama 
          FROM mahasiswa m
          WHERE m.id = ?
      ");
    $stmt->execute([$id]);
    $mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($mahasiswa) {
      $stmtCourses = $this->pdo->prepare("
        SELECT mk.id AS mata_kuliah_id, mk.kode_mk, d.id AS dosen_id, d.nama AS dosen_name
        FROM mahasiswa_mata_kuliah mmk
        JOIN mata_kuliah mk ON mmk.mata_kuliah_id = mk.id
        JOIN dosen_mata_kuliah dm ON mk.id = dm.mata_kuliah_id
        JOIN dosen d ON dm.dosen_id = d.id
        WHERE mmk.mahasiswa_id = ?
      ");
      $stmtCourses->execute([$id]);
      $mahasiswa['mata_kuliah_dosen'] = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);
    }

    return $mahasiswa;
  }

  public function getMahasiswaTanpaMataKuliah()
  {
    $query = "
      SELECT mahasiswa.id, mahasiswa.nim, mahasiswa.nama, mahasiswa.nama as nama_mahasiswa
      FROM mahasiswa 
      LEFT JOIN mahasiswa_mata_kuliah 
      ON mahasiswa.id = mahasiswa_mata_kuliah.mahasiswa_id 
      WHERE mahasiswa_mata_kuliah.mahasiswa_id IS NULL;
    ";
    $stmt = $this->pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($stmt);
  }

  public function getMahasiswaByMataKuliahDosen($mataKuliahId, $dosenId)
  {
    $query = "SELECT * FROM mahasiswa WHERE mata_kuliah_id = :mata_kuliah_id AND dosen_id = :dosen_id";
    $stmt = $this->pdo->prepare($query);
    $stmt->execute(['mata_kuliah_id' => $mataKuliahId, 'dosen_id' => $dosenId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getMahasiswaIdByUserId($user_id)
  {
    $query = "SELECT id FROM mahasiswa WHERE user_id = :user_id";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['id'] : null;
  }

  public function getMataKuliahAndDosenByMahasiswa($mahasiswa_id)
  {
    $query = "
        SELECT mk.nama_mk, mk.kode_mk, d.nama AS nama_dosen
        FROM nilai n
        JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
        JOIN dosen d ON n.dosen_id = d.id
        WHERE n.mahasiswa_id = :mahasiswa_id
    ";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getJumlahKelasDanRataRataNilai($mahasiswa_id)
  {
    $query = "
    SELECT 
    COUNT(mata_kuliah_id) AS jumlah_kelas, 
    AVG(total_nilai) AS rata_rata_nilai
    FROM nilai n
    JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
    JOIN dosen d ON n.dosen_id = d.id
    WHERE n.mahasiswa_id = :mahasiswa_id;
    ";

    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getNilaiByMahasiswaId($mahasiswa_id)
  {
    $sql = "SELECT nilai.*, mata_kuliah.nama_mk, mata_kuliah.kode_mk, dosen.nama AS nama_dosen
            FROM nilai
            JOIN mata_kuliah ON nilai.mata_kuliah_id = mata_kuliah.id
            JOIN dosen ON nilai.dosen_id = dosen.id
            WHERE nilai.mahasiswa_id = :mahasiswa_id";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['mahasiswa_id' => $mahasiswa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function filterTable($mataKuliahId, $dosenId)
  {
    $query = "
      SELECT 
        m.id,
        m.nim, 
        m.nama AS nama_mahasiswa, 
        IFNULL(n.kehadiran, 0) AS kehadiran, 
        IFNULL(n.tugas, 0) AS tugas, 
        IFNULL(n.kuis, 0) AS kuis, 
        IFNULL(n.responsi, 0) AS responsi, 
        IFNULL(n.uts, 0) AS uts, 
        IFNULL(n.uas, 0) AS uas, 
        IFNULL(n.keterangan, '-') AS keterangan
      FROM mahasiswa m
      LEFT JOIN nilai n ON m.id = n.mahasiswa_id
      WHERE n.mata_kuliah_id = :mataKuliahId AND n.dosen_id = :dosenId
      ORDER BY m.nim
    ";

    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':mataKuliahId', $mataKuliahId, PDO::PARAM_INT);
    $stmt->bindParam(':dosenId', $dosenId, PDO::PARAM_INT);
    $stmt->execute();
    header('Content-Type: application/json');
    echo json_encode($stmt);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function addMahasiswa()
  {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $nama = $_POST['nama'];
      $nim = $_POST['nim'];
      $mata_kuliah_ids = $_POST['mata_kuliah'];
      $dosen_ids = $_POST['dosen'];

      $username = strtolower(str_replace(' ', '_', $nama));
      $password = password_hash('defaultpassword', PASSWORD_DEFAULT);
      try {
        $pdo = connectDatabase();
        $pdo->beginTransaction();

        $userQuery = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, 'mahasiswa')");
        $userQuery->execute(['username' => $username, 'password' => $password]);
        $user_id = $pdo->lastInsertId();
        $mahasiswaQuery = $pdo->prepare("INSERT INTO mahasiswa (user_id, nim, nama) VALUES (:user_id, :nim, :nama)");
        $mahasiswaQuery->execute(['user_id' => $user_id, 'nim' => $nim, 'nama' => $nama]);

        $mahasiswa_id = $pdo->lastInsertId();

        if ($mata_kuliah_ids && $dosen_ids) {
          foreach ($mata_kuliah_ids as $mata_kuliah_id) {
            $mahasiswaMataKuliahQuery = $pdo->prepare("INSERT INTO mahasiswa_mata_kuliah (mahasiswa_id, mata_kuliah_id) VALUES (?, ?)");
            $mahasiswaMataKuliahQuery->execute([$mahasiswa_id, $mata_kuliah_id]);
          }

          foreach ($mata_kuliah_ids as $mata_kuliah_id) {
            $dosen_id = $dosen_ids[$mata_kuliah_id] ?? null;
            if ($dosen_id) {
              $nilaiQuery = $pdo->prepare("INSERT INTO nilai (mahasiswa_id, mata_kuliah_id, dosen_id) VALUES (?, ?, ?)");
              $nilaiQuery->execute([$mahasiswa_id, $mata_kuliah_id, $dosen_id]);
            }
          }
        }

        $pdo->commit();
        return true;
      } catch (Exception $e) {
        $this->pdo->rollBack();
        throw $e;
      }
    }
  }


  public function deleteMahasiswa($id)
  {
    try {
      $this->pdo->beginTransaction();

      $getUserIdQuery = $this->pdo->prepare("SELECT user_id FROM mahasiswa WHERE id = :id");
      $getUserIdQuery->bindParam(':id', $id);
      $getUserIdQuery->execute();
      $userId = $getUserIdQuery->fetchColumn();

      if ($userId) {
        $deleteMahasiswaQuery = $this->pdo->prepare("DELETE FROM mahasiswa WHERE id = :id");
        $deleteMahasiswaQuery->bindParam(':id', $id);
        $deleteMahasiswaQuery->execute();

        $deleteUserQuery = $this->pdo->prepare("DELETE FROM users WHERE id = :user_id");
        $deleteUserQuery->bindParam(':user_id', $userId);
        $deleteUserQuery->execute();

        $this->pdo->commit();
        return true;
      } else {
        return false;
      }
    } catch (Exception $e) {
      $this->pdo->rollBack();
      throw $e;
    }
  }

  public function updateMahasiswa($id, $nim, $nama, $mata_kuliah_ids, $dosen_ids)
  {
    try {
      $this->pdo->beginTransaction();

      $stmt = $this->pdo->prepare("UPDATE mahasiswa SET nim = :nim, nama = :nama WHERE id = :id");
      $stmt->bindParam(':id', $id);
      $stmt->bindParam(':nim', $nim);
      $stmt->bindParam(':nama', $nama);
      $stmt->execute();

      $deleteMataKuliahQuery = $this->pdo->prepare("DELETE FROM mahasiswa_mata_kuliah WHERE mahasiswa_id = :id");
      $deleteMataKuliahQuery->execute(['id' => $id]);

      $deleteNilaiQuery = $this->pdo->prepare("DELETE FROM nilai WHERE mahasiswa_id = :id");
      $deleteNilaiQuery->execute(['id' => $id]);

      if ($mata_kuliah_ids && $dosen_ids) {
        foreach ($mata_kuliah_ids as $mata_kuliah_id) {
          $mahasiswaMataKuliahQuery = $this->pdo->prepare("INSERT INTO mahasiswa_mata_kuliah (mahasiswa_id, mata_kuliah_id) VALUES (?, ?)");
          $mahasiswaMataKuliahQuery->execute([$id, $mata_kuliah_id]);

          $dosen_id = $dosen_ids[$mata_kuliah_id] ?? null;
          if ($dosen_id) {
            $nilaiQuery = $this->pdo->prepare("INSERT INTO nilai (mahasiswa_id, mata_kuliah_id, dosen_id) VALUES (?, ?, ?)");
            $nilaiQuery->execute([$id, $mata_kuliah_id, $dosen_id]);
          }
        }
      }

      $this->pdo->commit();
      return true;
    } catch (Exception $e) {
      $this->pdo->rollBack();
      throw $e;
    }
  }
}
