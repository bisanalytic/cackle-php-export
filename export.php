<?php

@set_time_limit(0);
@ini_set('memory_limit', '256M');
define('WXR_VERSION', '1.0');
function cackle_identifier_for_post($post) {
    return $post->ID . ' ' . $post->guid;
}

function cackle_export_wxr_missing_parents($categories) {
    if (!is_array($categories) || empty($categories))
        return array();
    foreach ($categories as $category)
        $parents[$category->term_id] = $category->parent;
    $parents = array_unique(array_diff($parents, array_keys($parents)));
    if ($zero = array_search('0', $parents))
        unset($parents[$zero]);
    return $parents;
}

function cackle_export_wxr_cdata($string) {
     $string = str_replace("<br />","\r\n",$string);
    //$string = utf8_encode($string);
    //$string = '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $string) . ']]>';
    return $string;
}

function cackle_export_wxr_site_url() {
    global $current_site;
    if (isset($current_site->domain)) {
        return 'http://' . $current_site->domain . $current_site->path;
    } else {
        return get_bloginfo_rss('url');
    }
}



function cackle_export_wp($post, $url) {
    $comments_query = "SELECT * FROM ".PREFIX."_comments WHERE post_id = $post";
    global $cackle_api;
    $comments = $cackle_api->db_connect($comments_query);

    ob_start();
    echo '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
    ?>
<rss version="2.0"
     xmlns:excerpt="http://wordpress.org/export/1.0/excerpt/"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:cackle="http://cackle.me/"
     xmlns:wfw="http://wellformedweb.org/CommentAPI/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:wp="http://wordpress.org/export/1.0/"
        >

    <channel>
        <item>
            <title>test1</title>

            <link><?php echo $url ?></link>
            <wp:post_id><?php echo $post; ?></wp:post_id>
            <?php
            if ($comments) {
                foreach ($comments as $c) {
                    ?>
                    <wp:comment>
                        <wp:comment_id><?php echo $c[id]; ?></wp:comment_id>
                        <wp:comment_author><?php echo cackle_export_wxr_cdata($c[autor]); ?></wp:comment_author>
                        <wp:comment_author_email><?php echo $c[email]; ?></wp:comment_author_email>
                        <wp:comment_author_url><?php echo $c[url]; ?></wp:comment_author_url>
                        <wp:comment_author_IP><?php echo $c[ip]; ?></wp:comment_author_IP>
                        <wp:comment_date><?php echo $c[date]; ?></wp:comment_date>
                        <wp:comment_date_gmt><?php echo $c[date]; ?></wp:comment_date_gmt>
                        <wp:comment_content><?php echo cackle_export_wxr_cdata($c[text]) ?></wp:comment_content>
                        <wp:comment_approved><?php echo $c[approve]; ?></wp:comment_approved>
                        <wp:comment_type><?php echo $c[comment_type]; ?></wp:comment_type>
                        <wp:comment_parent>0</wp:comment_parent>
                    </wp:comment>
                    <?php
                }
            } // comments ?>
        </item>
    </channel>
</rss>
<?php
    $output = ob_get_clean();
    return $output;
}

?>
