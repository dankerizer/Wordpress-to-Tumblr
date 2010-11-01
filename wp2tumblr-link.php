<?php
 
// The full path to the XML file you exported from Wordpress
$xmlFile = 'wp-allposts-published-postsiveshared-part4.xml';
// Your tumblr log in details
$tumblr_email = 'replacethis@gmail.com';
$tumblr_password = 'replacethis';
// Tumblr URL (e.g. http://thetylerhayes.tumblr.com)
$tumblrUrl = 'http://replacethis.tumblr.com';
// If a post from Wordpress is a draft, do you want it posted as private so you // have it available? True if so, False to ignore drafts
$publishDraftAsPrivate = false;
// Full path to a file that is writable, so that a log of current URL on your // wordpress blog to new URL on your tumblr can be written (good for redirects // to preserve links, etc)
$logFile = 'log.txt';
 
if (file_exists($xmlFile)) {
 
    $xml = simplexml_load_file($xmlFile);
 
} else {
 
    echo "no such file!!";
 
}
 
if (isset($xml)) {
 
    $nodes = $xml->xpath('/rss/channel/item');
 
    $count = 0;
 
    while(list( , $node) = each($nodes)) {
 
        $post_type = 'link';
        $post_title = $node->title;
 
        $post_title = str_replace("%20"," ",$post_title);
 
        $content = $node->children("http://purl.org/rss/1.0/modules/content/");
 
        $post_body = (string)$content->encoded;
		
// Comment out the following three lines if you don't want line breaks automatically converted to <p> tags
		$post_body = str_replace("\r\n\r\n", "</p><p>", $post_body);
		$post_body = str_replace("\n\n", "</p><p>", $post_body);
		$post_body = str_replace("\n", "<br />", $post_body);
		
		$tags = $node->xpath("category[@domain='tag'][@nicename]");
		foreach ($tags as $tag) {
			$tags = $tags.", ".$tag;
		}
		
		// removes "Array, " from beginning of string (which is put there by default)
		$tags = substr_replace($tags,'',0,7);
 
        $publish_status = $node->children("http://wordpress.org/export/1.0/");
 
        $date = $publish_status->post_date;
        echo $date," ";
        
        $metavalues = $xml->xpath('/rss/channel/item/wp:postmeta/wp:meta_value');
        foreach ($metavalues as $metavalue) {
        	if(substr($metavalue,0,4) === "http") {
        		$url = $metavalue;
        	} else {continue;}
        }
		
		$private = 0;
 
        if ($publish_status->status != "publish") {
 
            if (!$publishDraftAsPrivate) {
 
                continue;
 
            }
 
            $private = 1;
 
        }
 
        if ($publish_status->post_type == "attachment")
            continue;
 
        $count++;
 
        $request = array(
            'email' => $tumblr_email,
            'password' => $tumblr_password,
            'type'=> $post_type,
			'group'=> $tumblrUrl,
            'name'=> $post_title,
            'date'=> $date,
            'description'=> $post_body,
			'url'=> $url,
			'tags'=> $tags,
            'generator'=> 'wptumblr-ds',
            'private'=> $private
        );
 
        $request_data = "";
 
        $first = true;
        foreach ($request as $key=>$value) {
 
            if ($first) {
                $first = false;
            } else {
                $request_data .= "&";          
            }
 
            $request_data .= urlencode($key) . "=";
 
            if ($key == "body") {
 
                $request_data .= urldecode($value);
 
            } else {
 
                $request_data .= urlencode($value);
 
            }
 
        }
 
        $c = curl_init('http://www.tumblr.com/api/write');
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, $request_data);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_TIMEOUT, 45);
        $result = curl_exec($c);
        $status = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);
 
        if ($status == 201) {
            echo "Success! Post ID: $result";
 
            $res = file_put_contents($logFile,$node->link . " : " . $tumblrUrl . "/post/" . $result,FILE_APPEND);          
        } else if ($status == 403) {
            echo 'Bad email/password';
        } else {
            echo "Error: $result\n";
        }
        echo "<br />";
 
    }  
 
}
 
?>