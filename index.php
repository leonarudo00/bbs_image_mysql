<?php
ini_set('display_errors', 1); // エラーログをHTMLに出力

// phpファイルの読み込み
require 'ImageUploader.php';

define('DB_DATABASE'    , 'bbs_db');	// DB名
define('DB_USERNAME'    , 'dbuser');	// ユーザ名
define('DB_PASSWORD'    , 'dbuser');	// パスワード
define('PDO_DSN'        , 'mysql:host=localhost;dbname=' . DB_DATABASE);	//データソース名
define('MAX_FILE_SIZE'  , 1 * 1024 * 1024); // !MB
define('THUMBNAIL_WIDTH', 400); // サムネイルの幅
define('IMAGES_DIR'     , __DIR__ . '/images');  // 元画像を保存するディレクトリ
define('THUMBNAIL_DIR'  , __DIR__ . '/thumbs'); // サムネイルを保存するディレクトリ

// 名前空間MyAppのクラスImageUploaderをインスタンス化
$uploader = new \MyApp\ImageUploader();
// PDOオブジェクト
$db;

// GDが入っているかチェック
if(!function_exists('imagecreatetruecolor')){
  echo 'GD not installed';
  exit;
}

try{
	// PDOオブジェクトの作成
	$db = new PDO(PDO_DSN, DB_USERNAME, DB_PASSWORD);
	// 例外を投げるようにエラーモードを設定
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
	// 例外のメッセージを出力
	echo $e->getMessage();
	// 終了
	exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  // 画像をローカル環境にアップロード
  $uploader->upload();
  // アップロードした画像の中身を取得
  $image = file_get_contents($uploader->savePath);
  // データベースに挿入
  $insert = $db->prepare('INSERT INTO' . ' images (name, image, extension) values (:name, :image, :extension)' );
  $insert->bindValue(':name', $uploader->imageFileName, PDO::PARAM_STR);
  $insert->bindValue(':image', $image, PDO::PARAM_STR);
  $insert->bindValue(':extension', $uploader->imageType, PDO::PARAM_STR);
  $insert->execute();
  // リダイレクト
  header('Location: http://' . $_SERVER['HTTP_HOST']);
}

// 画像を取得(フォルダから)
$images = $uploader->getImages();
// 画像を取得(データベースから)
$getImage = $db->prepare('SELECT * FROM images');
$getImage->execute();
$rows = $getImage->fetchAll(PDO::FETCH_ASSOC);
// 逆順に並び替え
arsort($rows);

// htmlで表示可能な文字列に変換
// text/plain→text/html
function h($s){
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Image Uploader</title>
  <style>
  body{
    text-align: center;
    font-family: Arial, sans-serif;
  }
  ul{
    list-style: none;
    margin: 0;
    padding: 0;
  }
  li{
    margin-bottom: 5px;
  }
  </style>
</head>
<body>
  <!-- データベースから取得した画像の出力 -->
  <ul>
  <?php foreach($rows as $row) :?>
    <li>
      <img src="display.php?id=<?php echo h($row['id']); ?>">
    </li>
  <?php endforeach;?>
  </ul>

  <!-- 投稿フォーム -->
  <form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo h(MAX_FILE_SIZE); ?>">
    <input type="file" name="image">
    <input type="submit" value="upload">
  </form>

  <!-- フォルダから取得した画像の出力 -->
  <ul>
    <?php foreach($images as $image) : ?>
      <li>
        <a href="<?php echo h(basename(IMAGES_DIR) . '/' . basename($image));?>">
          <img src="<?php echo h($image);?>">
        </a>
      </li>
    <?php endforeach;?>
  </ul>
</body>
</html>
