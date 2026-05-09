# Dokumentasi Teknis Implementasi SSO

Project ini adalah contoh implementasi integrasi Single Sign-On (SSO) UNRAM untuk aplikasi PHP sederhana. Aplikasi akan mengarahkan pengguna ke portal SSO, menerima callback login dari SSO, memverifikasi signature, menyimpan data pengguna ke session, lalu mengarahkan pengguna ke halaman setelah login.

## Struktur File

```text
.
|-- .env.example
|-- index.php
|-- login.php
`-- print.php
```

| File | Fungsi |
| --- | --- |
| `index.php` | Entry point aplikasi. Pengguna diarahkan ke endpoint autentikasi SSO. |
| `login.php` | Endpoint callback dari SSO. File ini memproses data login, memverifikasi signature, menyimpan session, dan mengembalikan response JSON. |
| `print.php` | Contoh halaman setelah login. File ini membaca dan menampilkan data pengguna dari `$_SESSION['info']`. |
| `.env.example` | Contoh konfigurasi environment yang perlu dibuat sebagai `.env`. |

## Kebutuhan Sistem

- PHP dengan dukungan session.
- Web server yang dapat menjalankan file PHP, misalnya Apache, Nginx + PHP-FPM, atau PHP built-in server untuk pengujian lokal.
- Aplikasi harus dapat diakses dari URL yang didaftarkan/diizinkan oleh layanan SSO.

Project ini tidak menggunakan Composer atau dependency eksternal.

## Konfigurasi Environment

Buat file `.env` berdasarkan `.env.example`.

```env
APP_URI=https://nama-aplikasi.unram.ac.id
SSO_URI=https://sso.unram.ac.id
SSO_SECRET=secret-yang-diberikan-oleh-pustik
```

Penjelasan:

| Variabel | Wajib | Keterangan |
| --- | --- | --- |
| `APP_URI` | Ya | Base URL aplikasi ini. Digunakan untuk membentuk URL redirect setelah login, yaitu `{APP_URI}/print.php`. |
| `SSO_URI` | Ya | Origin layanan SSO. Digunakan pada header `Access-Control-Allow-Origin`. |
| `SSO_SECRET` | Ya | Secret key bersama antara aplikasi dan SSO. Digunakan untuk validasi HMAC SHA-256. |

Konfigurasi dapat dibaca dari environment server atau dari file `.env`. Jika variabel wajib tidak tersedia, aplikasi akan menghentikan proses dengan pesan `{NAMA_ENV} required!`.

## Alur Autentikasi

1. Pengguna membuka aplikasi melalui `index.php` atau bisa langsung mengarahkan ke endpoint autentikasi SSO (step 2).
2. `index.php` mengirim HTTP redirect ke:

   ```text
   https://sso.unram.ac.id/auth/nama-aplikasi
   ```

3. Setelah autentikasi berhasil di SSO, layanan SSO mengirim callback ke `login.php` dengan payload `POST`.
4. `login.php` membaca data dari `$_POST['usso']`.
5. Aplikasi memverifikasi `signature` menggunakan `SSO_SECRET`.
6. Jika signature valid, data pengguna dari `usso.info` disimpan ke:

   ```php
   $_SESSION['info']
   ```

7. Aplikasi mengembalikan response JSON yang berisi status login dan URL redirect.
8. Client diarahkan ke `print.php`.
9. `print.php` membaca session dan menampilkan data pengguna.

## Kontrak Callback SSO

Endpoint callback utama:

```text
POST /login.php
```

Payload yang dibaca aplikasi:

```php
$_POST['usso'] = [
    'info' => [
        // data profil pengguna dari SSO
    ],
    'login' => [
        // data login dari SSO
    ],
    'level' => [
        'kode_akses' => '...',
        'kode_view' => 'S|D|M',
    ],
    'signature' => '...',
];
```

Nilai `kode_view` yang didukung:

| Konstanta | Nilai | Keterangan |
| --- | --- | --- |
| `sso_staf` | `S` | Staf |
| `sso_dosen` | `D` | Dosen |
| `sso_mahasiswa` | `M` | Mahasiswa |

Pada implementasi saat ini, ketiga tipe pengguna tersebut diarahkan ke proses yang sama: menyimpan `usso.info` ke session dan mengembalikan redirect ke `print.php`.

## Verifikasi Signature

Signature dibuat menggunakan HMAC SHA-256.

```php
hash_hmac('sha256', json_encode($data), $key)
```

Data yang diverifikasi oleh aplikasi:

```php
[
    $_login,
    $__akses,
]
```

Dengan:

- `$_login` berasal dari `$_POST['usso']['login']`.
- `$__akses` berasal dari `$_POST['usso']['level']['kode_akses']`.
- `$key` adalah nilai `SSO_SECRET`.

Proses verifikasi:

```php
_signature_verify($_signature, [$_login, $__akses], $_sso_secret)
```

Jika signature tidak cocok, aplikasi mengembalikan:

```json
{
  "status": false,
  "data": "Invalid signature."
}
```

## Response Login

Jika login berhasil:

```json
{
  "status": true,
  "redirect": "https://nama-aplikasi.unram.ac.id/print.php"
}
```

Jika level pengguna tidak dikenali:

```json
{
  "status": false,
  "data": "Unknown level."
}
```

Semua response dari `login.php` menggunakan header:

```http
Content-Type: application/json
Access-Control-Allow-Credentials: true
Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization
Access-Control-Allow-Origin: {SSO_URI}
```

## Session

`login.php` dan `print.php` sama-sama memanggil:

```php
session_start();
```

Setelah callback berhasil, data profil pengguna disimpan di:

```php
$_SESSION['info']
```

Contoh pembacaan session ada di `print.php`:

```php
print_r($_SESSION['info'] ?? []);
```

Dalam implementasi production, halaman setelah login sebaiknya memvalidasi bahwa session sudah tersedia sebelum menampilkan halaman privat.

## Menjalankan Secara Lokal

Untuk saat ini opsi menjalankan autentikasi SSO secara lokal belum tersedia karena callback harus berasal dari domain yang didaftarkan di SSO.

## Catatan Keamanan dan Operasional

- Simpan `SSO_SECRET` hanya di environment server atau file `.env` yang tidak ikut version control.
- File `.env` sudah dikecualikan melalui `.gitignore`.
- Gunakan HTTPS di production agar payload callback dan session cookie tidak dikirim melalui koneksi tidak terenkripsi.
- Pastikan `APP_URI` sesuai dengan domain production yang didaftarkan di layanan SSO.
- Batasi origin CORS melalui `SSO_URI`; jangan gunakan wildcard `*` untuk endpoint login.
- Pertimbangkan penggunaan `hash_equals()` untuk perbandingan signature agar lebih tahan terhadap timing attack.
- Tambahkan validasi session pada halaman privat seperti `print.php`.
- Tambahkan proses logout bila aplikasi membutuhkan penghentian session secara eksplisit.

## Ringkasan Endpoint

| Method | Path | Keterangan |
| --- | --- | --- |
| `GET` | `/index.php` | Redirect pengguna ke portal SSO. |
| `POST` | `/login.php` | Callback SSO dan proses login aplikasi. |
| `GET` | `/print.php` | Contoh halaman setelah login yang menampilkan data session. |
