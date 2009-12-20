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
#                                                                             #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

include("inc.php");

if(count($_GET) > 0)
foreach($_GET as $key => $value)
$$key = $value;




function mix_tree($id, $aktuellerEintrag = 0, $tiefe = 0) {
global $settings, $parent_array, $child_array, $page, $order, $category, $descasc, $last_visit, $lang;
		
echo '<div class="threadkl" style="margin-left: ';
if ($tiefe==0 or $tiefe >= ($settings['max_thread_indent_mix']/$settings['thread_indent_mix']))
	{
	echo "0";
	}
else
	{
	echo $settings['thread_indent_mix'];
	}
echo 'px;">';

//[... Zeile mit den Eintragsdaten oder einem Link ausgeben ...]
if ($parent_array[$id]["pid"]!=0)
	{
	echo '<a class="';
	if (($aktuellerEintrag == 0
		&& isset($_SESSION[$settings['session_prefix'].'newtime'])
		&& $_SESSION[$settings['session_prefix'].'newtime'] < $parent_array[$id]["time"])
		|| ($aktuellerEintrag == 0
		&& empty($_SESSION[$settings['session_prefix'].'newtime'])
		&& $parent_array[$id]["time"] > $last_visit))
		{
		echo "replynew";
		}
	else
		{
		echo "reply";
		}
	echo '" href="mix_entry.php?id='.$parent_array[$id]["tid"];
	if ($page != 0 || $category != 0 || $order != "last_answer" || $descasc != "DESC")
		{
		echo '&amp;page='.$page.'&amp;category='.$category;
		echo '&amp;order='.$order.'&amp;descasc='.$descasc;
		}
	echo '#p'.$parent_array[$id]["id"].'" title="'.htmlspecialchars($parent_array[$id]["name"]);
	echo ", ".strftime($lang['time_format'],$parent_array[$id]["Uhrzeit"]).'">'.htmlspecialchars($parent_array[$id]["subject"]).'</a>';
	}

// Anfang der Schleife über alle Kinder ...
if (isset($child_array[$id]) && is_array($child_array[$id]))
	{
    foreach($child_array[$id] as $kind)
    	{
      mix_tree($kind, $aktuellerEintrag, $tiefe+1);
		}
	}
echo '</div>'."\n";
} # End: mix_tree

if (!isset($_SESSION[$settings['session_prefix'].'user_id'])
&& isset($_COOKIE['auto_login'])
&& isset($settings['autologin'])
&& $settings['autologin'] == 1)
	{
	header("location: ".$settings['forum_address']."login.php?referer=mix.php");
	die("<a href=\"login.php?referer=mix.php\">further...</a>");
	}

