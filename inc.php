<?php
###############################################################################
# my little forum                                                             #
# Copyright (C) 2005 Alex                                                     #
# http://www.mylittlehomepage.net/                                            #
#                                                                             #
# This program is free software; you can redistribute it and/or               #
# modify it under the terms of the GNU General Public License                 #
# as published by the Free Software Foundation; either version 2              #
# of the License, or (at your option) any later version.                      #
#                                                                             #
# This program is distributed in the hope that it will be useful,             #
# but WITHOUT ANY WARRANTY; without even the implied warranty of              #
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the                #
# GNU General Public License for more details.                                #
                                                                              #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

mb_internal_encoding('UTF-8');
header('Content-Type: text/html; charset=UTF-8');

#ini_set("session.use_trans_sid","0");
session_start();

include("db_settings.php");
include("functions.php");



# for details see: http://de.php.net/manual/en/security.magicquotes.disabling.php
if (get_magic_quotes_gpc())
	{
	$_POST = array_map('stripslashes_deep', $_POST);
	$_GET = array_map('stripslashes_deep', $_GET);
	$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
	$_SESSION = array_map('stripslashes_deep', $_SESSION);
	$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
	}


$connid = connect_db($db_settings['host'], $db_settings['user'], $db_settings['pw'], $db_settings['db']);
get_settings();
include("lang/".$settings['language_file']);
setlocale(LC_ALL, $lang['locale']);

if (basename($_SERVER['SCRIPT_NAME'])!='login.php'
&& basename($_SERVER['SCRIPT_NAME'])!='info.php'
&& (!(isset($_SESSION[$settings['session_prefix'].'user_type'])
&& $_SESSION[$settings['session_prefix'].'user_type']=='admin'))
&& $settings['forum_disabled']==1)
	{
	if (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] != 'admin')
		{
		session_destroy();
		setcookie("auto_login", "", 0);
		}
	header('location: '.$settings['forum_address'].'info.php?info=1');
	die('<a href="info.php?info=1">further...</a>');
	}

// look if IP is banned:
$ip_result = mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'ips' LIMIT 1", $connid);
if (!$ip_result) die($lang['db_error']);
$data = mysql_fetch_assoc($ip_result);
mysql_free_result($ip_result);

if (trim($data['list']) != '')
	{
	$banned_ips_array = explode(',', trim($data['list']));
	if (in_array($_SERVER["REMOTE_ADDR"], $banned_ips_array))
		{
		die($lang['ip_no_access']);
		}
	}

// look if user is banned:
if (isset($_SESSION[$settings['session_prefix'].'user_name']))
	{
	$ban_result = mysql_query("SELECT list FROM ".$db_settings['banlists_table']." WHERE name = 'users' LIMIT 1", $connid);
	if (!$ban_result) die($lang['db_error']);
	$data = mysql_fetch_assoc($ban_result);
	mysql_free_result($ban_result);
	if (trim($data['list']) != '')
		{
		$banned_users_array = explode(',', mb_strtolower(trim($data['list'])));
		if (in_array(mb_strtolower($_SESSION[$settings['session_prefix'].'user_name']),$banned_users_array) && $_SESSION[$settings['session_prefix'].'user_type']!='admin')
			{
			session_destroy();
			setcookie("auto_login", "", 0);
			header("location: ".$settings['forum_address']."login.php?msg=user_banned");
			die($lang['user_banned']);
			}
		}
	}

// determine last visit:
if (empty($_SESSION[$settings['session_prefix']."user_id"])
&& $settings['remember_last_visit'] == 1)
	{
	if (isset($_COOKIE['last_visit']))
		{
		$c_last_visit = explode(".", $_COOKIE['last_visit']);
		$c_last_visit[0] = (isset($c_last_visit[0])) ? trim($c_last_visit[0]) : time();
		$c_last_visit[1] = (isset($c_last_visit[1])) ? trim($c_last_visit[1]) : time();
		if ($c_last_visit[1] < (time() - 600))
			{
			$c_last_visit[0] = $c_last_visit[1];
			$c_last_visit[1] = time();
			setcookie("last_visit", $c_last_visit[0].".".$c_last_visit[1], time()+(3600*24*30));
			}
		}
	else
		{
		setcookie("last_visit", time().".".time(), time()+(3600*24*30));
		}
	}

$last_visit = (isset($c_last_visit)) ? $c_last_visit[0] : time();

if (isset($_GET['category'])) $category = intval($_GET['category']);
$categories = get_categories();
$category_ids = get_category_ids($categories);
if ($category_ids != false) $category_ids_query = implode(", ", $category_ids);
if (empty($category)) $category=0;

if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $category_accession = category_accession();

# count postings, threads, users and users online:
# no categories defined
if ($categories == false)
	{
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE pid = 0", $connid);
	list($thread_count) = mysql_fetch_row($count_result);
	mysql_free_result($count_result);
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table'], $connid);
	list($posting_count) = mysql_fetch_row($count_result);
	mysql_free_result($count_result);
	}
# there are categories
else if (is_array($categories))
	{
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE pid = 0 AND category IN (".$category_ids_query.")", $connid);
	list($thread_count) = mysql_fetch_row($count_result);
	mysql_free_result($count_result);
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE category IN (".$category_ids_query.")", $connid);
	list($posting_count) = mysql_fetch_row($count_result);
	mysql_free_result($count_result);
	}
else
	{
	$thread_count = 0;
	$posting_count = 0;
	}

$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['userdata_table'], $connid);
list($user_count) = mysql_fetch_row($count_result);

if ($settings['count_users_online'] == 1)
	{
	user_online();
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['useronline_table']." WHERE user_id > 0", $connid);
	list($useronline_count) = mysql_fetch_row($count_result);
	$count_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['useronline_table']." WHERE user_id = 0", $connid);
	list($guestsonline_count) = mysql_fetch_row($count_result);
	$counter = str_replace("[postings]", $posting_count, $lang['counter_uo']);
	$counter = str_replace("[threads]", $thread_count, $counter);
	$counter = str_replace("[users]", $user_count, $counter);
	$counter = str_replace("[total_online]", $useronline_count+$guestsonline_count, $counter);
	$counter = str_replace("[user_online]", $useronline_count, $counter);
	$counter = str_replace("[guests_online]", $guestsonline_count, $counter);
	}
else
	{
	$counter = str_replace("[forum_name]", '<a href="'.$settings['forum_address'].'">'.$settings['forum_name'].'</a>', $lang['counter']);
	$counter = str_replace("[contact]", '<a href="contact.php?forum_contact=true">'.$lang['contact_linkname'].'</a>', $counter);
	$counter = str_replace("[postings]", $posting_count, $counter);
	$counter = str_replace("[threads]", $thread_count, $counter);
	$counter = str_replace("[users]", $user_count, $counter);
	}
mysql_free_result($count_result);

$time_difference = (isset($settings['time_difference'])) ? $settings['time_difference'] : 0;

if (isset($_SESSION[$settings['session_prefix'].'user_time_difference']))
	{
	$time_difference = $_SESSION[$settings['session_prefix'].'user_time_difference']+$time_difference;
	}
else if (isset($_COOKIE['user_time_difference']))
	{
	$time_difference = $_COOKIE['user_time_difference']+$time_difference;
	}

?>