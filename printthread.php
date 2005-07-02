<?php
/**
 * MyBB 1.0
 * Copyright � 2005 MyBulletinBoard Group, All Rights Reserved
 *
 * Website: http://www.mybboard.com
 * License: http://www.mybboard.com/eula.html
 *
 * $Id$
 */

$templatelist = "printthread,printthread_post";

require "./global.php";
require "./inc/functions_post.php";

// Load global language phrases
$lang->load("printthread");

$query = $db->query("SELECT * FROM ".TABLE_PREFIX."threads WHERE tid='$tid' AND visible='1'");
$thread = $db->fetch_array($query);
$thread['subject'] = htmlspecialchars(stripslashes(dobadwords($thread['subject'])));
if(!$thread['tid'])
{
	error($lang->error_invalidthread);
}
$fid = $thread['fid'];
$query = $db->query("SELECT * FROM ".TABLE_PREFIX."forums WHERE fid='$thread[fid]' AND active!='no'");
$forum = $db->fetch_array($query);
$breadcrumb = makeprintablenav();

$parentsexp = explode(",", $forum['parentlist']);
$numparents = count($parentsexp);
$tdepth = "-";
for($i=0;$i<$numparents;$i++)
{
	$tdepth .= "-";
}
$forumpermissions = forum_permissions($forum['fid']);

if($forum['type'] != "f")
{
	error($lang->error_invalidforum);
}
if($forumpermissions['canview'] == "no" || $forumpermissions['canviewthreads'] == "no")
{
	nopermission();
}

// Password protected forums ......... yhummmmy!
checkpwforum($fid, $forum['password']);

$query = $db->query("SELECT u.*, u.username AS userusername, p.* FROM ".TABLE_PREFIX."posts p LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid) WHERE p.tid='$tid' ORDER BY p.dateline");
while($postrow = $db->fetch_array($query))
{
	if($postrow['userusername'])
	{
		$postrow['username'] = $postrow['userusername'];
	}
	$postrow['subject'] = htmlspecialchars(stripslashes(dobadwords($postrow['subject'])));
	$postrow['date'] = mydate($mybb->settings['dateformat'], $postrow['dateline']);
	$postrow['time'] = mydate($mybb->settings['timeformat'], $postrow['dateline']);
	$postrow['message'] = postify(stripslashes($postrow['message']), $forum['allowmycode'], $forum['allowsmilies'], $forum['allowimgcode']);
	// do me code
	if($forum['allowmycode'] != "no")
	{
		$postrow['message'] = domecode($postrow['message'], $postrow['username']);
	}

	eval("\$postrows .= \"".$templates->get("printthread_post")."\";");
}
eval("\$printable = \"".$templates->get("printthread")."\";");
outputpage($printable);

function makeprintablenav($pid="0", $depth="--")
{
	global $db, $pforumcache, $fid, $forum, $lang;
	if(!is_array($pforumcache))
	{
		$parlist = buildparentlist($fid, "fid", "OR", $forum['parentlist']);
		$query = $db->query("SELECT name, fid, pid FROM ".TABLE_PREFIX."forums WHERE 1=1 AND $parlist ORDER BY pid, disporder");
		while($forumnav = $db->fetch_array($query))
		{
			$pforumcache[$forumnav['pid']][$forumnav['fid']] = $forumnav;
		}
		unset($forumnav);
	}
	if(is_array($pforumcache[$pid]))
	{
		while(list($key, $forumnav) = each($pforumcache[$pid]))
		{
			$forums .= "+".$depth."$lang->forum $forumnav[name] (<i>".$mybb->settings[bburl]."/forumdisplay.php?fid=$forumnav[fid]</i>)<br>\n";
			if($pforumcache[$forumnav['fid']])
			{
				$newdepth = $depth."-";
				$forums .= makeprintablenav($forumnav['fid'], $newdepth);
			}
		}
	}
	return $forums;
}

?>