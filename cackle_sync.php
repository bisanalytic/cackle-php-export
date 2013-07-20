<?php
include ('cackle_api.php');
class Sync {
    function Sync() {
        $cackle_api = new CackleAPI();
        $this->accountApiKey = $cackle_api->cackle_get_param("account_api");
        $this->siteApiKey = $cackle_api->cackle_get_param("site_api");
    }

    function comment_sync_all($a = "") {
        $response_size = $this->comment_sync();

        if ($response_size == 100 && $a = "all_comments") {
            while ($response_size == 100) {
                $response_size = $this->comment_sync();
            }
        }
        return "success";
    }

    function comment_sync(){
        $cackle_api = new CackleAPI();
        $cackle_last_modified = 0;
        $get_last_modified = $cackle_api->cackle_get_param("last_modified");
        $get_last_modified = str_replace(",",".",$get_last_modified);
        if ($get_last_modified!=""){
            $cackle_last_modified = $get_last_modified;
        }
        function to_i($number_to_format){
            return number_format($number_to_format, 0, '', '');
        }
        $cackle_last_modified = to_i($get_last_modified);
        $params1 = "accountApiKey=$this->accountApiKey&siteApiKey=$this->siteApiKey&modified=$cackle_last_modified";
        $host="cackle.me/api/comment/mutable_list?$params1";


        function curl($url)
        {
            $ch = curl_init();
            curl_setopt ($ch, CURLOPT_URL,$url);
            curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
            curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec ($ch);
            curl_close($ch);

            return $result;
        }
        $response = curl($host);
        $response = $this->cackle_json_decodes($response);
        $this->push_comments($response);
        return count($response['comments']);

    }

    function to_i($number_to_format){
        return number_format($number_to_format, 0, '', '');
    }


    function cackle_json_decodes($response){

        $obj = json_decode($response,true);

        return $obj;
    }

    function insert_comm($comment,$status){

        /*
         * Here you can convert $url to your post ID
         */

        $url = $comment['channel'];

        if ($comment['author']!=null){
            $author_name = $comment['author']['name'];
            $author_email=  $comment['author']['email'];
            $author_www = $comment['author']['www'];
            $author_avatar = $comment['author']['avatar'];
            $author_provider = $comment['author']['provider'];
            $author_anonym_name = null;
            $anonym_email = null;
        }
        else{
            $author_name = $comment['anonym']['name'];
            $author_email= $comment['anonym']['email'];;
            $author_www = null;
            $author_avatar = null;
            $author_provider = null;
            $author_anonym_name = $comment['anonym']['name'];
            $anonym_email = $comment['anonym']['email'];

        }
        $get_parent_local_id = null;
        $comment_id = $comment['id'];
        $comment_modified = $comment['modified'];
        // if ($comment['parentId']) {
        //     $comment_parent_id = $comment['parentId'];
        //     $sql = "select comment_id from comment where user_agent='Cackle:$comment_parent_id';";
        //     $get_parent_local_id = $this->db_connect($sql, "comment_id"); //get parent comment_id in local db
        // }
        //You should define post_id  in $commentdata according you cms engine(ex. maybe your cms have function to return post_id by page's url)
        $cackle_api = new CackleAPI();
        if ($cackle_api->cackle_get_param("last_comment")==''){
            $cackle_api->cackle_db_prepare();
        }
        $date =strftime("%Y-%m-%d %H:%M:%S", $comment['created']/1000);
        $ip = $comment['ip'];
        $message = $comment['message'];
        $user_agent = 'Cackle:' . $comment['id'];

        $commentdata = array(
            'post_id' => $url,
            'autor' =>  $author_name,
            'email' =>  $author_email,
            'date' => strftime("%Y-%m-%d %H:%M:%S", $comment['created']/1000),
            'ip' => $comment['ip'],
            'text' =>$comment['message'],
            'approve' => $status,
            'user_agent' => 'Cackle:' . $comment['id'],


        );
        $conn = $cackle_api->conn();
        $sql = "insert into " . PREFIX ."_comments (post_id,autor,email,date,ip,text,approve,user_agent) values (:url, :author_name, :author_email, :date, :ip, :message, :status, :user_agent ) ";
	    $q = $conn->prepare($sql);
	    $q->execute(
                array(
                    ':url'=>$url,
                    ':author_name'=>$author_name,
                    ':author_email'=>$author_email,
                    ':date'=>$date,
                    ':ip'=>$ip,
                    ':message'=>$message,
                    ':status'=>$status,
                    ':user_agent'=>$user_agent,


                ));
        $q=null;

        $cackle_api->cackle_set_param("last_comment",$comment_id);
        $get_last_modified = $cackle_api->cackle_get_param("last_modified");
        $get_last_modified = (int)$get_last_modified;
        if ($comment['modified'] > $get_last_modified) {
            $cackle_api->cackle_set_param("last_modified",(string)$comment['modified']);
        }

    }

    function comment_status_decoder($comment) {
        $status;
        if (strtolower($comment['status']) == "approved") {
            $status = 1;
        }
        elseif (strtolower($comment['status'] == "pending") || strtolower($comment['status']) == "rejected") {
            $status = 0;
        }
        elseif (strtolower($comment['status']) == "spam") {
            $status = 0;
        }
        elseif (strtolower($comment['status']) == "deleted") {
            $status = 0;
        }
        return $status;
    }

    function update_comment_status($comment_id, $status, $modified, $comment_content) {
        $cackle_api = new CackleAPI();
        $sql = "update ". PREFIX ."_comments set approve = ? , text = ? where user_agent = ?";
        $conn = $cackle_api->conn();
        $q = $conn->prepare($sql);
        $q->execute(array($status,$comment_content,"Cackle:$comment_id"));
        $q = null;
        //$cackle_api->db_connect("update dle_comments set approve = $status, text = '$comment_content' where user_agent = 'Cackle:$comment_id';");
        $cackle_api->cackle_set_param("last_modified",$modified);

    }

    function push_comments ($response){
        $obj = $response['comments'];
        if ($obj) {
            foreach ($obj as $comment) {
                $cackle_api = new CackleAPI();
                $get_last_modified = $cackle_api->cackle_get_param("last_modified");
                $get_last_comment = $cackle_api->cackle_get_param("last_comment");
                //$get_last_comment = $this->db_connect("select common_value from common where `common_name` = 'last_comment'","common_value");
                //$get_last_modified = $this->db_connect("select common_value from common where `common_name` = 'last_modified'","common_value");
                if ($comment['id'] > $get_last_comment) {
                    $this->insert_comm($comment, $this->comment_status_decoder($comment));
                } else {
                    if ($get_last_modified==""){
                        $get_last_modified == 0;
                    }
                    if ($comment['modified'] > $get_last_modified) {
                        $this->update_comment_status($comment['id'], $this->comment_status_decoder($comment), $comment['modified'], $comment['message'] );
                    }
                }

            }
        }
    }

}
?>