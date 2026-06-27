# 🖥️ Orvella Web Frontend (Laravel Dashboard Portal)

Dokumentasi ini menjelaskan arsitektur, cara kerja, struktur keamanan, dan konfigurasi untuk portal web **Orvella Healthcare System** yang dibangun menggunakan **Laravel**.

---

## 🚀 Sekilas Tentang Web Portal

Portal web Orvella merupakan dashboard utama yang digunakan oleh staff medis dan pasien untuk mengelola rekam medis dan melihat diagnosis visualisasi AI. Web frontend dirancang menggunakan **Laravel** dengan arsitektur **BFF (Backend-for-Frontend)**, yang berarti seluruh data aplikasi tidak disimpan di Laravel melainkan dikonsumsi secara dinamis melalui REST API milik **Go Backend**.

### Halaman Utama & Akses Role
- **Welcome / Landing Page**: Halaman publik yang memuat info kontak, performa statistik medis, dan deskripsi sistem (konten teks dapat diperbarui secara dinamis oleh Admin melalui dashboard).
- **Admin Portal**: Panel kendali untuk pendaftaran akun, edit/hapus user, alokasi jam kerja dokter (schedules), dan modifikasi landing page.
- **Doctor Portal**: Tampilan antrian scan CT Scan, antarmuka peninjau hasil AI, form diagnosis, persetujuan medis, dan manajemen jadwal janji temu pasien.
- **Medical Record Portal**: Formulir pengunggahan file scan CT Scan pasien baru, pemicu awal analisis AI, dan pemetaan pasien ke dokter spesialis.
- **Patient Portal**: Dashboard ringkas pasien untuk memesan jadwal pertemuan (appointment) dan melihat rekam medis/scan miliknya yang telah diverifikasi dokter.

---

## 🛠️ Stack Teknologi & Utilitas FE

- **Framework Web**: [Laravel (PHP)](https://laravel.com)
- **Desain & Styling**: [Tailwind CSS](https://tailwindcss.com) (Untuk tampilan antarmuka modern, bersih, dan responsif)
- **Asset Compiler / Bundler**: [Vite](https://vitejs.dev) (Untuk kompilasi aset Javascript/Tailwind secara instan)
- **HTTP Client**: `Illuminate\Support\Facades\Http` (Guzzle wrapper bawaan Laravel untuk komunikasi API)
- **Database Lokal**: SQLite (Digunakan secara minimal hanya untuk penyimpanan tabel Session dan Cache bawaan framework Laravel)

---

## 🔗 Integrasi API via `GoApiService`

Seluruh komunikasi keluar menuju Go Backend dipusatkan pada satu service class yaitu [GoApiService.php](file:///c:/FinproPPT/Orvella_UTS_PPT-main/frontend-laravel/app/Services/GoApiService.php).

### Mekanisme Kerja:
1. **Header Authorization Otomatis**: Setiap kali ada request masuk, `GoApiService` akan mengambil `api_token` dari session Laravel dan menyematkannya ke header `Authorization: Bearer <token>` sebelum diteruskan ke Go Backend.
2. **Penanganan Multi-part Upload**: Untuk pengunggahan file gambar CT Scan, method `upload()` mengotomatisasi pembungkusan berkas biner gambar menggunakan class `attach()` bawaan Laravel Http Client agar dikenali sebagai form-data oleh Go.
3. **Penyimpanan Profil Pengguna**: Setelah proses `/login` sukses, client akan langsung menembak `/me` untuk mendapatkan detail role serta nama lengkap pengguna, kemudian disimpan ke session Laravel (`Session::put('user', $user)`).

---

## 🔐 Keamanan Sisi Client (Middlewares)

Keamanan rute diatur menggunakan middleware Laravel di dalam [web.php](file:///c:/FinproPPT/Orvella_UTS_PPT-main/frontend-laravel/routes/web.php):

1. **`CheckSession` (`auth.session`)**:
   - Memastikan bahwa pengguna memiliki `api_token` aktif dalam session Laravel. Jika kosong, pengguna langsung diredirect ke halaman `/login`.
2. **`CheckRole` (`auth.role:<nama_role>`)**:
   - Menjaga rute spesifik berdasarkan hak akses. Mengambil data role dari session `user.role_name`.
   - Contoh: Rute dengan prefix `admin/` hanya dapat dilewati jika session role bernilai `Admin`. Jika peran tidak sesuai (misal pasien mencoba membuka `/admin/dashboard`), sistem akan mengalihkan kembali ke rute dashboard utama dengan pesan error *flash message*.

---

## ⚙️ Panduan Setup & Menjalankan Project

### Prasyarat
- **PHP** (versi >= 8.1)
- **Composer** (untuk dependensi PHP)
- **Node.js & NPM** (untuk kompilasi Tailwind CSS)

### Langkah Jalankan Web Portal

1. **Salin Environment Variable**  
   Buat file `.env` di dalam direktori `frontend-laravel/`:
   ```bash
   cp .env.example .env
   ```

2. **Atur URL API Go Backend**  
   Pastikan variabel `GO_API_URL` mengarah ke alamat server Go Backend yang sedang berjalan (default: port 8080):
   ```env
   GO_API_URL=http://127.0.0.1:8080
   ```

3. **Instal Dependensi PHP (Composer)**  
   ```bash
   composer install
   ```

4. **Instal Dependensi Javascript (NPM) & Build Asset**  
   ```bash
   npm install
   npm run build
   ```
   *Catatan: Gunakan `npm run dev` jika Anda ingin melakukan kustomisasi desain Tailwind secara real-time.*

5. **Generate Application Key**  
   ```bash
   php artisan key:generate
   ```

6. **Jalankan Database Session (SQLite)**  
   Buat file database kosong untuk menampung sesi (sesuai setting `DB_CONNECTION=sqlite`):
   ```bash
   # Windows PowerShell
   New-Item -ItemType File -Path database/database.sqlite -Force
   
   # Jalankan migrasi tabel session bawaan Laravel
   php artisan migrate
   ```

7. **Jalankan Server Laravel**  
   Jalankan server port lokal (default port 8001 sesuai dengan launcher bat):
   ```bash
   php artisan serve --port=8001
   ```
   Akses aplikasi web dashboard Orvella melalui: **http://127.0.0.1:8001**