if ($settings['access_for_users_only'] == 1
&& isset($_SESSION[$settings['session_prefix'].'user_name'])
|| $settings['access_for_users_only']  != 1)
	{
	if ($settings['remember_userstandard'] == 1
	&& !isset($_SESSION[$settings['session_prefix'].'newtime']))
		{
		setcookie("user_view","mix",time()+(3600*24*30));
		}
	unset($zeile);

	if (empty($page)) $page = 0;
	if (empty($order)) $order="last_answer";
	if (empty($descasc)) $descasc="DESC";
	if (isset($descasc) && $descasc=="ASC")
		{
		$descasc = "ASC";
		}
	else
		{
		$descasc = "DESC";
		}
	$ul = $page * $settings['topics_per_page'];

	# Variablen korrekt (de)initialisieren
	unset($parent_array);
	unset($child_array);

	# database request
	# no categories defined
	if ($categories == false)
		{
		$threadsNoCatsQuery = "SELECT
		id,
		pid,
		tid,
		user_id,
		UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS Uhrzeit,
		UNIX_TIMESTAMP(last_answer + INTERVAL ".$time_difference." HOUR) AS la_Uhrzeit,
		UNIX_TIMESTAMP(last_answer) AS last_answer,
		name,
		subject,
		category,
		marked,
		fixed,
		views
		FROM ".$db_settings['forum_table']."
		WHERE pid = 0
		ORDER BY fixed DESC, ".$order." ".$descasc."
		LIMIT ".$ul.", ".$settings['topics_per_page'];
		$result = mysql_query($threadsNoCatsQuery, $connid);
		if(!$result) die($lang['db_error']);
		}
	# there are categories and all categories should be shown
	else if (is_array($categories) && $category == 0)
		{
		$threadsCatsQuery = "SELECT
		id,
		pid,
		tid,
		user_id,
		UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS Uhrzeit,
		UNIX_TIMESTAMP(last_answer + INTERVAL ".$time_difference." HOUR) AS la_Uhrzeit,
		UNIX_TIMESTAMP(last_answer) AS last_answer,
		name,
		subject,
		category,
		marked,
		fixed,
		views
		FROM ".$db_settings['forum_table']."
		WHERE pid = 0 AND category IN (".$category_ids_query.")
		ORDER BY fixed DESC, ".$order." ".$descasc."
		LIMIT ".$ul.", ".$settings['topics_per_page'];
		$result = mysql_query($threadsCatsQuery, $connid);
		if (!$result) die($lang['db_error']);
		}
	# there are categories and all categories should be shown
	else if (is_array($categories) && $category != 0 && in_array($category, $category_ids))
		{
		$threadsSingleCatQuery = "SELECT
		id,
		pid,
		tid,
		user_id,
		UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS Uhrzeit,
		UNIX_TIMESTAMP(last_answer + INTERVAL ".$time_difference." HOUR) AS la_Uhrzeit,
		UNIX_TIMESTAMP(last_answer) AS last_answer,
		name,
		subject,
		category,
		marked,
		fixed,
		views
		FROM ".$db_settings['forum_table']."
		WHERE category = '".mysql_real_escape_string($category)."' AND pid = 0
		ORDER BY fixed DESC, ".$order." ".$descasc."
		LIMIT ".$ul.", ".$settings['topics_per_page'];
		$result = mysql_query($threadsSingleCatQuery, $connid);
		if (!$result) die($lang['db_error']);
		# how many entries?
		$pid_result = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE pid = '0' AND category = '".mysql_real_escape_string($category)."'", $connid);
		list($thread_count) = mysql_fetch_row($pid_result);
		mysql_free_result($pid_result);
		}

	$subnav_1 = '<a class="textlink" href="posting.php?view=mix&amp;category='.$category.'" title="'.$lang['new_entry_linktitle'].'">'.$lang['new_entry_linkname'].'</a>';
	$subnav_2 = '';
	if (isset($_SESSION[$settings['session_prefix'].'user_id']))
		{
		$subnav_2 .= '<a href="index.php?update=1&amp;view=mix&amp;category='.$category.'"><img src="img/update.gif" alt="" title="'.$lang['update_time_linktitle'].'" width="9" height="9" onmouseover="this.src=\'img/update_mo.gif\';" onmouseout="this.src=\'img/update.gif\';" /></a>';
		}
	if ($settings['thread_view'] == 1 && $category == 0)
		{
		$subnav_2 .= ' &nbsp;<span class="small"><a href="forum.php" title="'.$lang['thread_view_linktitle'].'"><img src="img/thread.gif" alt="'.$lang['thread_view_linkname'].'" width="12" height="9" title="'.$lang['thread_view_linktitle'].'" />'.$lang['thread_view_linkname'].'</a></span>';
		}
	else if ($settings['thread_view'] == 1 && $category != 0)
		{
		$subnav_2 .= ' &nbsp;<span class="small"><a href="forum.php?category='.$category.'" title="'.$lang['thread_view_linktitle'].'"><img src="img/thread.gif" alt="'.$lang['thread_view_linkname'].'" width="12" height="9" title="'.$lang['thread_view_linktitle'].'" />'.$lang['thread_view_linkname'].'</a></span>';
		}
	if ($settings['board_view']==1 && $category == 0)
		{
		$subnav_2 .= ' &nbsp;<span class="small"><a href="board.php" title="'.$lang['board_view_linktitle'].'"><img src="img/board.gif" alt="'.$lang['board_view_linkname'].'" width="12" height="9" title="'.$lang['board_view_linktitle'].'" />'.$lang['board_view_linkname'].'</a></span>';
		}
	else if ($settings['board_view']==1 && $category != 0)
		{
		$subnav_2 .= ' &nbsp;<span class="small"><a href="board.php?category='.$category.'" title="'.$lang['board_view_linktitle'].'"><img src="img/board.gif" alt="'.$lang['board_view_linkname'].'" width="12" height="9" title="'.$lang['board_view_linktitle'].'" />'.$lang['board_view_linkname'].'</a></span>';
		}
	$subnav_2 .= nav($page, $settings['topics_per_page'], $thread_count, $order, $descasc, $category);
	$categories = get_categories();
	if ($categories!=false && $categories != "not accessible")
		{
		$subnav_2 .= '&nbsp;&nbsp;<form method="get" action="mix.php" title="'.$lang['choose_category_formtitle'].'"><div style="display: inline;"><select class="kat" size="1" name="category" onchange="this.form.submit();">';
		if (isset($category) && $category==0)
			{
			$subnav_2 .= '<option value="0" selected="selected">'.$lang['show_all_categories'].'</option>';
			}
		else
			{
			$subnav_2 .= '<option value="0">'.$lang['show_all_categories'].'</option>';
			}
		while(list($key, $val) = each($categories))
			{
			if($key!=0)
				{
				if($key==$category)
					{
					$subnav_2 .= '<option value="'.$key.'" selected="selected">'.$val.'</option>';
					}
				else
					{
					$subnav_2 .= '<option value="'.$key.'">'.$val.'</option>';
					}
				}
			}
		$subnav_2 .= '</select><noscript> <input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></noscript></div></form>';
		}

	parse_template();
	echo $header;

	if ($thread_count > 0 && isset($result))
		{
		echo '<table class="normaltab">'."\n";
		echo '<tr>'."\n";
		echo '<th><a href="mix.php?category='.$category.'&amp;order=subject&amp;descasc=';
		echo ($descasc=="ASC" && $order=="subject") ? 'DESC' : 'ASC';
		echo '" title="'.$lang['order_linktitle'].'">'.$lang['board_subject_headline'].'</a>';
		if ($order=="subject" && $descasc=="ASC")
			{
			echo '&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" />';
			}
		else if ($order=="subject" && $descasc=="DESC")
			{
			echo '&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" />';
			}
		echo '</th>'."\n";
		if ($categories!=false && $category == 0)
			{
			echo '<th>'.$lang['board_category_headline'];
			if ($order=="category" && $descasc=="ASC")
				{
				echo '&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" />';
				}
			else if ($order=="category" && $descasc=="DESC")
				{
				echo '&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" />';
				}
			echo '</th>'."\n";
			}
		echo '<th><a href="mix.php?category='.$category.'&amp;order=name&amp;descasc=';
		echo ($descasc=="ASC" && $order=="name") ? 'DESC' : 'ASC';
		echo '" title="'.$lang['order_linktitle'].'">'.$lang['board_author_headline'].'</a>';
		if ($order=="name" && $descasc=="ASC")
			{
			echo '&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" />';
			}
		else if ($order=="name" && $descasc=="DESC")
			{
			echo '&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" />';
			}
		echo '</th>'."\n";
		echo '<th><a href="mix.php?category='.$category.'&amp;order=time&amp;descasc=';
		echo ($descasc=="DESC" && $order=="time") ? 'ASC' : 'DESC';
		echo '" title="'.$lang['order_linktitle'].'">'.$lang['board_date_headline'].'</a>';
		if ($order=="time" && $descasc=="ASC")
			{
			echo '&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" />';
			}
		else if ($order=="time" && $descasc=="DESC")
			{
			echo '&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" />';
			}
		echo '</th>'."\n";
		echo '<th>'.$lang['board_answers_headline'].'</th>'."\n";
		echo '<th><a href="mix.php?category='.$category.'&amp;order=last_answer&amp;descasc=';
		echo ($descasc=="DESC" && $order=="last_answer") ? 'ASC' : 'DESC';
		echo '" title="'.$lang['order_linktitle'].'">'.$lang['board_last_answer_headline'].'</a>';
		if ($order=="last_answer" && $descasc=="ASC")
			{
			echo '&nbsp;<img src="img/asc.gif" alt="[asc]" width="5" height="9" />';
			}
		else if ($order=="last_answer" && $descasc=="DESC")
			{
			echo '&nbsp;<img src="img/desc.gif" alt="[desc]" width="5" height="9" />';
			}
		echo '</th>'."\n";
		if (isset($settings['count_views']) && $settings['count_views'] == 1)
			{
			echo '<th>'.$lang['views_headline'].'</th>'."\n";
			}
		if (isset($_SESSION[$settings['session_prefix'].'user_type'])
		&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
			{
			echo '<th>&nbsp;</th>'."\n";
			}
		echo '</tr>';

		$i=0;
		while ($zeile = mysql_fetch_array($result))
			{
			# count replies:
			$pid_resultc = mysql_query("SELECT COUNT(*) FROM ".$db_settings['forum_table']." WHERE tid = ".$zeile["tid"], $connid);
			list($answers_count) = mysql_fetch_row($pid_resultc);
			$answers_count = $answers_count - 1;
			mysql_free_result($pid_resultc);

			# data for link to last reply:
			if ($settings['last_reply_link'] == 1)
				{
				$last_answer_result = mysql_query("SELECT name, id FROM ".$db_settings['forum_table']." WHERE tid = ".$zeile["tid"]." ORDER BY time DESC LIMIT 1", $connid);
				$last_answer = mysql_fetch_array($last_answer_result);
				mysql_free_result($last_answer_result);
				}
			$rowClass = ($i % 2 == 0) ? "a" : "b";
			echo '<tr class="'.$rowClass.'">'."\n";
			echo '<td>'."\n";
			echo '<a class="';
			if ((isset($_SESSION[$settings['session_prefix'].'newtime'])
			&& $_SESSION[$settings['session_prefix'].'newtime'] < $zeile["last_answer"])
			|| (($zeile["pid"]==0)
			&& empty($_SESSION[$settings['session_prefix'].'newtime'])
			&& $zeile["last_answer"] > $last_visit))
				{
				echo "threadnew";
				}
			else
				{
				echo "thread";
				}
			echo '" href="mix_entry.php?id='.$zeile["tid"];
			if ($page != 0 || $category != 0 || $order != "last_answer" || $descasc != "DESC")
				{
				echo '&amp;page='.$page.'&amp;category='.$category;
				echo '&amp;order='.$order.'&amp;descasc='.$descasc;
				}
			echo '">'.htmlspecialchars($zeile["subject"]).'</a>';
			if ($zeile["fixed"] == 1)
				{
				echo ' <img src="img/fixed.gif" width="9" height="9" title="'.$lang['fixed'].'" alt="*" />';
				}
			if ($settings['all_views_direct'] == 1)
				{
				echo " <span class=\"small\">";
				if ($settings['board_view'] == 1)
					{
					echo '<a href="board_entry.php?id='.$zeile["tid"].'">';
					echo '<img src="img/board_d.gif" alt="[Board]" title="';
					echo $lang['open_in_board_linktitle'].'" width="12" height="9" /></a>';
					}
				if ($settings['thread_view']==1)
					{
					echo '<a href="forum_entry.php?id='.$zeile["tid"].'">';
					echo '<img src="img/thread_d.gif" alt="[Thread]" title="';
					echo $lang['open_in_thread_linktitle'].'" width="12" height="9" /></a>';
					}
				echo "</span>";
				}
			$postingIdsQuery = "SELECT
			id,
			pid,
			tid,
			UNIX_TIMESTAMP(time) AS time,
			UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS Uhrzeit,
			name,
			subject,
			category
			FROM ".$db_settings['forum_table']."
			WHERE tid = ".$zeile["tid"]."
			ORDER BY time ASC";
			$thread_result = mysql_query($postingIdsQuery, $connid);
			# Ergebnisse einlesen:
			while($tmp = mysql_fetch_array($thread_result))
				{
				$parent_array[$tmp["id"]] = $tmp;          // Ergebnis im Array ablegen
				$child_array[$tmp["pid"]][] =  $tmp["id"]; // Vorwärtsbezüge konstruieren
				}
			mix_tree($zeile["tid"]);
			mysql_free_result($thread_result);
			echo '</td>'."\n";
			if ($categories!=false && $category == 0)
				{
				echo '<td>'."\n";
				if (isset($categories[$zeile["category"]]) && $categories[$zeile["category"]]!='')
					{
					echo '<a title="'.str_replace("[category]", $categories[$zeile["category"]], $lang['choose_category_linktitle']);
					if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 2)
						{
						echo " ".$lang['admin_mod_category'];
						}
					else if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 1)
						{
						echo " ".$lang['registered_users_category'];
						}
					echo '" href="mix.php?category='.$zeile["category"].'"><span class="';
					if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 2)
						{
						echo "category-adminmod-b";
						}
					else if (isset($category_accession[$zeile["category"]])
					&& $category_accession[$zeile["category"]] == 1)
						{
						echo "category-regusers-b";
						}
					else
						{
						echo "category-b";
						}
					echo '">'.$categories[$zeile["category"]].'</span></a>';
					}
				else
					{
					echo "&nbsp;";
					}
				echo '</td>'."\n";
				}
			echo '<td>'."\n";
			if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["user_id"] > 0)
				{
				$sult = str_replace("[name]", htmlspecialchars($zeile["name"]), $lang['show_userdata_linktitle']);
				echo '<a href="user.php?id='.$zeile["user_id"].'" title="'.$sult.'">';
				}
			echo '<span class="small">'.htmlspecialchars($zeile["name"]).'</span>';
			if ($zeile["user_id"] > 0 && $settings['show_registered'] ==1)
				{
				echo '<img src="img/registered.gif" alt="(R)" width="10" height="10"';
				echo ' title="'.$lang['registered_user_title'].'" />';
				}
			if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $zeile["user_id"] > 0)
				{
				echo '</a>';
				}
			echo '</td>'."\n";
			echo '<td><span class="small">'.strftime($lang['time_format'],($zeile["Uhrzeit"])).'</span></td>'."\n";
			echo '<td><span class="small">'.$answers_count.'</span></td>'."\n";
			echo '<td><span class="small">';
			if ($answers_count > 0)
				{
				if ($settings['last_reply_link']==1)
					{
					echo '<a href="mix_entry.php?id='.$zeile["tid"].'&amp;page='.$page;
					echo '&amp;category='.$category.'&amp;order='.$order.'&amp;descasc=';
					echo $descasc.'#p'.$last_answer['id'].'" title="';
					echo str_replace("[name]", $last_answer['name'], $lang['last_reply_lt']).'">';
					}
				echo strftime($lang['time_format'],$zeile["la_Uhrzeit"]);
				if ($settings['last_reply_link']==1)
					{
					echo '</a>';
					}
				}
			else
				{
				echo "&nbsp;";
				}
			echo '</span></td>'."\n";
			if (isset($settings['count_views']) && $settings['count_views'] == 1)
				{
				echo '<td><span class="small">'.$zeile['views'].'</span></td>'."\n";
				}
			if (isset($_SESSION[$settings['session_prefix'].'user_type'])
			&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
				{
				echo '<td><a href="admin.php?mark='.$zeile["tid"].'&amp;refer=';
				echo basename($_SERVER["SCRIPT_NAME"]).'&amp;page='.$page.'&amp;category=';
				echo $category.'&amp;order='.$order.'">';
				if ($zeile['marked']==1)
					{
					echo '<img src="img/marked.gif" alt="[x]" width="9" height="9"';
					echo ' title="'.$lang['unmark_linktitle'].'" />';
					}
				else
					{
					echo '<img src="img/mark.gif" alt="[-]" title="';
					echo $lang['mark_linktitle'].'" width="9" height="9" />';
					}
				echo '</a></td>'."\n";
				}
			echo '</tr>';
			$i++;
			}
		echo "\n".'</table>'."\n";
		mysql_free_result($result);
		echo outputManipulateMarked('mix');
		}
	else
		{
		if ($category!=0) echo "<p>".$lang['no_messages_in_category']."</p>";
		else echo "<p>".$lang['no_messages']."</p>";
		}
	echo $footer;
	}
else
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess");
	die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}
?>