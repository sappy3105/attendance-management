# 勤怠管理アプリ

## 環境構築

### 1. リポジトリのクローンと環境準備（ローカル環境）

1. プロジェクトをクローンし、Dockerコンテナを起動します。

   ```bash
   git clone git@github.com:sappy3105/attendance-management.git
   cd attendance-management
   docker-compose up -d --build
   ```

2. `.env.example` をコピーして `.env` を作成し、環境準備をします。

   ```bash
   docker-compose exec php bash
   cp .env.example .env
   ```

### 2. 各種サービスの設定 (.env)

#### 2-1. データベース設定

`.env`ファイルに以下の環境変数を追加してください。

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```


#### 2-3. 開発環境でのメール認証システム設定 (Mailtrap)

本プロジェクトでは、メール認証のテストに [Mailtrap](https://mailtrap.io/) を使用しています。  
機能を再現するには、以下の手順で設定を行ってください。

**1. Mailtrap のセットアップ**

1. [Mailtrap公式サイト](https://mailtrap.io/)でアカウントを作成します。
2. ログイン後、左メニューの「Sandboxes」→「My Sandbox」をクリックします。
3. 「Integration」タブが選択されていることを確認し、その下の「SMTP」を選択します。
4. 表示された `Credentials` 欄の `Username` と `Password` を確認します。
5. 「My Sandbox」ページのURL(`https://mailtrap.io/inboxes/数字/messages`) をコピーします。

**2. 環境設定 (.env)**

プロジェクト直下の `.env` ファイルに、確認した値を反映させてください。

```env
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=確認したユーザー名
MAIL_PASSWORD=確認したパスワード
MAIL_DASHBOARD_URL=「My Sandbox」ページのURL
```

### 3. アプリケーションの初期化（コンテナ内操作）

以下のコマンドを実行して、PHPコンテナ内でアプリケーションの構築を行います。

```bash
# コンテナ内に入る（一度だけ実行）
docker-compose exec php bash
```

--- 以下、コンテナ内での操作 ---

#### 3-1. パッケージのインストール

```bash
composer install
```

#### 3-2. アプリケーションキーの生成と反映

```bash
php artisan key:generate
php artisan config:clear
```

#### 3-3. データベースの構築

```bash
php artisan migrate
php artisan db:seed
```

#### 3-4. ストレージリンクの作成

商品画像などのアップロードファイルを表示するために、ストレージへのシンボリックリンクを作成する必要があります。

```bash
php artisan storage:link
```

#### 3-5. ディレクトリの権限設定

ブラウザでアクセスした際に Permission denied エラーが発生する場合、以下のコマンドを実行して書き込み権限を付与してください。

```bash
chmod -R 777 storage bootstrap/cache
```

#### 3-6. フロントエンドの環境構築

本プロジェクトでは Autoprefixer を使用して CSS のブラウザ互換性を管理しています。スタイルを正しく反映させるため、以下の手順を実行してください。

```bash
# 1. パッケージのインストール（初回のみ）
npm install

# 2. ビルドの実行
# 開発用（変更を確認したい場合など）
npm run dev

# 本番用（ファイルを最適化・圧縮したい場合）
npm run production
```

もし `npm run dev` でエラーが出る場合は、以下のコマンドを試してから再度ビルドしてください。

```bash
npm install postcss-loader autoprefixer --save-dev
```

#### 3-7. 完了したらコンテナを抜ける

```bash
exit
```


## 使用技術（実行環境）

- PHP 8.3.30
- Laravel 12.53.0
- MySQL 8.0.26
- nginx 1.21.1

## URL

- 開発環境： http://localhost/
- phpMyAdmin： http://localhost:8080/

## 動作確認ガイド（動作確認用データの構成）

本プロジェクトでは、リレーションを考慮した10パターンの動作確認用データを投入しています。  
セットアップ完了後、以下の動作確認用データを使用して各機能を確認いただけます。

**1. データの初期化**

リポジトリをクローンし、環境構築が完了した後、以下のコマンドを実行してデータベースを最新の状態にします。  
※初回シーディング直後の場合は、この作業はスキップしてください。

```bash
docker-compose exec php bash
php artisan migrate:fresh --seed
```

**2. 動作確認用アカウント**

動作確認には以下の固定ユーザーを使用してください。パスワードは全て共通です。

| ID  | ユーザー名      | 認証有無                   | メールアドレス    | パスワード |
| :-: | :-------------- | :------------------------- | :---------------- | :--------- |
|  1  | テストユーザー1 | 認証済み                   | test1@example.com | password   |
|  2  | テストユーザー2 | 未認証・認証メール送信済み | test2@example.com | password   |
|  3  | テストユーザー3 | 未認証・認証メール未送信   | test3@example.com | password   |

**3. 商品データと組み合わせ一覧**

商品一覧および各商品詳細ページにて、以下の挙動を確認できます。  
※出品者番号と購入者番号、いいね/コメントした人の番号は、動作確認用アカウントのIDで表示しています。

## ER 図