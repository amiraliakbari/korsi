<?php
open_DB();

$sym = ($_GET['sym']=='true') ? true : false;

switch ($_GET['action']){
	case 'sync':
		$r = get_row("SELECT timer_active, list_invalidated, list_index, reset_timer, payam FROM status WHERE id=1");
		if (!$sym) {
			mysql_query("UPDATE status SET reset_timer=reset_timer-1 WHERE id=1 AND reset_timer>0");
			mysql_query("UPDATE status SET payam=1 WHERE payam>1");
		}
		echo "{$r['timer_active']} {$r['list_invalidated']} {$r['list_index']} {$r['reset_timer']} {$r['payam']}";
		break;
		
	case 'list':
		//$r = get_row("SELECT timer_active, list_invalidated, list_index FROM status WHERE id=1");
		$l = array('list'=>array() /*, 'list_index'=>$r['list_index']*/);
		$rs = mysql_query("SELECT str1, str2, str3 FROM list ORDER BY place ASC");
		while ($row = mysql_fetch_assoc($rs)){
			$l['list'][] = array($row['str1'], $row['str2'], $row['str3']);
		} 
		echo json_encode($l);
		if (!$sym) {
			mysql_query("UPDATE status SET list_invalidated=0 WHERE id=1");
		}
		break;
		
	case 'get':
		$r = get_row("SELECT `value` FROM dic WHERE `key` LIKE '{$_GET['key']}'");
		echo $r['value'];
		break;

	case 'set':
		$r = get_row("SELECT `value` FROM dic WHERE `key` LIKE '{$_GET['key']}'");
		echo $r['value'];
		break;
		
	case 'start_timer':
		mysql_query('UPDATE status SET timer_active=1 WHERE id=1');
		break;
		
	case 'stop_timer':
		mysql_query('UPDATE status SET timer_active=0 WHERE id=1');
		break;
		
	case 'next':
		$r = get_row("SELECT COUNT(*) AS cnt FROM list");
		$l_cnt = $r['cnt'];		
		$r = get_row("SELECT list_index FROM status WHERE id=1");
		$i_cur = $r['list_index'];
		$i_new = min($i_cur+1, $l_cnt-1);
		$reset = ($i_new != $i_cur) ? '2' : 'reset_timer';
		
		mysql_query("UPDATE status SET list_index=$i_new, reset_timer=$reset WHERE id=1");
		break;
		
	case 'prev':
		$r = get_row("SELECT list_index FROM status WHERE id=1");
		$reset = ($r['list_index'] > 0) ? 2 : 'reset_timer';
		$i_cur = max(0, $r['list_index']-1);
		mysql_query("UPDATE status SET list_index=$i_cur, reset_timer=$reset WHERE id=1");
		break;
		
	case 'add':
		$r = get_row('SELECT COUNT(*) AS cnt FROM list WHERE place>0');
		$place = $r['cnt'] + 1;
		mysql_query("INSERT INTO list (place, str1, str2, str3) VALUES ($place, '{$_GET['str1']}', '{$_GET['str2']}', '{$_GET['str3']}')");
		mysql_query("UPDATE status SET list_invalidated=1 WHERE id=1");
		break;
		
	case 'del':
		$r = get_row("SELECT timer_active, list_invalidated, list_index FROM status WHERE id=1");
		$nli = $r['list_index'];
		$reset = ($nli == ($_GET['place']+0)) ? '2' : 'reset_timer';
		if ($nli >= ($_GET['place']+0))
			$nli = max(0, $nli-1);
		mysql_query("DELETE FROM list WHERE place={$_GET['place']}");
		mysql_query("UPDATE list SET place=place-1 WHERE place>{$_GET['place']}");
		mysql_query("UPDATE status SET list_invalidated=1, list_index=$nli, reset_timer=$reset WHERE id=1");
		break;

	case 'move':
		$r = get_row("SELECT timer_active, list_invalidated, list_index FROM status WHERE id=1");
		$nli = $r['list_index'];
		$reset = ($nli == ($_GET['place']+0)) ? '2' : 'reset_timer';
		if ($nli >= ($_GET['place']+0))
			$nli = max(0, $nli-1);
		mysql_query("DELETE FROM list WHERE place={$_GET['place']}");
		mysql_query("UPDATE list SET place=place-1 WHERE place>{$_GET['place']}");
		mysql_query("UPDATE status SET list_invalidated=1, list_index=$nli, reset_timer=$reset WHERE id=1");
		break;
		
	case 'clear_message':
		mysql_query("UPDATE status SET payam=0 WHERE id=1");
		break;
	
	case 'send_message':	
		mysql_query("UPDATE status SET payam=payam+1 WHERE id=1");
		mysql_query("UPDATE dic SET `value`='{$_GET['message']}' WHERE `key` LIKE 'message'");
		break;
		
	case 'reset':
		mysql_query("DELETE FROM list");
		mysql_query("UPDATE status SET timer_active=0, list_invalidated=1, list_index=0, reset_timer=2, payam=0 WHERE id=1");
		break;
		
	case 'reset_timer':
		mysql_query("UPDATE status SET timer_active=0, reset_timer=2 WHERE id=1");
		break;
		
	case 'refresh':
		mysql_query("UPDATE status SET list_invalidated=1 WHERE id=1");
		break;			
}

close_DB();



function open_DB(){
	mysql_connect('localhost', 'korsi', 'korsi');
	@mysql_select_db('korsi') or die( "Unable to select database");
	mysql_query('SET NAMES utf8');
}

function close_DB(){
	mysql_close();
}

function get_row($query){
	return mysql_fetch_assoc(mysql_query($query));
}
