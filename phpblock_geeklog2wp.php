<?php
/*
 * phpblock_geeklog2wp.php for Geeklog 1.7.0
 * Copyright (C) 2010 casey, pc.casey.jp
 *
 * Export Geeklog stories in WordPress format
 *
 * @AUTHOR    CASEY
 * @VERSION   0.0.1
 * @LICENSE   GPL
 * @NOTICE    The export file is saved as <geeklog>/data/gl_stories.txt.
 */

define('MT_ARTICLE_SEPARATOR', "--------\n");
define('MT_FIELD_SEPARATOR', "-----\n");

// The name of export file
define('EXPORT_FILE', $_CONF['path'] . 'data/gl_stories.txt');

/**
* Language definition
*/
if (empty($_USER['language'])) {
	if (empty($_COOKIE[$_CONF['cookie_language']])) {
		define('GL2WP_LANG', 'english_utf-8');
	} else {
		define('GL2WP_LANG', $_COOKIE[$_CONF['cookie_language']]);
	}
} else {
	define('GL2WP_LANG', $_USER['language']);
}

/**
* Language data for English(utf-8)
*/
$LANG_GEEKLOG2MT['english_utf-8'] = array(
	1              => 'Success!  The export file is stored as ' . EXPORT_FILE,
	2              => 'Cannot open a file for export.',
	3              => 'Cannot write into a file for export.',
	4              => 'Cannot lock a file for writing.',
	'choose_topic' => 'Check topics you would like to export:',
	'export'       => 'Export!',
	'no_topic'     => 'No topic was specified.',
);

/**
* Language data for Japanese(utf-8)
*/
$LANG_GEEKLOG2MT['japanese_utf-8'] = array(
	1              => 'エクスポートが終了しました。' . EXPORT_FILE . ' に保存されています。',
	2              => 'ファイルを開けません。',
	3              => 'ファイルに書き込めません。',
	4              => 'ファイルをロックできません。',
	'choose_topic' => 'エクスポートする話題にチェックを入れてください：',
	'export'       => 'エクスポート実行',
	'no_topic'     => '話題が選択されていません。',
);

//===============================================
//  Functions
//===============================================

/**
* Escape a string for display
*
* @param  string $str - a string to be escaped
* @return string      - an escaped string
*/
function GL2WP_escape($str) {
	global $LANG_CHARSET;

	// Unescape a string
	$str = str_replace(
		array('&lt;', '&gt;', '&amp;', '&quot:', '&#039;'),
		array(   '<',    '>',     '&',      '"',      "'"),
		$str
	);

	return htmlspecialchars($str, ENT_QUOTES, $LANG_CHARSET);
}

/**
* Get all topics
*
* @return array
*/
function GL2WP_getAllTopics() {
	global $_PLUGINS, $_TABLES;

	static $retval = null;

	if (!is_null($retval)) {
		return $retval;
	}

	$retval = array();

	if (in_array('dataproxy', $_PLUGINS)) {
		$dp = new Dataproxy(0);
		$result = $dp->article->getAllCategories(true);
		if (is_array($result) AND count($result) > 0) {
			foreach ($result as $item) {
				$retval[$item['id']] = $item['title'];
			}
		}
	} else {
		$sql = "SELECT tid, topic FROM {$_TABLES['topics']}";
		$result = DB_query($sql);
		if (!DB_error()) {
			while (($A = DB_fetchArray($result)) !== false) {
				$tid   = stripslashes($A['tid']);
				$topic = stripslashes($A['topic']);
				$retval[$tid] = $topic;
			}
		}
	}

	return $retval;
}

/**
* Return a list checkboxes of all topics
*
* @return string - HTML
*/
function GL2WP_getTopicOptions() {
	$retval = '';
	$topics = GL2WP_getAllTopics();

	foreach ($topics as $tid => $topic) {
		$retval .= '<input id="' . 't_' . $tid . '" name="GL2WP_topics[]" type="checkbox" value="'
				.  $tid . '">'
				.  '<label for="' . 't_' . $tid . '">' . GL2WP_escape($topic) . '</label><br>';
	}

	return $retval;
}

/**
* Return a corresponding message
*
* @param  mixed  $msg - message id
* @return string      - message
*/
function GL2WP_msg($msg) {
	global $LANG_GEEKLOG2MT;

	return GL2WP_escape($LANG_GEEKLOG2MT[GL2WP_LANG][$msg]);
}

