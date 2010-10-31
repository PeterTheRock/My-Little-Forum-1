<?php

/**
 * collection of functions for generation of HTML-output
 * @author Heiko August
 */



/**
 * generates the list of functions
 * to manipulate marked threads
 *
 * @param string $refer
 * @return string $output
 */
function outputManipulateMarked($refer='') {
global $settings,$lang;

$r  = '';

if (isset($_SESSION[$settings['session_prefix'].'user_type'])
	and $_SESSION[$settings['session_prefix'].'user_type']=='admin')
	{
	$ref = (!empty($refer)) ? '&amp;refer='.$refer : '';
	$r .= '<div class="marked-threads">'."\n";
	$r .= ' <h2><img src="img/marked.gif" alt="[x]" width="9" height="9" /> ';
	$r .= $lang['marked_threads_actions'].'</h2>'."\n";
	$r .= ' <ul>'."\n";
	$r .= '  <li><a href="admin.php?action=delete_marked_threads'.$ref.'">';
	$r .= $lang['delete_marked_threads'].'</a></li>'."\n";
	$r .= '  <li><a href="admin.php?action=lock_marked_threads'.$ref.'">';
	$r .= $lang['lock_marked_threads'].'</a></li>'."\n";
	$r .= '  <li><a href="admin.php?action=unlock_marked_threads'.$ref.'">';
	$r .= $lang['unlock_marked_threads'].'</a></li>'."\n";
	$r .= '  <li><a href="admin.php?action=unmark'.$ref.'">';
	$r .= $lang['unmark_threads'].'</a></li>'."\n";
	$r .= '  <li><a href="admin.php?action=invert_markings'.$ref.'">';
	$r .= $lang['invert_markings'].'</a></li>'."\n";
	$r .= '  <li><a href="admin.php?action=mark_threads'.$ref.'">';
	$r .= $lang['mark_threads'].'</a></li>'."\n";
	$r .= ' </ul>'."\n";
	$r .= '</div>'."\n";
	}

return $r;
} # End: outputManipulateMarked



/**
 * generates a form for categories
 *
 * @param array $categories
 * @param integer $category
 * @return string $output
 */
function outputCategoriesList($categories, $category) {
global $lang;

$r = '';

if($categories != false && $categories != "not accessible")
	{
	$r .= "\n".'<form method="get" action="'.$_SERVER['SCRIPT_NAME'].'" title="'.outputLangDebugInAttributes($lang['choose_category_formtitle']).'">'."\n".'<div class="inline-form">'."\n";
	$r .= '<select class="kat" size="1" name="category" onchange="this.form.submit();">'."\n";
	$r .= '<option value="0"';
	$r .= (isset($category) && $category==0) ? ' selected="selected"' : '';
	$r .= '>'.$lang['show_all_categories'].'</option>'."\n";
	while(list($key, $val) = each($categories))
		{
		if($key!=0)
			{
			$r .= '<option value="'.$key.'"';
			$r .= ($key==$category) ? ' selected="selected"' : '';
			$r .= '>'.$val.'</option>'."\n";
			}
		}
	$r .= '</select>'."\n".'<noscript><p class="inline-form"> <input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></p></noscript></div>'."\n".'</form>'."\n";
	}

return $r;
} # End: outputCategoriesList



/**
 * detects the status of $mark dependent of users role
 *
 * @param array $mark
 * @param array $zeile
 * @param string $connid
 * @return array $mark
 */
function outputStatusMark($mark, $zeile, $connid) {
global $settings;
if (is_array($zeile)) {
	global $db_settings;
	$query = "SELECT
	user_type
	FROM ".$db_settings['userdata_table']."
	WHERE user_id = '".$zeile["user_id"]."'";
	$userdata_result = mysql_query($query, $connid);
	$userdata = mysql_fetch_assoc($userdata_result);
	mysql_free_result($userdata_result);
	}
else
	{
	$userdata['user_type'] = $zeile;
	}

$mark['admin'] = ($userdata['user_type'] === "admin" && $settings['admin_mod_highlight'] == 1) ? true : false;
$mark['mod'] = ($userdata['user_type'] === "mod" && $settings['admin_mod_highlight'] == 1) ? true : false;
$mark['user'] = ($userdata['user_type'] === "user" && $settings['user_highlight'] == 1) ? true : false;
return $mark;
}



