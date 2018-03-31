<?php
function getimg($url) {
	$headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg';
	$headers[] = 'Connection: Keep-Alive';
	$headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
	$user_agent = 'php';
	$process = curl_init($url);
	curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($process, CURLOPT_HEADER, 0);
	curl_setopt($process, CURLOPT_USERAGENT, $user_agent); //check here
	curl_setopt($process, CURLOPT_TIMEOUT, 30);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
	$return = curl_exec($process);
	curl_close($process);
	return $return;
}

function getdmvideo($videoid, $pialert = false) {
	global $global;
	$pitemp = $pivar = $pisql = $piinsert = $piimg = $img_path = $pisearch = $picheck = '';

	$pitemp = @file_get_contents("https://api.dailymotion.com/video/" . $videoid . "?fields=title,id,description,duration,embed_url,thumbnail_120_url,created_time");
	if (empty($pitemp)) {
		if ($pialert) {
			echo '<script language="javascript">';
			echo 'alert("Video ID: ' . $videoid . ' is invalid")';
			echo '</script>';
		}
		return false;
		exit;
	}
	$pivar = json_decode($pitemp);
	
	$pisearch = "SELECT clean_title FROM videos WHERE clean_title = '" . $pivar->id ."'";
	$picheck = $global['mysqli']->query($pisearch);
	if (mysqli_num_rows($picheck) > 0) {
		if ($pialert) {
			echo '<script language="javascript">';
			echo 'alert("Video ID: ' . $videoid . ' is already exist")';
			echo '</script>';
		}
		return false;
		exit;
	}
	
	$pisql = "INSERT INTO `videos` (`id`, `title`, `clean_title`, `description`, `views_count`, `status`, `created`, `modified`, `users_id`, `categories_id`, `filename`, `duration`, `type`, `videoDownloadedLink`, `order`, `rotation`, `zoom`, `youtubeId`, `videoLink`, `next_videos_id`, `isSuggested`) VALUES (NULL, '" . strip_tags($global['mysqli']->real_escape_string($pivar->title)) . "', '" . $pivar->id . "', '" . strip_tags($global['mysqli']->real_escape_string($pivar->description)) . "', '" . rand(10000,100000) . "', 'a', '" . gmdate("Y-m-d H:i:s",$pivar->created_time) . "', '" . gmdate("Y-m-d H:i:s",$pivar->created_time) . "', '" . User::getId() . "', '1', 'dm_" . $pivar->id . "', '" . gmdate("H:i:s",$pivar->duration) . "', 'embed', NULL, '1', '0', '1', NULL, '" . $pivar->embed_url . "', NULL, '0')" ;
	
	$piinsert = $global['mysqli']->query($pisql);

	if ($piinsert !== TRUE) {
		$obj->error = "Error deleting configuration: " . $global['mysqli']->error;
		echo json_encode($obj);
		return false;
		exit;
	}

	$img_path = "{$global['systemRootPath']}videos/dm_{$pivar->id}.jpg";
	if (!file_exists($img_path)) {
		$piimg = getimg($pivar->thumbnail_120_url);$piimg = getimg($pivar->thumbnail_120_url);
		file_put_contents($img_path,$piimg);
	}
	return true;
}

function getdmchannel($channelid) {
	global $global;
	$pitemp = $pilist = $pivar = '';
	$pipage = 1;
	$pihasmore = true;
	$pibigsql = "INSERT INTO `videos` (`id`, `title`, `clean_title`, `description`, `views_count`, `status`, `created`, `modified`, `users_id`, `categories_id`, `filename`, `duration`, `type`, `videoDownloadedLink`, `order`, `rotation`, `zoom`, `youtubeId`, `videoLink`, `next_videos_id`, `isSuggested`) VALUES ";
	$pisqladd = '';
	while ($pihasmore) {
		$pitemp = @file_get_contents("https://api.dailymotion.com/user/" . $channelid . "/videos?limit=100&page=" . $pipage . "&fields=title,id,description,duration,embed_url,thumbnail_120_url,created_time");
		if (empty($pitemp)) {
			echo '<script language="javascript">';
			echo 'alert("Channel ID: ' . $channelid . ' is invalid")';
			echo '</script>';
			return false;
			exit;
		}
		$pivar = json_decode($pitemp);
		$pihasmore = $pivar->has_more;
		$pilist = $pivar->list;
		foreach ($pilist as $key => $pivideo) {
			
			$pisearch = "SELECT clean_title FROM videos WHERE clean_title = '" . $pivideo->id ."'";
			$picheck = $global['mysqli']->query($pisearch);
			if (mysqli_num_rows($picheck) > 0) {
				// echo "Video id: " . $pivideo->id . " is already exist ! <br/>";
				continue;
			}
			
			$pisqladd .= "(NULL, '" . strip_tags($global['mysqli']->real_escape_string($pivideo->title)) . "', '" . $pivideo->id . "', '" . strip_tags($global['mysqli']->real_escape_string($pivideo->description)) . "', '" . rand(10000,100000) . "', 'a', '" . gmdate("Y-m-d H:i:s",$pivideo->created_time) . "', '" . gmdate("Y-m-d H:i:s",$pivideo->created_time) . "', '" . User::getId() . "', '1', 'dm_" . $pivideo->id . "', '" . gmdate("H:i:s",$pivideo->duration) . "', 'embed', NULL, '1', '0', '1', NULL, '" . $pivideo->embed_url . "', NULL, '0'),";
			
			$img_path = "{$global['systemRootPath']}videos/dm_{$pivideo->id}.jpg";
			if (!file_exists($img_path)) {
				$piimg = getimg($pivideo->thumbnail_120_url);
				file_put_contents($img_path,$piimg);
			}
		}
		$pipage +=1;
	}
	if (!empty($pisqladd)) {
		$piinsert = $global['mysqli']->query(rtrim($pibigsql . $pisqladd,","));

		if ($piinsert !== TRUE) {
			$obj->error = "Error deleting configuration: " . $global['mysqli']->error;
			echo json_encode($obj);
			return false;
			exit;
		}
		
	}
	else {
		echo '<script language="javascript">';
		echo 'alert("No video is added !")';
		echo '</script>';
	}
	return true;
}
function piupdate() {
	$pipost = $_POST;

	if (!empty($pipost['channelid'])) {
		getdmchannel($pipost['channelid']);
		return 'a channel';
	} else {
	   if (!empty($pipost['videoid'])) {
		   getdmvideo($pipost['videoid'],$pialert = true);
	   }
	   return 'a video';
	}
}
?>