/**
* Return the email
*
* @param  int $uid - user id
* @return string   - user name
*/
function GL2WP_email($uid) {
 global $_TABLES;

 $retval = '';
 static $emails = array();

 if (isset($emails[$uid])) {
 $retval = $emails[$uid];
 } else {
 $sql = "SELECT email FROM {$_TABLES['users']} "
 . "WHERE (uid = '" . $uid . "')";
 $result = DB_query($sql);
 if (!DB_error()) {
 $A = DB_fetchArray($result);
 $retval = stripslashes($A['email']);
 $emails[$uid] = $retval;
 }
 }

 return $retval;
}


/**
* Return the user name
*
* @param  int $uid - user id
* @return string   - user name
*/
function GL2WP_username($uid) {
	global $_TABLES;

	$retval = '';
	static $names = array();

	if (isset($names[$uid])) {
		$retval = $names[$uid];
	} else {
		$sql = "SELECT username FROM {$_TABLES['users']} "
			 . "WHERE (uid = '" . $uid . "')";
		$result = DB_query($sql);
		if (!DB_error()) {
			$A = DB_fetchArray($result);
			$retval = stripslashes($A['username']);
			$names[$uid] = $retval;
		}
	}

	return $retval;
}

function GL2WP_date($timestamp) {
	return date('Y/m/d H:i:s', $timestamp);
}

/**
* get users who have commented
*
* @param
* @return      string
 */

function GL2WP_handleUsers() {
        global $_TABLES;

        $retval = '';

	$sql = "select gl_users.uid,gl_users.username,gl_users.email,gl_users.fullname "
		. "from gl_users, gl_comments "
		. "where gl_users.uid=gl_comments.uid "
		. "group by gl_users.uid having count(*) > 1";

        $result = DB_query($sql);
        if (DB_numRows($result) > 0) {
                while (($C = DB_fetchArray($result)) !== false) {
                        list($uid, $username, $email, $fullname) = $C;

			$retval .= '<wp:author><wp:author_id>' . $uid . '</wp:author_id>'
				. '<wp:author_login><![CDATA[' . $username . ']]></wp:author_login>'
				. '<wp:author_email><![CDATA[' . $email . ']]></wp:author_email>'
				. '<wp:author_display_name><![CDATA[' . $username . ']]></wp:author_display_name>'
				. '<wp:author_first_name><![CDATA[]]></wp:author_first_name>'
				. '<wp:author_last_name><![CDATA[]]></wp:author_last_name>'
				. '</wp:author>'. LB ;

                }
        }

        return $retval;
}




/**
* Handle comments related to the story
*
* @param  $sid string - story id
* @return      string
*/
function GL2WP_handleComments($sid) {
	global $_TABLES;
	static $comment_counter = 1;
	$retval = '';

	$sql = "SELECT uid, date, ipaddress, title, comment "
		 . "FROM {$_TABLES['comments']} "
		 . "WHERE (type = 'article') AND (sid = '" . addslashes($sid) . "')";
	$result = DB_query($sql);
	if (DB_numRows($result) > 0) {
		while (($C = DB_fetchArray($result)) !== false) {
			list($uid, $date, $ipaddress, $title, $comment) = $C;
			$title   = stripslashes($title);	// Not used
			$comment = str_replace(array("\r\n", "\r"), LB, stripslashes($comment));

			$retval .= '<wp:comment><wp:comment_id>' . $comment_counter.'</wp:comment_id>'. LB
			. '<wp:comment_author><![CDATA[' . GL2WP_username($uid) . ']]></wp:comment_author>'. LB
			. '<wp:comment_author_email><![CDATA[' . GL2WP_email($uid) .']]></wp:comment_author_email>'. LB
			. '<wp:comment_author_url>' . 'https://wp.baltimorespokes.org' . '</wp:comment_author_url>'. LB
			. '<wp:comment_author_IP><![CDATA[' . $ipaddress . ']]></wp:comment_author_IP>'. LB
			. '<wp:comment_date><![CDATA[' . GL2WP_date (strtotime ($date)) . ']]></wp:comment_date>'. LB
			. '<wp:comment_date_gmt><![CDATA[' . GL2WP_date (strtotime ($date)) . ']]></wp:comment_date_gmt>'. LB
			. '<wp:comment_content><![CDATA[' . $comment .']]></wp:comment_content>'. LB
			. '<wp:comment_approved><![CDATA[1]]></wp:comment_approved>'. LB
			. '<wp:comment_type><![CDATA[comment]]></wp:comment_type>'. LB
			. '<wp:comment_parent>0</wp:comment_parent>'. LB
			. '<wp:comment_user_id>' . $uid . '</wp:comment_user_id>'. LB
			. '</wp:comment>'. LB;

			$comment_counter++;
		}
	}

	return $retval;
}

