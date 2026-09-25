# StudioAI — Backend

Laravel 12 API ve özel CSS admin paneli. Kurulum, yapılandırma ve API açıklamaları için kök dizindeki [README.md](../README.md) ve [PROJE_DOKUMANI.md](../PROJE_DOKUMANI.md) dosyalarına bakın.

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed && php artisan storage:link
composer run dev
```
