# Product Requirements Document (PRD): Website Monitoring & Manajemen Sistem IoT

| Informasi Dokumen | Detail |
| :--- | :--- |
| **Nama Produk** | Dashboard Monitoring & Manajemen Sistem IoT |
| **Versi Dokumen** | 1.2 |
| **Status** | Draft / Ready for Development |
| **Sumber Utama** | `Rangkuman_Kebutuhan_Dashboard_IoT.docx` |
| **Arsitektur Utama** | Node Sensor (LoRa) $\rightarrow$ Gateway (SD Card & MQTT) $\rightarrow$ Web Server & Database $\rightarrow$ Web Dashboard |

> **Keterangan penandaan:** Teks tanpa penanda berasal dari rangkuman kebutuhan. Bagian bertanda **[Usulan]** adalah tambahan penyusunan PRD yang belum ada di rangkuman dan perlu disetujui sebelum dianggap kebutuhan final.

**Riwayat Revisi**
* **1.2:** Lokasi gateway dihapus karena rangkuman kebutuhan hanya mencantumkan lokasi untuk perangkat. Tambahan yang tidak ada di rangkuman diberi penanda **[Usulan]**. Ditambahkan catatan halaman web lokal gateway dan catatan teknis dari rangkuman.
* **1.1:** Versi awal dengan kontrak MQTT dan aturan sistem.

---

## 1. Latar Belakang & Tujuan Produk
Sistem ini dirancang untuk memantau parameter kelistrikan dan lingkungan (suhu, tegangan, arus, daya, dan *irradiance*) secara jarak jauh dari beberapa titik Node Sensor di lapangan.

**Tujuan utama pengembangan website:**
* Menyediakan visualisasi data *real-time* dan historis yang mudah dipahami dari data yang dikirimkan Gateway.
* Memberi ringkasan status perangkat (*online* / *offline*) dan peringatan ketika nilai sensor melewati ambang atau perangkat tidak mengirim data.
* Menyediakan kontrol akses bertingkat (Super Admin & Admin) untuk pengelolaan perangkat dan wilayah pemantauan.

---

## 2. Role & Hak Akses Pengguna (RBAC)

| Role | Deskripsi & Hak Akses |
| :--- | :--- |
| **Super Admin** | **Akses penuh:**<br>• Mengelola seluruh pengguna dan role (Super Admin / Admin).<br>• Mengelola seluruh perangkat, gateway, pengaturan sistem, dan ambang peringatan.<br>• Menetapkan perangkat atau lokasi yang boleh dikelola oleh Admin tertentu.<br>• Melihat seluruh data sensor, catatan perubahan, dan riwayat peringatan. |
| **Admin** | **Akses terbatas (sesuai perangkat yang ditugaskan):**<br>• Menambah dan mengelola perangkat sesuai kewenangannya.<br>• Melihat data terkini, data historis, dan mengekspor data dari perangkat yang dikelola.<br>• Mengatur peringatan untuk perangkat yang dikelola.<br>• **Batasan:** Tidak dapat mengubah Super Admin atau pengaturan global. |

---

## 3. Spesifikasi Alur Data & Integrasi Hardware

### 3.1. Fungsi Perangkat (dari rangkuman)
1. **Node Sensor:** Membaca suhu (DS18B20), tegangan, arus, dan daya (Modbus), serta arus INA219 untuk estimasi *irradiance*. Sampel diambil tiap 5 detik dan dirata-rata selama 5 menit. Ringkasan dikirim via LoRa setelah menerima *beacon* gateway, disertai **nomor paket**.
2. **Gateway:** Mengirim *beacon* tiap 5 menit untuk menyinkronkan node. Mencatat **RSSI** dan **SNR**, lalu menghitung perkiraan **packet loss** dari nomor paket. Menyimpan data ke CSV pada kartu SD, dan menerbitkan sebagian data ke **MQTT Broker**.
3. **Halaman web lokal gateway:** Gateway juga menyediakan halaman web lokal untuk melihat data terbaru dan mengunduh CSV. Halaman ini terpisah dari website dashboard utama.
4. **Website (MQTT subscriber):** Menerima data dari MQTT, memvalidasi perangkat, menyimpan pengukuran ke database, dan menjalankan pengecekan peringatan.

