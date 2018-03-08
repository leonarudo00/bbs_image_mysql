<?php
namespace MyApp;

class ImageUploader{

  public $imageFileName; // 保存した画像のファイル名
  public $imageType; // 保存する画像の拡張子
  public $savePath; // 画像の保存先のパス

  public function upload()
  {
    try{
      // エラーチェック
      $this->validateUpload();

      // 拡張子チェック
      $extension = $this->validateImageType();

      // 保存
      $this->savePath = $this->save($extension);

      // サムネイル作成
      $this->createThumbnail($this->savePath);
    } catch(\Exception $e){
      echo $e->getMessage();
      exit;
    }
    // リダイレクト
    //header('Location: http://' . $_SERVER['HTTP_HOST']);
    //exit;
  }

  // 画像の取得
  public function getImages()
  {
    $images = [];
    $files = [];
    // ディレクトリハンドルをオープン
    $imageDir = opendir(IMAGES_DIR);
    // ディレクトリからファイルを取得、取得するファイルがなくなったら終了
    while(false !== ($file = readdir($imageDir))){
      // ファイル以外のものは無視
      if($file === '.' || $file === '..'){
        continue;
      }
      $files[] = $file;
      // サムネイルがあればそちらを取得
      if(file_exists(THUMBNAIL_DIR . '/' . $file)){
        $images[] = basename(THUMBNAIL_DIR) . '/' . $file;
      } else {
        $images[] = basename(IMAGES_DIR) . '/' . $file;
      }
    }
    // $files順に逆向きで$imagesをソート
    // サムネイルのある画像とない画像でパスが異なるのでfilesを基準にソートする
    array_multisort($files, SORT_DESC, $images);
    return $images;
  }

  // サムネイルの作成
  private function createThumbnail($savePath)
  {
    // 画像のサイズを取得
    $imageSize = getimagesize($savePath);
    $width = $imageSize[0];
    $height = $imageSize[1];
    // 指定サイズより大きければサムネイルを作成
    if($width > THUMBNAIL_WIDTH){
      $this->createThumbnailMain($savePath, $width, $height);
    }
  }
  // サムネイル作成の主な処理部
  private function createThumbnailMain($savePath, $width, $height)
  {
    // 元の画像の画像リソースを作成
    switch ($this->imageType) {
      case IMAGETYPE_GIF:
        $srcImage = imagecreatefromgif($savePath);
        break;
      case IMAGETYPE_JPEG:
        $srcImage = imagecreatefromjpeg($savePath);
        break;
      case IMAGETYPE_PNG:
        $srcImage = imagecreatefrompng($savePath);
        break;
    }
    // サムネイルの高さを幅の比率から算出
    $thumbHeight = round($height * THUMBNAIL_WIDTH / $width);
    // サムネイルのリソースを作成
    $thumbImage = imagecreatetruecolor(THUMBNAIL_WIDTH, $thumbHeight);
    // サムネイルを生成
    imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0,
      THUMBNAIL_WIDTH, $thumbHeight, $width, $height);

    // サムネイルを保存
    switch($this->imageType){
      case IMAGETYPE_GIF:
        imagegif($thumbImage, THUMBNAIL_DIR . '/' . $this->imageFileName);
        break;
      case IMAGETYPE_JPEG:
        imagejpeg($thumbImage, THUMBNAIL_DIR . '/' . $this->imageFileName);
        break;
      case IMAGETYPE_PNG:
        imagepng($thumbImage, THUMBNAIL_DIR . '/' . $this->imageFileName);
        break;
    }
  }
  // 画像の保存
  private function save($extension)
  {
    // 画像を保存する際のファイル名を決定
    $this->imageFileName = sprintf(
      '%s_%s.%s',
      time(), // 時刻
      sha1(uniqid(mt_rand(), true)), // ランダムなID
      $extension // 拡張子
    );
    // 保存する画像のパス
    $savePath = IMAGES_DIR . '/' . $this->imageFileName;
    // 画像を指定したパスに保存
    $res = move_uploaded_file($_FILES['image']['tmp_name'], $savePath);
    // 画像の保存に失敗したらエラーログを返す
    if($res === false){
      throw new \Exception('Could not upload!');
    }

    return $savePath;
  }

  // 拡張子のチェック
  private function validateImageType()
  {
    // 拡張子を取得
    $this->imageType = exif_imagetype($_FILES['image']['tmp_name']);

    // 拡張子に応じて文字列を返す
    switch($this->imageType){
      case IMAGETYPE_GIF:
        return 'gif';
      case IMAGETYPE_JPEG:
        return 'jpg';
      case IMAGETYPE_PNG:
        return 'png';
      default:
        throw new \Exception('PNG/JPEG/GIF only!');
    }
  }
  // エラーチェック
  private function validateUpload()
  {
    // ファイルがない・エラーログが空の場合はエラー
    if(!isset($_FILES['image']) || !isset($_FILES['image']['error'])){
      throw new \Exception('Upload Error!');
    }

    // エラーログのチェック
    switch($_FILES['image']['error']){
      // 問題なし
      case UPLOAD_ERR_OK:
        return true;
      // 設定よりサイズが大きければエラー
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        throw new \Exception('File too large');
      // 何かしらエラーが出たらそのログを返す
      default:
        throw new \Exception('Err: ' . $_FILES['image']['error']);
    }
  }
}
?>
