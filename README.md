# attendance-management-system
## 環境構築
### Dockerビルド
1. `git clone git@github.com:mizuho-35/attendance-management-system.git`
2. `cd attendance-management-system` ←クローンしたフォルダに移動
3. `docker-compose up -d --build`
- MacのM1・M2チップのPCの場合、`no matching manifest for linux/arm64/v8 in the manifest list entries`のメッセージが表示されビルドができない場合があります。 エラーが発生する場合は、docker-compose.ymlファイルの「mysql」内に「platform」の項目を追加して記載してください

```
mysql:
    platform: linux/x86_64
    image: mysql:8.0.26
    environment:
```
- Windows/WSL2環境で、MySQLのコンテナが起動しない場合は以下を実行してください。
1. Docker Desktop を完全に終了
2. `cd ~/coachtech/attendance-management-system`←attendance-management-systemのディレクトリに移動
3. `rm -rf docker/mysql/data`←docker/mysql/dataの中身を削除

### Laravel環境構築
1. `docker-compose exec php bash`
2. `composer install`
3. `cp .env.example .env`　←環境変数を変更
4. .env以下の環境変数を追加
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="test@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```
5. アプリケーションキーの作成
```
php artisan key:generate
```
6. マイグレーションファイルの実行
```
php artisan migrate
```
7. シーディングの実行
```
php artisan db:seed
```
## 使用技術（実行環境）
- PHP 8.1
- Laravel　8.83.29
- MySQL 8.0.35
- mailhog （SMTP:1025 / Web UI:8025）

## ER図
<img width="820" height="731" alt="index drawio" src="https://github.com/user-attachments/assets/bbcc63cd-37d1-4b9d-8615-6dc1f655f9b7" />

## URL
- 開発環境: http://localhost/
- phpMyAdmin: http://localhost:8080/

## テストアカウント
- 名前：管理者
- メールアドレス：admin@example.com
- パスワード：password
---
- 名前：スタッフ
- メールアドレス：staff@example.com
- パスワード：password

## ユニットテスト環境構築・実行
以下の手順で、ユニットテストを実行しました。
- `docker exec attendance-management-system-mysql-1 sh`
- `mysql -u root -p`パスワードでrootと入力
- `CREATE DATABASE demo_test;`
- `exit`
- `docker-compose exec php bash`
- `php artisan migrate:fresh --env=testing`
- `./vendor/bin/phpunit --testsuite=Feature`
※.env.testingにもStripeのAPIキーを設定してください。