### 3.2. Catatan Teknis (dari rangkuman)
* Gateway telah menyiapkan ID Node 1–3. Program node yang ditinjau adalah Node 2.
* Komentar interval beacon di firmware menyebut 15 menit, sedangkan nilai yang dijalankan adalah 5 menit. Perlu diperiksa.
* Kecocokan *sync word* LoRa antara gateway dan node perlu diverifikasi.

### 3.3. Kontrak Topik & Payload MQTT **[Usulan]**
Bagian ini tidak ada di rangkuman kebutuhan. Bentuk topik dan payload perlu disepakati dengan tim firmware gateway.

* **Broker:** dikonfigurasi lewat variabel `MQTT_*` (host, port, username, password, TLS). Koneksi memakai autentikasi (lihat 5.2).
* **Topik:** `omahiot/gateway/{kode_gateway}/data`, contoh `omahiot/gateway/GW-001/data`. Kode gateway diambil dari segmen ketiga topik.
* **Isi pesan (JSON), satu pesan per paket Node:**
  ```json
  {
    "node_id": 1,
    "packet_no": 10,
    "recorded_at": "2026-11-15T10:00:00+07:00",
    "temperature": 28.5,
    "voltage": 220.5,
    "current": 5.2,
    "power": 1146,
    "irradiance": 850,
    "rssi": -85,
    "snr": 10,
    "packet_loss": 2.5
  }
  ```
* Field `recorded_at` opsional. Jika kosong, waktu penerimaan server dipakai.

---

## 4. Kebutuhan Fungsional (Functional Requirements)

### 4.1. Dashboard Utama
* **Ringkasan status perangkat:** jumlah perangkat terdaftar, jumlah perangkat *online*, dan jumlah perangkat *offline*.
* **Status koneksi gateway:** status koneksi gateway dan layanan MQTT (terhubung / terputus) beserta waktu komunikasi terakhir.
* **Nilai sensor terbaru:** suhu (°C), tegangan (V), arus (A), daya (W), dan *irradiance* (W/m²).
* **Waktu data terakhir:** *timestamp* kapan data terakhir diterima dari perangkat.
* **Grafik tren ringkas:** grafik garis pergerakan nilai sensor dalam rentang waktu singkat.
* **Peringatan aktif:** panel peringatan yang sedang berlangsung dan belum selesai ditangani.

### 4.2. Manajemen Perangkat & Detail Perangkat
* **Daftar perangkat:** tabel berisi ID, nama, lokasi, gateway, dan status perangkat.
* **Tambah, ubah, dan nonaktifkan perangkat**, serta melihat halaman detail.
* **Halaman detail perangkat:**
  * Nilai sensor terkini (suhu, tegangan, arus, daya, *irradiance*) dan waktu pembaruan.
  * Grafik historis perangkat tersebut.
  * Nilai **RSSI**, **SNR**, dan **packet loss** untuk diagnosa kualitas sinyal.
* **Penetapan perangkat atau lokasi:** Super Admin dapat menetapkan perangkat atau lokasi yang boleh dikelola oleh Admin tertentu.
* **Lokasi perangkat:** setiap perangkat memiliki lokasinya sendiri. Lokasi perangkat tidak otomatis mengikuti gateway.
* **ID Node** **[Usulan]:** setiap perangkat memiliki ID Node (`node_id`) yang harus cocok dengan firmware dan dipetakan ke gateway tempat perangkat itu terdaftar.
* **Aturan penghapusan** **[Usulan]:** perangkat yang sudah memiliki data pengukuran, peringatan, atau ambang batas tidak dapat dihapus. Sistem menyarankan untuk menonaktifkan perangkat saja. Perangkat nonaktif berarti datanya diabaikan.

### 4.3. Data Historis & Ekspor
* **Tabel pengukuran historis:** menampilkan rekaman data dengan kolom **timestamp** dan **nomor paket**, identitas perangkat, serta nilai kelima parameter sensor.
* **Filter:** perangkat, jenis sensor, dan rentang waktu (tanggal mulai dan selesai).
* **Grafik historis:** grafik per sensor dan per perangkat sesuai filter waktu.
* **Ekspor data:** unduhan data hasil filter dalam format **CSV**, sekurangnya dalam format CSV, dengan kolom timestamp dan nomor paket.
* **Struktur kolom CSV** **[Usulan]:** menyerupai struktur CSV pada kartu SD gateway agar mudah dicocokkan saat pemulihan data.

