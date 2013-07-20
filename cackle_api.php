<?php
/*Enter database settings*/
define('LOCAL_DB_HOST', "localhost");
define('LOCAL_DB_NAME', "dle");
define('LOCAL_DB_USER', "root");
define('LOCAL_DB_PASSWORD', "");
define('PREFIX', "dle");

class CackleAPI{

    function CackleAPI(){
        $this->last_error = null;
    }
    function db_connect($query){
        try {
            $hd="mysql:host=" . LOCAL_DB_HOST . ";dbname=" . LOCAL_DB_NAME;
            $DBH = new PDO($hd, LOCAL_DB_USER, LOCAL_DB_PASSWORD);
            //$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            $STH = $DBH->query($query);

            #  устанавливаем режим выборки
            $STH->setFetchMode(PDO::FETCH_ASSOC);
            $x=0;
            $row=array();
            while($res = $STH->fetch()) {
                $row[$x]=$res;
                $x++;
            }
            $DBH = null;
            return $row;
        }
        catch(PDOException $e) {
            echo "invalid sql - $query - ";
            file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
        }
    }
    function conn(){
        try {
            $hd="mysql:host=" . LOCAL_DB_HOST . ";dbname=" . LOCAL_DB_NAME;
            $DBH = new PDO($hd, LOCAL_DB_USER, LOCAL_DB_PASSWORD);
            $DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

            return $DBH;
        }
        catch(PDOException $e) {
            echo "invalid sql - $query - ";
            file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
        }
    }
    function db_table_exist($table){
        $hd="mysql:host=" . LOCAL_DB_HOST . ";dbname=" . LOCAL_DB_NAME;
        $DBH = new PDO($hd, LOCAL_DB_USER, LOCAL_DB_PASSWORD);
        $tableExists = (gettype($DBH->exec("SELECT count(*) FROM $table")) == "integer")?true:false;
        return $tableExists;
    }
    function db_column_exist($table,$column){
        if ($this->db_table_exist($table)){
            $hd="mysql:host=" . LOCAL_DB_HOST . ";dbname=" . LOCAL_DB_NAME;
            $DBH = new PDO($hd, LOCAL_DB_USER, LOCAL_DB_PASSWORD);
            $quer= "SHOW COLUMNS FROM $table LIKE '$column'";
            $column_exist = $DBH->query($quer)->fetch();
            $column_exist = $column_exist['Field'];
            //$column_exist = (gettype($DBH->query("SHOW COLUMNS FROM $table LIKE '$column''")) == "integer")?true:false;
            return $column_exist;
            //return $quer;
        }
        else {
            return false;
        }
    }
    function cackle_set_param($param, $value){
        $hd="mysql:host=" . LOCAL_DB_HOST . ";dbname=" . LOCAL_DB_NAME;
        $DBH = new PDO($hd, LOCAL_DB_USER, LOCAL_DB_PASSWORD);

        if ($this->db_table_exist("".PREFIX."_cackle")){
            $DBH->query("delete from ".PREFIX."_cackle where param = '$param'");
            $DBH->query("insert into ".PREFIX."_cackle (param, value) values ('$param','$value')");
        }
        else{
            $this->db_connect("CREATE TABLE ".PREFIX."_cackle (param VARCHAR(100) NOT NULL DEFAULT '',value VARCHAR(100) NOT NULL DEFAULT '')");
        }
    }

    function cackle_get_param($param){
        $hd="mysql:host=" . LOCAL_DB_HOST . ";dbname=" . LOCAL_DB_NAME;
        $DBH = new PDO($hd, LOCAL_DB_USER, LOCAL_DB_PASSWORD);

        if ($this->db_table_exist("".PREFIX."_cackle")){
            $ex = $DBH->query("select value from ".PREFIX."_cackle where param = '$param'")->fetch();
            return $ex['value'];
        }
        else{
            $this->db_connect("CREATE TABLE ".PREFIX."_cackle (param VARCHAR(100) NOT NULL DEFAULT '',value VARCHAR(100) NOT NULL DEFAULT '')");
        }
    }
    function cackle_db_prepare(){

        if ($this->db_table_exist("".PREFIX."_comments")){
            $this->db_connect("ALTER TABLE ".PREFIX."_comments ADD user_agent VARCHAR(64) NOT NULL default ''");
           // $this->db_connect("ALTER TABLE ".PREFIX."_comments MODIFY 'user_agent' varchar(64) NOT NULL default ''");
        }

    }
    function import_wordpress_comments(&$wxr, $timestamp, $eof) {
        if( $curl = curl_init() ) {
            curl_setopt($curl, CURLOPT_URL, 'http://import.cackle.me/api/import-wordpress-comments');

            curl_setopt ($curl, CURLOPT_TIMEOUT, 60);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt ($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
            curl_setopt ($curl, CURLOPT_REFERER, "localhost1");
            curl_setopt ($curl, CURLOPT_ENCODING, "gzip, deflate");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    "Content-type" => "application/x-www-form-urlencoded; charset='utf-8'",

                    "Accept" =>	"text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"

                )
            );
            $data = array(

                'siteId' =>$this->cackle_get_param("site_id"),
                'accountApiKey' => $this->cackle_get_param("account_api"),
                'siteApiKey' => $this->cackle_get_param("site_api"),

                'wxr' => $wxr,

                'eof' => (int)$eof

            );
            $query = http_build_query($data, '', '&');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $query

            );
            $response = curl_exec($curl);
            curl_close($curl);
        }





        if ($response['body']=='fail') {
            $this->api->last_error = $response['body'];
            return -1;
        }
        $data = $response;
        if (!$data || $data== 'fail') {
            return -1;
        }

        return $data;
    }
    function get_last_error() {
        if (empty($this->last_error)) return;
        if (!is_string($this->last_error)) {
            return var_export($this->last_error);
        }
        return $this->last_error;
    }
    function curl($url) {
        $ch = curl_init();
        $php_version = phpversion();
        $useragent = "Drupal";
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("referer" =>  "localhost"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}