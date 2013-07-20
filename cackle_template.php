<?php

//Define timer
define('CACKLE_TIMER', 1);
//define('AVATAR_PATH', $config['http_home_url'] . "/uploads/fotos/");
include ('cackle_sync.php');


class Cackle{

    function Cackle($init=true){
        global $db;
        if ($init){
            $this->cackle_auth();
            $sync = new Sync();
            if ($this->time_is_over(CACKLE_TIMER)){
                $sync->comment_sync();
            }
            $this->cackle_display_comments();
            $db->query( "UPDATE " . PREFIX . "_post SET comm_num=comm_num-1 where id='1333'" );
        }
    }
    
    function time_is_over($cron_time){
        $cackle_api = new CackleAPI();
        $get_last_time = $cackle_api->cackle_get_param("last_time");
        $now=time();
        $set_time = $cackle_api->cackle_set_param("last_time",$now);
        if ($get_last_time==""){
            $set_time = $cackle_api->cackle_set_param("last_time",$now);
            return time();
        }
        else{
            if($get_last_time + $cron_time > $now){
                return false;
            }
            if($get_last_time + $cron_time < $now){
                $set_time = $cackle_api->cackle_set_param("last_time",$now);
                return $cron_time;
            }
        }
    }

    function cackle_auth() {
        $cackle_api = new CackleAPI();
        $siteApiKey = $cackle_api->cackle_get_param("site_api");
        $timestamp = time();
        if ($_SESSION['dle_user_id']) {
            $user_id = $_SESSION['dle_user_id'];
            $user_info = $cackle_api->db_connect("select * from ".PREFIX."_users where user_id = $user_id");
            $user_info = $user_info[0];
            $user = array(
                'id' => $user_id,
                'name' => $user_info["name"],
                'email' => $user_info["email"],
                'avatar' => AVATAR_PATH . $user_info["foto"]
            );
            $user_data = base64_encode(json_encode($user));
        } else {
            $user = '{}';
            $user_data = base64_encode($user);
        }
        $sign = md5($user_data . $siteApiKey . $timestamp);
        return "$user_data $sign $timestamp";
    }


     function cackle_comment( $comment) {
        
        ?><li  id="cackle-comment-<?php echo $comment['id']; ?>">
              <div id="cackle-comment-header-<?php echo $comment['comment_id']; ?>" class="cackle-comment-header">
                  <cite id="cackle-cite-<?php echo $comment['id']; ?>">
                  <?php if($comment['autor']) : ?>
                      <a id="cackle-author-user-<?php echo $comment['id']; ?>" href="#" target="_blank" rel="nofollow"><?php echo $comment['autor']; ?></a>
                  <?php else : ?>
                      <span id="cackle-author-user-<?php echo $comment['id']; ?>"><?php echo $comment['name']; ?></span>
                  <?php endif; ?>
                  </cite>
              </div>
              <div id="cackle-comment-body-<?php echo $comment['id']; ?>" class="cackle-comment-body">
                  <div id="cackle-comment-message-<?php echo $comment['id']; ?>" class="cackle-comment-message">
                  <?php echo $comment['text']; ?>
                  </div>
              </div>
          </li><?php } 
    
     
     function cackle_display_comments(){
         global $cackle_api;
         $cackle_api = new CackleAPI();?>
         <div id="mc-container">
            <div id="mc-content">
                <ul id="cackle-comments">
                <?php $this->list_comments(); ?>
                </ul>
            </div>
        </div>
        <script type="text/javascript">

        var mcSite = '<?php echo $cackle_api->cackle_get_param("site_id"); //from cackle's admin panel?>';
        <?php
            //$mcChannel = $_GET["newsid"]
            $mcChannel = 5;
            ?>
        var mcChannel = '<?php echo($mcChannel)?>';
            <?php if ($cackle_api->cackle_get_param('cackle_sso') == 1) { ?>
        var mcSSOAuth = '<?php echo $this->cackle_auth(); ?>';
            <?php } ?>
        document.getElementById('mc-container').innerHTML = '';
        (function() {
            var mc = document.createElement('script');
            mc.type = 'text/javascript';
            mc.async = true;
            mc.src = 'http://cackle.me/mc.widget-min.js';
            (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(mc);
        })();
        </script>
<?php }
    function get_local_comments(){
        //getting all comments for special post_id from database.
        $cackle_api = new CackleAPI();
        $post_id = $_GET["newsid"]; //ex.
        $get_all_comments = $cackle_api->db_connect("select * from ".PREFIX."_comments where post_id = 5 and approve = 1;");
        return $get_all_comments;
    }
    function list_comments(){
        $obj = $this->get_local_comments();
        foreach ($obj as $comment) {
            $this->cackle_comment($comment);
        }
    }
}
$a = new Cackle();
?>
