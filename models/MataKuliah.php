<?php
// models/MataKuliah.php

class MataKuliah
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  public function getAllMataKuliah()
  {
    $stmt = $this->pdo->prepare("SELECT * FROM mata_kuliah ORDER BY kode_mk");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function addMataKuliah($kode_mk, $nama_mk, $nomor_mk, $sks)
  {
    $stmt = $this->pdo->prepare("INSERT INTO mata_kuliah (kode_mk, nama_mk, nomor_mk, sks) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$kode_mk, $nama_mk, $nomor_mk, $sks]);
  }

  public function updateMataKuliah($id, $kode_mk, $nama_mk, $nomor_mk, $sks)
  {
    $stmt = $this->pdo->prepare("UPDATE mata_kuliah SET kode_mk = ?, nama_mk = ?, nomor_mk = ?, sks = ? WHERE id = ?");
    return $stmt->execute([$kode_mk, $nama_mk, $nomor_mk, $sks, $id]);
  }

  public function deleteMataKuliahWithDependencies($id)
  {
    try {
      $this->pdo->beginTransaction();

      // Hapus nilai mahasiswa yang berkaitan dengan mata kuliah
      $stmt = $this->pdo->prepare("DELETE FROM nilai WHERE mata_kuliah_id = ?");
      $stmt->execute([$id]);

      // Hapus data mata kuliah dari database
      $stmt = $this->pdo->prepare("DELETE FROM mata_kuliah WHERE id = ?");
      $stmt->execute([$id]);

      $this->pdo->commit();
      return true;
    } catch (Exception $e) {
      $this->pdo->rollBack();
      return false;
    }
  }
}