/**
* Handle trackbacks related to the story
*
* @param  $sid string - story id
* @return      string
*/
function GL2WP_handleTrackbacks($sid) {
	global $_TABLES;

	$retval = '';
	if (!isset($_TABLES['trackback'])) {
		return $retval;
	}

	$sql = "SELECT title, url, ipaddress, blog, excerpt, date "
		 . "FROM {$_TABLES['trackback']} "
		 . "WHERE (type = 'article') AND (sid = '" . addslashes($sid) . "')";
	$result = DB_query($sql);
	if (DB_numRows($result) > 0) {
		while (($T = DB_fetchArray($result)) !== false) {
			list($title, $url, $ipaddress, $blog, $excerpt, $date,) = $T;
			$title   = stripslashes($title);
			$url     = stripslashes($url);
			$blog    = stripslashes($blog);
			$excerpt = stripslashes($excerpt);
			$retval .= 'PING: ' . LB
					.  'TITLE: ' . $title . LB
					.  'IP: ' . $ipaddress . LB
					.  'BLOG NAME: ' . $blog . LB
					.  'DATE: ' . GL2WP_date(strtotime($date)) . LB
					.  $excerpt . LB
					.  MT_FIELD_SEPARATOR;
		}
	}

	return $retval;
}

// return data file header
function getHeader(){
	$data  = '<?xml version="1.0" encoding="UTF-8" ?>' . LB;
	$data .= '<rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.0/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.0/">' . LB;
	$data .= '<channel>' . LB;
	$data .= '<wp:wxr_version>1.0</wp:wxr_version>' . LB;
	return $data;
}

// return data file footer
function getFooter(){
	$data = null;
	$data .='</channel>' . LB;
	$data .='</rss>' . LB;
	return $data;
}

/**
* @param  string $tid - topic id
* @return string exported string in MT format
*/
function GL2WP_export($tid) {
	global $_PLUGINS, $_TABLES;

	$retval = getHeader();

	$retval .= GL2WP_handleUsers();

	$all_topics = GL2WP_getAllTopics();
	if (!array_key_exists($tid, $all_topics)) {
		return $retval;
	} else {
		$topic = $all_topics[$tid];
	}

	// Get stories

	$stories = array();

	if (in_array('dataproxy', $_PLUGINS)) {
		$dp = new Dataproxy(0);
		$meta_data = $dp->article->getItems($tid, true);

		foreach($meta_data as $meta) {
			$temp = $dp->article->getItemById($meta['id'], true);
			$stories[] = $temp['raw_data'];
		}
	} else {
		$sql = "SELECT sid, uid, draft_flag, date, title, introtext, bodytext, "
		     . "comments, trackbacks, commentcode, trackbackcode, postmode "
			 . "FROM {$_TABLES['stories']} "
			 . "WHERE (tid = '" . addslashes($tid) . "')";
		$result = DB_query($sql);

		while (($A = DB_fetchArray($result)) !== false) {
			$A['title']     = stripslashes($A['title']);
			$A['introtext'] = stripslashes($A['introtext']);
			$A['bodytext']  = stripslashes($A['bodytext']);
			$stories[] = $A;
		}
	}

	// [id]=name
	//GL2WP_escape($topic)
	$topics = GL2WP_getAllTopics();

	foreach ($stories as $S) {
		//	.  '<category><![CDATA['. GL2WP_escape($topics[$tid]) . ']]></category>'				. LB
		$img1 = null;
		$img2 = null;
		$id = strtotime($S['date']) - strtotime('2005/01/01 00:00:00');
		$retval .= '<item>' . LB
				.  '<dc:creator><![CDATA[' . GL2WP_username($S['uid']) . ']]></dc:creator>' . LB
				.  '<title>' . $S['title'] . '</title>' . LB
				.  '<wp:post_id>' . $id . '</wp:post_id>' . LB
				.  '<wp:post_date>' . GL2WP_date(strtotime($S['date'])) . '</wp:post_date>' . LB
			 	.  '<category domain="category" nicename="' . $topic . '"><![CDATA[' . $topic . ']]></category>' . LB
				.  '<wp:status>' . ($S['draft_flag'] == 0 ? 'publish' : 'draft') . '</wp:status>' . LB
				.  '<wp:comment_status>' . '<![CDATA[open]]>' . '</wp:comment_status>' . LB
				.  '<wp:ping_status>' . ($S['trackbackcode'] == 0 ? '1' : '0') . '</wp:ping_status>' . LB
				.  '<wp:post_parent>0</wp:post_parent>' . LB
				.  '<wp:menu_order>0</wp:menu_order>' . LB
				.  '<wp:post_type>post</wp:post_type>' . LB
				.  '<wp:is_sticky>0</wp:is_sticky>' . LB;

		if (trim($S['introtext']) != '') {
			if($imgs = GL2WP_getImage($id, $S['introtext'])){
				foreach($imgs as $img){
					$img1 .= _makeImage(GL2WP_username($S['uid']), GL2WP_date(strtotime($S['date'])), $img, $id) . LB;
				}
			}
			$intro = str_replace(array("\r\n", "\r"), LB, $S['introtext'] . LB);
		}else{
			$intro = null;
		}

		if (trim($S['bodytext']) != '') {
			if($imgs = GL2WP_getImage($id, $S['bodytext'])){
				foreach($imgs as $img){
					$img2 .= _makeImage(GL2WP_username($S['uid']), GL2WP_date(strtotime($S['date'])), $img, $id) . LB;
				}
			}
			$body =  '<!--more-->' . LB
					. str_replace(array("\r\n", "\r"), LB, $S['bodytext'] . "oldId." . $S['sid']) .  LB;
		}else{
			$body = null;
		}
		$retval .= '<content:encoded><![CDATA['
					.  str_replace(array("\r\n", "\r"), LB, $intro . $body)
					.  ']]></content:encoded>' . LB;

		$retval .= '<excerpt:encoded><![CDATA[]]></excerpt:encoded>' . LB;
		$retval .= GL2WP_handleComments($S['sid']);
		$retval .= '</item>' . LB;
		$retval .= $img1 ? $img1 : null;
		$retval .= $img2 ? $img2 : null;

		//$retval .= GL2WP_handleComments($S['sid'])
		//		.  GL2WP_handleTrackbacks($S['sid'])
		//		.  MT_ARTICLE_SEPARATOR;
	}
	$retval .= getFooter();
	return $retval;
}

	// make image data format
	function _makeImage($author, $date, $url, $sid){
		$name = basename($url);
		$data = null;
		$data .= '<item>' . LB;
		$data .= '<title>' . $name . '</title> ' . LB;
		$data .= '<dc:creator><![CDATA['. $author .']]></dc:creator>' . LB;
		$data .= '<wp:post_date>' . $date . '</wp:post_date>' . LB;
		$data .= '<wp:post_type>attachment</wp:post_type>' . LB;
		$data .= '<wp:attachment_url>' . $url . '</wp:attachment_url>' . LB;
		$data .= '<wp:post_parent>' . $sid . '</wp:post_parent>' . LB;
		$data .= '<wp:postmeta><wp:meta_key>_wp_attached_file</wp:meta_key></wp:postmeta>' . LB;
		$data .= '</item>';
		return $data;
	}