### 4.4. Sistem Peringatan (Alerting)
* **Pemicu peringatan:**
  1. **Nilai sensor melewati ambang:** terpicu saat nilai sensor melewati batas atas atau bawah yang dikonfigurasi.
  2. **Perangkat offline:** terpicu saat tidak ada data masuk melebihi batas waktu *offline*.
* **Riwayat peringatan:** tabel berisi jenis peringatan, perangkat terkait, nilai pemicu, waktu kejadian, dan status penanganan.
* **Status penanganan** **[Usulan]:** nilai status adalah *Belum Ditangani*, *Sedang Dicek*, dan *Selesai*. Rangkuman hanya menyebut "status penanganan".
* **Pengaturan ambang:** halaman untuk menentukan nilai minimal dan maksimal tiap parameter sensor, serta batas waktu sebelum perangkat dinyatakan *offline*.
* **Batas waktu offline** **[Usulan]:** disimpan di tabel `settings` dengan key `offline_timeout_minutes`, dengan nilai awal **15 menit**.

### 4.5. Gateway & Pengaturan Sistem
* **Monitoring kesehatan infrastruktur:** status gateway, status MQTT, status internet, dan waktu komunikasi terakhir.
* **Pengaturan sistem (khusus Super Admin):**
  * Identitas dan lokasi perangkat.
  * Ambang peringatan standar.
  * Zona waktu (misalnya `Asia/Jakarta` / WIB).
  * Ekspor dan retensi data (berapa lama data historis disimpan sebelum diarsipkan atau dihapus).
* **Catatan perubahan:** mencatat perubahan penting seperti penambahan perangkat dan perubahan ambang batas, termasuk siapa yang mengubah dan kapan.

### 4.6. Gateway & API Internal
* **Identitas gateway** **[Usulan]:** gateway memiliki nama. Kode gateway dibuat sistem dengan format `GW-001`, `GW-002`, dan seterusnya, berdasarkan ID unik, sehingga tidak dapat diisi atau diubah pengguna. Rangkuman tidak menjelaskan identitas gateway secara rinci.
* **Status koneksi gateway** **[Usulan]:** status gateway (`online`/`offline`), MQTT (`connected`/`disconnected`), dan internet hanya diperbarui oleh sistem. Gateway baru dimulai dengan status offline.
* **Penghapusan gateway** **[Usulan]:** gateway yang masih memiliki perangkat terdaftar tidak dapat dihapus.
* **API JSON gateway untuk dashboard** **[Usulan]:** endpoint `/api/gateways` untuk daftar, detail, tambah, ubah nama, dan hapus. Endpoint ini memakai sesi login dashboard dan **bukan** untuk perangkat hardware. Akses dari aplikasi atau perangkat eksternal memerlukan autentikasi token dan di luar cakupan versi ini.

---

## 5. Kebutuhan Non-Fungsional & Catatan Teknis

1. **Sinkronisasi interval & batas waktu offline** **[Usulan]:** karena *beacon* gateway berjalan tiap 5 menit, batas waktu *offline* yang disarankan minimal 10–15 menit (toleransi 2–3 siklus) agar tidak terjadi *false alarm* saat satu paket LoRa gagal terkirim.
2. **Keamanan & validasi payload** **[Usulan]:** integrasi MQTT memakai autentikasi (username/password atau TLS) dan memvalidasi tipe data sebelum disimpan.
3. **Konsistensi data backup** **[Usulan]:** struktur ekspor CSV disarankan menyerupai CSV pada kartu SD gateway.
4. **Verifikasi konfigurasi hardware** (dari rangkuman): memastikan *sync word* LoRa dan interval *beacon* antara firmware node dan gateway sudah sinkron sebelum pengujian integrasi penuh.
5. **Aturan penyimpanan data MQTT** **[Usulan]:**
   * Pesan yang bukan JSON valid, tidak lengkap, atau berisi tipe data salah ditolak dan dicatat ke log.
   * Paket dari gateway yang tidak dikenal, atau dari `node_id` yang tidak terdaftar di gateway tersebut, ditolak.
   * Paket dengan `packet_no` yang sama untuk perangkat yang sama diabaikan (duplikat).
   * Data dari perangkat yang nonaktif diabaikan.
   * Kegagalan satu pesan tidak menghentikan langganan. Koneksi yang terputus dicoba ulang secara otomatis.