/**
 * generates the link to the posting form in top- and subnavigation
 *
 * @param integer $category
 * @param string $view (optional)
 * @return string $output
 */
function outputPostingLink($category,$view='') {
global $lang;

$r = '';

$qs = '';

$q1 = !empty($view) ? 'view='.$view : '';
$q2 = !empty($category) ? 'category='.$category : '';

if (!empty($view) or !empty($category))
	{
	$qs .= '?'.$q1;
	if (!empty($q2))
		{
		$qs .= ($qs != '?') ? '&amp;'.$q2 : $q2;
		}
	}

$r .= '<a class="textlink"  rel="nofollow" href="posting.php'.$qs;
$r .= '" title="'.outputLangDebugInAttributes($lang['new_entry_linktitle']).'">'.$lang['new_entry_linkname'].'</a>';

return $r;
} # End: outputPostingLink



/**
 * generates posting authr name string
 *
 *
 */
function outputAuthorInfo($mark, $entry, $page, $order, $view, $category=0) {
global $lang, $settings;

$r = '';
$email_hp = '';
$place_c = '';
$place = '';
$editor = '';
$linktitle = '';
$entryIP = '';
$entryedit = '';
$entryID = '';
$answer = '';

# whole author string template
$authorstring = $lang['forum_author_marking'];
# editors template
$editstring = $lang['forum_edited_marking'];
# generate setting to show contact link if not present
$entry["hide_email"] = empty($entry["hide_email"]) ? 0 : $entry["hide_email"];
# generate string for posting ID
$entryID .= ($settings['show_posting_id'] == 1) ? '<span class="postinginfo">Posting:&nbsp;#&nbsp;'.$entry['id'].'</span>' : '';
# generate string for name of the answered author
$answer = (!empty($entry['answer'])) ? '<span class="postinginfo">@&nbsp;'.htmlspecialchars($entry['answer']).'</span>' : '';
# generate HTML cource code for userdata (hp, email, location)
if ($entry["email"]!="" && $entry["hide_email"] != 1 or $entry["hp"]!="")
	{
	$email_hp .= " ";
	}
if ($entry["hp"]!="")
	{
	$email_hp .= '<a href="'.amendProtocol($entry["hp"]).'" title="';
	$email_hp .= htmlspecialchars($entry["hp"]).'"><img src="img/homepage.gif" ';
	$email_hp .= 'alt="'.outputLangDebugInAttributes($lang['homepage_alt']).'" width="13" height="13" /></a>';
	}
if (($entry["email"]!="" && $entry["hide_email"] != 1)
	and $entry["hp"]!=""
	and (($settings['entries_by_users_only'] == 1
	and isset($_SESSION[$settings['session_prefix'].'user_id']))
	or $settings['entries_by_users_only'] == 0))
	{
	$email_hp .= "&nbsp;";
	}
if (($entry["email"]!="" && $entry["hide_email"] != 1)
	and (($settings['entries_by_users_only'] == 1
	and isset($_SESSION[$settings['session_prefix'].'user_id']))
	or $settings['entries_by_users_only'] == 0))
	{
	$email_hp .= '<a href="contact.php?id='.$entry["id"];
	$email_hp .= !empty($page) ? '&amp;page='.intval($page) : '';
	$email_hp .= !empty($order) ? '&amp;order='.$order : '';
	$email_hp .= !empty($category) ? '&amp;category='.intval($category) : '';
	$email_hp .= '" rel="nofollow" title="';
	$email_hp .= str_replace("[name]", htmlspecialchars($entry['name']), outputLangDebugInAttributes($lang['email_to_user_linktitle'])).'">';
	$email_hp .= '<img src="img/email.gif" alt="'.outputLangDebugInAttributes($lang['email_alt']).'" width="13" height="10" /></a>';
	}
if ($entry["place"] != "")
	{
	$place .= htmlspecialchars($entry['place']);
	}
# generate HTML source code of authors name
$name = outputAuthorsName($entry['name'], $mark, $entry['user_id']);

if (isset($_SESSION[$settings['session_prefix'].'user_id'])
	and $entry['user_id'] > 0)
	{
	$linktitle = str_replace("[name]", htmlspecialchars($entry['name']), outputLangDebugInAttributes($lang['show_userdata_linktitle']));
	$uname .= '<a class="userlink" href="user.php?id='.$entry["user_id"].'"';
	$uname .= ' rel="nofollow" title="'.$linktitle.'">'.$name.'</a>';
	}
else
	{
	$uname = $name;
	}

if (isset($_SESSION[$settings['session_prefix'].'user_id'])
	&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin" ||
	isset($_SESSION[$settings['session_prefix'].'user_id'])
	&& $_SESSION[$settings['session_prefix'].'user_type'] == "mod")
	{
	$entryIP = '<span class="postinginfo">'.$entry['ip'].'</span>';
	}

if ($entry["edited_diff"] > 0
	&& $entry["edited_diff"] > $entry["time"]
	&& $settings['show_if_edited'] == 1)
	{
	$editstring = str_replace("[name]", htmlspecialchars($entry["edited_by"]), $editstring);
	$editstring = str_replace("[time]", strftime($lang['time_format'],$entry["e_time"]), $editstring);
	$entryedit .= '<span class="postinginfo">'.$editstring.'</span>';
	}

if ($view=='forum')
	{
	$authorstring = str_replace("[name]", $uname, $authorstring);
	$authorstring = str_replace("[email_hp]", $email_hp, $authorstring);
	$authorstring = str_replace("[place]", $place, $authorstring);
	$authorstring = str_replace("[place]", $place, $authorstring);
	$authorstring = str_replace("[time]", strftime($lang['time_format'],$entry["p_time"]), $authorstring);
	$entryID = !empty($entryID) ? ' - '.$entryID : '';
	$entryedit = (!empty($entryedit)) ? '<br />'.$entryedit : '';
	$r .= '<p class="author">'.$authorstring.'&nbsp;'.$entryIP.$answer.$entryID.$entryedit.'</p>'."\n";
	}
else if ($view=='board' or $view=='mix')
	{
	$place = (!empty($place)) ? '<br />'.$place : '';
	$entryedit = (!empty($entryedit)) ? '<br />'.$entryedit : '';
	if (!empty($entryIP) or !empty($entryID) or !empty($answer))
		{
		$separator = '<br /><br />';
		if (!empty($entryIP))
			{
			$entryID = (!empty($entryID)) ? '<br /><br />'.$entryID : '';
			$answer = (!empty($answer)) ? '<br />'.$answer : '';
			}
		else
			{
			if (!empty($answer))
				{
				$entryID = (!empty($entryID)) ? '<br />'.$entryID : '';
				}
			}
		}
	$r .= $uname.'<br />'."\n".$email_hp.$place."\n<br />".strftime($lang['time_format'],$entry["p_time"]).$entryedit.$separator.$entryIP.$answer.$entryID."\n";
	}
else
	{
	if (!empty($entryID))
		{
		$entryID = ' - '.$entryID;
		}
#	$r .= $name.$entryID;
	$r .= $name;
	}

return $r;
} # End: outputAuthorInfo