function GL2WP_getImage($sid, $str) {
	// init
	// http://oshiete.goo.ne.jp/qa/1276090.html
	preg_match_all("/(\C{5,10})[\"]?https?:\/\/([^\"\s]+)/i" , $str , $arr);
	$imgs = array();

	// tag search
	for($i=0;$i<count($arr[1]);$i++){
		if(stristr($arr[1][$i], "src")){
			$imgs[] = "http://" . $arr[2][$i];
		}
	}

//	$ret = null;
//	foreach($imgs as $img){
//		$ret .= $sid . $img . LB;
//	}

	// return
	//return $ret;
	return $imgs;
}



//===============================================
//  Main
//===============================================

function phpblock_geeklog2mt() {
    if (!SEC_inGroup('Root')) {
        return '';
    }

	if (isset($_GET['gl2mt'])) {
		$msg = COM_applyFilter($_GET['gl2mt'], true);
		if ($msg >= 1 AND $msg <= 4) {
			return GL2WP_msg($msg);
		}
	}

	if (!isset($_POST['GL2WP_submit'])) {
		$retval = '<p>' . GL2WP_msg('choose_topic') . '</p>' . LB
				. '<form action="' . COM_getCurrentURL() . '" method="post">' . LB
				. GL2WP_getTopicOptions() . '<br>'
				. '<input name="GL2WP_submit" type="submit" value="' . GL2WP_msg('export')
				. '"><br>' . LB
				. '</form>' . LB;

		return $retval;
	}

	// Do export

	if (!isset($_POST['GL2WP_topics']) OR count($_POST['GL2WP_topics']) == 0) {
		return GL2WP_msg('no_topic');
	}

	$export = '';

	foreach ($_POST['GL2WP_topics'] as $tid) {
		$tid = COM_applyFilter($tid);
		$export .= GL2WP_export($tid);
	}

	// Save into a file
	$fp = @fopen(EXPORT_FILE, 'wb');
	if ($fp === false) {
		$retval = 2;
	} else {
		if (flock($fp, LOCK_EX)) {
			if (fwrite($fp, $export) === false) {
				$retval = 3;
			} else {
				$retval = 1;
			}
			flock($fp, LOCK_UN);
		} else {
			$retval = 4;
		}
		fclose($fp);
	}

	return GL2WP_msg($retval);
}

?>
