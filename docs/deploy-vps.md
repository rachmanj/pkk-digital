# Deploy Buku PKK Digital ke VPS (Ubuntu 24.04)

Panduan ini untuk VM produksi (contoh: IDCloudhost, IP `103.59.95.229`) dengan Docker 29, nginx di host pada port 80, dan UFW yang hanya membuka 22, 80, dan 443. Aplikasi Docker hanya di-bind ke `127.0.0.1:8080`.

## Prasyarat di VM

1. Docker Engine dan plugin Compose terpasang (`docker compose version`).
2. Pengguna deploy (mis. `pkkadmin`) ada di grup `docker`.
3. Nginx host sudah berjalan di port 80.
4. Direktori kerja deploy, mis. `~/pkk-digital`.
5. **Jangan** membuka port container ke publik; cukup `127.0.0.1:8080` seperti di `docker-compose.prod.yml`. Port baru di UFW hanya diperlukan jika suatu saat Anda mem-bind layanan ke `0.0.0.0`.

## Persiapan `.env.production` di VM

Sebelum stack pertama kali dijalankan:

```bash
cd ~/pkk-digital
cp .env.production.example .env.production
nano .env.production   # atau editor lain
```

Isi minimal:

| Variabel | Keterangan |
|----------|------------|
| `DB_PASSWORD` / `MYSQL_PASSWORD` | Sandi user aplikasi MySQL (sama) |
| `MYSQL_ROOT_PASSWORD` | Sandi root MySQL |
| `ADMIN_PASSWORD` | Sandi akun admin (`admin@pkk.test`) untuk seeder |
| `APP_KEY` | Lihat langkah di bawah |

`MYSQL_USER` / `DB_USERNAME` dan `MYSQL_DATABASE` / `DB_DATABASE` harus selaras (contoh di `.env.production.example`).

### `APP_KEY` (wajib sebelum produksi cache config)

Contoh di **container** setelah image sudah ada di VM (tanpa menjalankan stack penuh):

```bash
cd ~/pkk-digital
docker compose -f docker-compose.prod.yml run --rm --no-deps --env-file .env.production \
  --entrypoint php app artisan key:generate --show --force
```

Salin keluaran (base64) ke baris `APP_KEY=` di `.env.production`, format:

```dotenv
APP_KEY=base64:....=
```

Tanpa `APP_KEY`, perintah `config:cache` saat startup container produksi akan gagal.

## Deploy image dari mesin build (dea-geekom)

Dari root repositori di laptop/build server:

```bash
chmod +x deploy/production/deploy.sh
./deploy/production/deploy.sh
```

Skrip mem-build `pkk-digital-app:latest`, mengekspor ke `pkk-digital-app.tar.gz`, mengirim ke VM lewat `scp`, memuat image, lalu `docker compose -f docker-compose.prod.yml up -d --no-build --wait`.

Pastikan `.env.production` sudah ada di `~/pkk-digital` di VM sebelum menjalankan skrip (Compose membacanya saat `up`).

## Migrasi dan seeder (manual)

Entrypoint container menjalankan `php artisan migrate --force` otomatis; **seeder tidak** dijalankan otomatis.

Setelah stack sehat, isi `ADMIN_PASSWORD` di `.env.production`, lalu:

```bash
cd ~/pkk-digital
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --class=MasterSeeder --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --class=UserSeeder --force
```

`UserSeeder` memakai `ADMIN_PASSWORD` dari environment container (dari `env_file`).

## Import data buku 2026

Setelah master data ada:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan pkk:import-buku2026 --execute --force
```

Pastikan berkas sumber impor tersedia di image/volume sesuai kebutuhan perintah (lihat dokumentasi perintah di aplikasi).

## Nginx di host (reverse proxy)

Salin contoh vhost:

```bash
sudo cp deploy/production/nginx-host.conf.example /etc/nginx/sites-available/pkk-digital
sudo ln -sf /etc/nginx/sites-available/pkk-digital /etc/nginx/sites-enabled/pkk-digital
sudo nginx -t
sudo systemctl reload nginx
```

Sesuaikan `server_name` bila domain sudah aktif.

## Log container

```bash
cd ~/pkk-digital
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f mysql
docker compose -f docker-compose.prod.yml logs app --tail=100
```

Log Laravel juga di volume `app_storage` (`storage/logs` di dalam container).

## Queue worker

Worker sudah dijalankan oleh Supervisor di container (`queue:work --sleep=3 --tries=3 --max-time=3600`). Cek proses:

```bash
docker compose -f docker-compose.prod.yml exec app supervisorctl status
```

Restart worker bila perlu:

```bash
docker compose -f docker-compose.prod.yml exec app supervisorctl restart laravel-worker
```

## Healthcheck dan status

```bash
docker compose -f docker-compose.prod.yml ps
curl -f http://127.0.0.1:8080/login
```

## UFW

Default: hanya 22, 80, 443. Binding `127.0.0.1:8080` **tidak** memerlukan aturan UFW baru. Bila suatu layanan Docker di-bind ke `0.0.0.0:<port>`, buka port tersebut secara eksplisit, mis. `sudo ufw allow <port>/tcp`.

## Build image lokal (opsional, di VM atau build machine)

```bash
docker build -t pkk-digital-app:latest -f docker/production/Dockerfile .
```

Produksi frontend tidak memerlukan `npm run build`; aset AdminLTE dilayani dari `public/vendor`.