/**
 * generates the name part of the authors information
 *
 * @param string $name
 * @param array $mark
 * @param integer $user_id
 * @return string $output
 */
function outputAuthorsName($username, $mark, $user_id=0) {
global $settings, $lang;

$r = '';
$name = '<span class="';
$regimg = '';

if ($mark['admin']===true or $mark['mod']===true or $mark['user']===true)
	{
	if ($mark['admin']==true)
		{
		$name .= 'admin-highlight" title="'.outputLangDebugInAttributes($lang['ud_admin']);
		}
	else if ($mark['mod']==true)
		{
		$name .= 'mod-highlight" title="'.outputLangDebugInAttributes($lang['ud_mod']);
		}
	else if ($mark['user']===true)
		{
		$name .= 'user-highlight" title="'.outputLangDebugInAttributes($lang['ud_user']);
		}
	}
else
	{
	$name .= 'username';
	}
$name .= '">'.htmlspecialchars($username).'</span>';

# generate image for registered users
if ($settings['show_registered'] ==1
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and $user_id > 0)
	{
	$regimg .= '<img src="img/registered.gif" alt="(R)" width="10" height="10" title="'.outputLangDebugInAttributes($lang['registered_user_title']).'" />';
	}

$r .= $name.$regimg;

return $r;
} # End: outputAuthorsName



