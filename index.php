<?php
# m3u organizer
# Run as server.
# This is a playlist organizer. Create yaml and m3u playlist

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");

if (!is_dir('trash')) mkdir('trash');
if (!is_dir('bg')) mkdir('bg');

$dh = opendir('.');
$curd = getcwd();
$plsf = '!PLST.m3u8';
$music = array();
$data = array(); // Songs
$string = ''; // Song list

function m3uc ($array) {
	$string = '';
	foreach ($array as $value) {
		$string .= $value . "\n";
	}
	return $string;
}

if (isset($_REQUEST['songs'])) {
	header('Content-Type: application/json');


	
	foreach($_REQUEST['songs'] as $k => $file) {
		$data[] = $file;
	}

	$string = m3uc($data);
	
	if (false !== file_put_contents($plsf, $string)) {
		echo json_encode(array('status' => 'ok'));
	} else echo json_encode(array('status' => 'fail'));
	die();
}
	
if (isset($_REQUEST['delete'])) {
	header('Content-Type: application/json');
	if (false !== @rename($_REQUEST['delete'], 'trash/'.$_REQUEST['delete'])) {
		echo json_encode(array('delete' => 'ok'));
	} else echo json_encode(array('delete' => 'fail'));
	die();
}
	
if (isset($_REQUEST['move'])) {
	header('Content-Type: application/json');
	if (false !== @rename($_REQUEST['move'], 'bg/'.$_REQUEST['move'])) {
		echo json_encode(array('move' => 'ok'));
	} else echo json_encode(array('move' => 'fail'));
	die();
}


if (file_exists($plsf)) {
	$data = file($plsf, FILE_IGNORE_NEW_LINES);
	$has_new = false;
	if (isset($_GET['update'])) {
		while (false !== ( $file = readdir($dh))) {
			$ext  = pathinfo($file, PATHINFO_EXTENSION);
			$name = pathinfo($file, PATHINFO_FILENAME);
			if ($ext == 'mp3' || $ext == 'wav' || $ext == 'mp4' ||  $ext == 'flac' ||  $ext == 'ogg' ||  $ext == 'm4a') {
				
				$data_n[] = $file;
			}
		}
		foreach ($data_n as $value) {
			if (false === array_search($value, $data)) {
				//echo $value, '<br>';
				array_unshift($data, $value);
				$has_new = true;
			}
			unset($value);
		}
		foreach ($data as $key => $value) {
			if (false === array_search($value, $data_n)) {
				unset($data[$key]);
			}
			unset($key, $value);
			$has_new = true;
		}
		
		if (!$has_new) echo '<a href="http:/'.$_SERVER['HTTP_HOST'].'">no new</a>';
			else {
				$string = m3uc($data);
				file_put_contents($plsf, $string);
				header("Location: http://{$_SERVER['HTTP_HOST']}");
			}
	}
	
} else {
	while (false !== ( $file = readdir($dh))) {
		$ext  = pathinfo($file, PATHINFO_EXTENSION);
		$name = pathinfo($file, PATHINFO_FILENAME);
		
		if ($ext == 'mp3' || $ext == 'wav' || $ext == 'mp4' ||  $ext == 'flac' ||  $ext == 'ogg' ||  $ext == 'm4a') {
			
			$data[] = $file;
		}
		$string = m3uc($data);
		file_put_contents($plsf, $string);
		
	}
	
}

//print_r($data);

?><!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>PLS Order</title>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<style>
	#sortable { list-style-type: none; margin: 0; padding: 0; width: 85%; }
	#sortable li { margin: 0 3px 3px 3px; padding: 0.05em .8em .7em 0.5em; font-size: 1.4em; height: 25px;}
	audio {vertical-align: middle; margin-right: 1em;}
	.highlight {background: antiquewhite;}
	.ui-sortable-helper {background: antiquewhite;}
	.mark {background: cornsilk;}
	.mark2 {background: azure;}
	#sortable li span {margin: 0 0 0 .3em; cursor: pointer; line-height: 25px; padding: 0 5px;}
	span:hover {background: grey;}
	</style>
	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<script>
		$( function() {
			$( "#sortable" ).sortable({
				stat: function(e, ui) {
				},
				stop: function(e, ui) {
					$('.ui-state-default').removeClass('highlight');
					ui.item.addClass('highlight');
					//console.log(ui);
					
					var elem = $(ui.item).find('audio')[0].pause();
					//console.log(elem);
					//elem[0].pause();
				},
				placeholder: "ui-state-highlight"
			});
			//$( "#sortable" ).disableSelection();
		} );
		
		let uri = "http://<?= $_SERVER['HTTP_HOST'] ?>";
		
		function save() {
			let songs = [];
			var listItems = $("#sortable li");
			listItems.each(function(idx, li) {
				var song = $(li).attr('data');
				//console.log(song);
				songs.push(song);
				
			});
			//console.log(songs);
			$.ajax({
			  method: "POST",
			  url: uri,
			  data: { songs }
			})
			.done(function( msg ) {
				//alert(11);
				window.location = uri;
			});
		}
		
		function delet(id, e) {
			//$(e).parent('li').remove();
			$.ajax({
			  method: "POST",
			  url: uri,
			  data: {'delete': id},
			  e: e
			})
			.done(function( msg ) {
				//alert(1);
				$(this.e).parent('li').remove();
			})
			.fail(function( msg ) {
				alert(2);
			})
		}
		
		function move(id, e) {
			$.ajax({
			  method: "POST",
			  url: uri,
			  data: {'move': id},
			  e: e
			})
			.done(function( msg ) {
				$(this.e).parent('li').remove();
			});
		}
		
		function up(e) {
			var li = $(e).parent('li');
			li.parent().prepend(li);
			li.find('audio')[0].pause();
		}
		
		function down(e) {
			var li = $(e).parent('li');
			li.parent().append(li);
			li.find('audio')[0].pause();
		}
	</script>
</head>
<body>
	<h1 style="margin-bottom: 0;">Playlist Organizer</h1>
	<h4 style="margin: 0;"><?= getcwd() ?></h4>
	<a href="http://<?= $_SERVER['HTTP_HOST'] ?>?update=1">UPDATE</a>
	<br>
	<br>
	<ul id="sortable">
		<?php 
			foreach ($data as $k => $song) {
				echo '<li class="ui-state-default" data="'.$song.'">';
				echo str_pad($k, 3, "0", STR_PAD_LEFT);
				echo '<span onclick="delet(\''.str_replace("'", "\'", $song).'\', this)">✕</span>';
				echo '<span onclick="up(this)">▲</span><span onclick="down(this)">▼</span>';
				echo '<span onclick="move(\''.str_replace("'", "\'", $song).'\', this)">➔</span>&nbsp;';
				echo '<audio controls preload="none" src="'.$song.'"></audio>';
				echo $song;
				echo '<input style="float: right;top: 11px;position: relative;" type="checkbox" onclick="$(this).parent().toggleClass(\'mark\');">';
				echo '<input style="float: right;top: 11px;position: relative;" type="checkbox" onclick="$(this).parent().toggleClass(\'mark2\');"></li>', PHP_EOL; 
				//echo '';
			}
		?>
	</ul>

	<button id="save" onclick="save();" style="position: fixed; top: 15px; right: 15px; padding: 5px; ">SAVE</button>
	
</body>
</html>