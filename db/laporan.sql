CREATE TABLE laporan (
  id INT PRIMARY KEY AUTO_INCREMENT,
  tanggal DATE,
  judul VARCHAR(255),
  isi TEXT
);
-- Menambahkan data acak
INSERT INTO laporan (tanggal, judul, isi) VALUES
('2024-08-01', 'Judul 1', 'Isi laporan 1'),
('2024-08-02', 'Judul 2', 'Isi laporan 2'),
('2024-08-03', 'Judul 3', 'Isi laporan 3'),
('2024-08-04', 'Judul 4', 'Isi laporan 4'),
('2024-08-05', 'Judul 5', 'Isi laporan 5'),
('2024-08-06', 'Judul 6', 'Isi laporan 6');