<?php
ini_set('display_errors', 1); // エラーログをHTMLに出力

define('DB_DATABASE'    , 'bbs_db');	// DB名
define('DB_USERNAME'    , 'dbuser');	// ユーザ名
define('DB_PASSWORD'    , 'dbuser');	// パスワード
define('PDO_DSN'        , 'mysql:host=localhost;dbname=' . DB_DATABASE);	//データソース名

$db;
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

// 任意のIDの画像を取得
$getImage = $db->prepare('SELECT * FROM images where id=' . $_GET['id']);
$getImage->execute();
// データをキーごとに分割
$row = $getImage->fetch(PDO::FETCH_ASSOC);
// 出力
switch($row['extension']){
	case IMAGETYPE_JPEG:
		header('Content-type: image/jpeg');
		break;
	case IMAGETYPE_PNG:
		header('Content-type: image/png');
		break;
	case IMAGETYPE_GIF:
		header('Content-type: image/gif');
		break;
}
echo ($row['image']);
?>