/**
 * generates the menu for editing of a posting
 * @return string
 */
function outputPostingEditMenu($thread, $view, $first = '') {
global $settings, $lang, $page, $order, $descasc, $category;

$r  = '';
$period = false;

$view = !empty($view) ? '&amp;view='.$view : '';
if ($settings['user_edit']==1 and $settings['edit_period'] > 0)
	{
	$editPeriodEnd = $thread['time'] + ($settings['edit_period'] * 60);
	$period = ($editPeriodEnd > time()) ? true : false;
	}
else if ($settings['user_edit']==1 and $settings['edit_period'] == 0)
	{
	$period = true;
	}

if (($settings['user_edit'] == 1
	and (isset($_SESSION[$settings['session_prefix'].'user_id'])
	and $thread["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]
	and $period === true))
	or (isset($_SESSION[$settings['session_prefix'].'user_id'])
	and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
	or $_SESSION[$settings['session_prefix']."user_type"] == "mod")))
	{
	$r .= "<ul class=\"menu\">\n";
	$r .= '<li><a href="posting.php?action=edit&amp;id=';
	$r .= $thread["id"].$view.'&amp;back='.$thread["tid"].'&amp;page='.$page;
	$r .= '&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category='.$category;
	$r .= '" class="edit-posting" title="'.outputLangDebugInAttributes($lang['edit_linktitle']).'">';
	$r .= $lang['edit_linkname'].'</a></li>'."\n";
	if (($settings['user_delete'] == 1
		and (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and $thread["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]
		and $period === true))
		or (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod")))
		{
		$r .= '<li><a href="posting.php?action=delete&amp;id=';
		$r .= $thread["id"].'&amp;back='.$thread["tid"].$view.'&amp;page=';
		$r .= $page.'&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category=';
		$r .= $category.'" class="delete-posting" title="'.outputLangDebugInAttributes($lang['delete_linktitle']).'">';
		$r .= $lang['delete_linkname'].'</a></li>'."\n";
		}
	if ((!empty($first) and $first==='opener')
		and (isset($_SESSION[$settings['session_prefix'].'user_id'])
		and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod")))
		{
		$r .= '<li><a href="posting.php?lock=true'.$view.'&amp;id=';
		$r .= $thread["id"].'&amp;page='.$page.'&amp;order='.$order.'&amp;descasc=';
		$r .= $descasc.'&amp;category='.$category.'" class="lock-posting" title="';
		$r .= ($thread['locked'] == 0) ? outputLangDebugInAttributes($lang['lock_linktitle']) : outputLangDebugInAttributes($lang['unlock_linktitle']);
		$r .= '">';
		$r .= ($thread['locked'] == 0) ? $lang['lock_linkname'] : $lang['unlock_linkname'];
		$r .= '</a></li>'."\n";
		}
	$r .= "</ul>\n";
	}

return $r;
} # End: outputPostingEditMenu



/**
 * generates output for language file debug mode
 *
 * @param array $lang
 * @return array $lang
 */
function outputLangDebugOrNot($lang, $file) {
$str = array();
$debug = (!empty($_SESSION['debug']) and $_SESSION['debug'] == 'lang') ? 1 : 0;

foreach ($lang as $key => $val) {
	$hasDebug = strpos($val, '<span title="key: ');
	if ($hasDebug !== false) {
		$str[$key]  = $val;
		}
	else {
		$str[$key]  = $debug == 1 ? '<span title="key: ['.htmlspecialchars($key).'], file: '.htmlspecialchars($file).'">' : '';
		$str[$key] .= htmlspecialchars(strval($val));
		$str[$key] .= $debug == 1 ? '</span>' : '';
		}
	}
return $str;
}



/**
 * reorders the debug output for strings in case of use in HTML-attributes
 */
function outputLangDebugInAttributes($string) {

# <span title="key: [irgendwas], file: german.php">Wert</span>
$debug = (!empty($_SESSION['debug']) and $_SESSION['debug'] == 'lang') ? 1 : 0;

if ($debug == 1)
	{
	$substring = strstr($string, '"');
	$substring = substr($substring, 1);
	$pos1 = strpos($substring, '"');
	$substring = substr($substring, 0, $pos1);
	$string = strip_tags($string);
	$string = $string.", ".$substring;
	}

return $string;
}


/**
 *
 */
function outputXMLclearedString($string) {
$illegalChars = array(array(), array());

$illegalChars["char"][0] = chr(0);
$illegalChars["repl"][0] = "";
$illegalChars["char"][1] = chr(1);
$illegalChars["repl"][1] = "";
$illegalChars["char"][2] = chr(2);
$illegalChars["repl"][2] = "";
$illegalChars["char"][3] = chr(3);
$illegalChars["repl"][3] = "";
$illegalChars["char"][4] = chr(4);
$illegalChars["repl"][4] = "";
$illegalChars["char"][5] = chr(5);
$illegalChars["repl"][5] = "";
$illegalChars["char"][6] = chr(6);
$illegalChars["repl"][6] = "";
$illegalChars["char"][7] = chr(7);
$illegalChars["repl"][7] = "";
$illegalChars["char"][8] = chr(8);
$illegalChars["repl"][8] = "";
$illegalChars["char"][9] = chr(9);
$illegalChars["repl"][9] = " ";
$illegalChars["char"][10] = chr(10);
$illegalChars["repl"][10] = chr(10);
$illegalChars["char"][11] = chr(11);
$illegalChars["repl"][11] = "";
$illegalChars["char"][12] = chr(12);
$illegalChars["repl"][12] = "";
$illegalChars["char"][13] = chr(13);
$illegalChars["repl"][13] = chr(13);
$illegalChars["char"][14] = chr(14);
$illegalChars["repl"][14] = "";
$illegalChars["char"][15] = chr(15);
$illegalChars["repl"][15] = "";
$illegalChars["char"][16] = chr(16);
$illegalChars["repl"][16] = "";
$illegalChars["char"][17] = chr(17);
$illegalChars["repl"][17] = "";
$illegalChars["char"][18] = chr(18);
$illegalChars["repl"][18] = "";
$illegalChars["char"][19] = chr(19);
$illegalChars["repl"][19] = "";
$illegalChars["char"][20] = chr(20);
$illegalChars["repl"][20] = "";
$illegalChars["char"][21] = chr(21);
$illegalChars["repl"][21] = "";
$illegalChars["char"][22] = chr(22);
$illegalChars["repl"][22] = "";
$illegalChars["char"][23] = chr(23);
$illegalChars["repl"][23] = "";
$illegalChars["char"][24] = chr(24);
$illegalChars["repl"][24] = "";
$illegalChars["char"][25] = chr(25);
$illegalChars["repl"][25] = "";
$illegalChars["char"][26] = chr(26);
$illegalChars["repl"][26] = "";
$illegalChars["char"][27] = chr(27);
$illegalChars["repl"][27] = "";
$illegalChars["char"][28] = chr(28);
$illegalChars["repl"][28] = "";
$illegalChars["char"][29] = chr(29);
$illegalChars["repl"][29] = "";
$illegalChars["char"][30] = chr(30);
$illegalChars["repl"][30] = "";
$illegalChars["char"][31] = chr(31);
$illegalChars["repl"][31] = "";

$string = str_replace($illegalChars["char"], $illegalChars["repl"], $string);

return $string;
} # End: outputXMLclearedString

?>
