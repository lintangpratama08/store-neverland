# Neverland Store

Marketplace ringan untuk jual-beli akun Total Football.

Powered by Bang Put & Mpink.

## Stack

- Laravel 13 / PHP 8.3
- PostgreSQL
- React 19 + Vite
- CSS Liquid Glass tanpa library UI tambahan

## Jalankan lokal

1. Salin `.env.example` menjadi `.env`.
2. Isi kredensial PostgreSQL dan buat database jb_akun_neverland. Database ini khusus Neverland Store dan tidak boleh memakai database Neverland utama.
3. Jalankan `composer install` dan `npm install`.
4. Jalankan `php artisan key:generate`.
5. Jalankan `php artisan migrate --seed`.
6. Jalankan `npm run dev` dan `php artisan serve`.

Admin demo: `admin` / `password`.
Pendaftaran publik ditutup; login hanya untuk admin.

## Area utama

- `/` — beranda marketplace
- `/produk` — katalog akun aktif + pesan via WhatsApp
- `/produk/{slug}` — detail akun
- `/testimoni` — testimoni pembeli dan seller
- `/admin/login` — akses admin tersembunyi
- `/dashboard` — kelola listing akun, produk, media, dan order

Beranda memakai satu banner Neverland Store bermotif Neverland. Figur tampil full body di dalam frame banner tanpa meluber. Kategori akun dihapus; filter slider rentang harga dan nominal min/max hanya tersedia di katalog produk.

Halaman `/testimoni` menampilkan galeri foto saja. Upload foto dari Dashboard → Media library menggunakan key `testimonial_gallery`.

## Alur jual-beli

Pembeli memilih akun → klik `Pesan via WA` → detail produk dikirim ke WhatsApp admin.
Admin menambah listing dari dashboard, termasuk rank, harga, status, dan galeri.

## Catatan produksi

- Ganti `WHATSAPP_NUMBER`, password admin, dan `APP_KEY` sebelum deploy.
- Jalankan php artisan storage:link agar logo, banner, dan galeri upload tampil dari /storage/image/....
- Banner utama tersedia di storage/app/public/image/banner/neverland-store-hero.png.
- Domain produksi yang disiapkan: store.neverlandfc.my.id.
- Aktifkan HTTPS dan gunakan kredensial PostgreSQL khusus aplikasi.
