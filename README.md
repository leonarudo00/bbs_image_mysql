# bbs_image_mysql
PHPとMySQLによる画像アップローダ

## テーブル構成
| Field    | Type         | Null | Key | Default | Extra          |  
|---       |---           |---   |---  |---      |---             |
| id       | int(10) unsigned      | NO   | PRI | NULL    | auto_increment |  
| name     | varchar(50) | YES  |     | NULL    |                |  
| image  | mediumblob | YES  |     | NULL    |                |  
| extension | varchar(5)      | YES  |     | NULL    |                |
