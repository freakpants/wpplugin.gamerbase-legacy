<?php
global $riot_api_key;
// shortcodes
add_shortcode( 'matchticker_unsorted', 'output_match_ticker_unsorted' );
add_shortcode( 'matchticker', 'output_match_ticker' );
add_shortcode( 'vodticker', 'output_vod_ticker' );
add_shortcode( 'toplist', 'output_lol_toplist' );
add_shortcode( 'lol_match_details', 'shortcode_lol_match_details');
add_shortcode( 'player_profile', 'shortcode_lol_player_profile' );
add_shortcode( 'lolteamhistory', 'output_lol_team_history' );
add_shortcode( 'link_summoner', 'link_summoner' );

require('random_functions.php');

// error_reporting(E_ALL);

function link_summoner(){
	global $riot_api_key;	
	// REPLACE DB CONNECTION
	$user_ID = get_current_user_id();
	$request_exists = false;
	$already_confirmed = false;
	// check if request already exists
	$sql = "SELECT confirmed, code, lkid FROM players2wordpress WHERE wordpress_id = :user_id";
	$stmt = $db->prepare($sql);
	$stmt->execute(array(':user_id' => $user_ID));
	$confirms = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($confirms as $confirm){
		$request_exists = true;
		if($confirm['confirmed'] == 1){
			$already_confirmed = true;
			echo 'You have already successfully linked your summoner account!</br></br>';
		} else if (!isset($_GET['code_given'])) {
			echo '
			<form action="/poros_link_summoner/" method="get">
				<input type="hidden" name="code_given" value="true">
				<input type="hidden" name="lkid" value="'.$confirm['lkid'].'">
				</br>
				You have a pending request already. Please rename your first runepage to <b>'.$confirm['code'].'</b> and click <input type="submit" value="here">
			</form>
			</br></br>';
		}
	}
	if(isset($_GET['code_given']) &&  !$already_confirmed){
		// if the code was provided to the player, and he clicked ok, check his runepages
		// the url for api lookup
		$url = 'https://euw.api.pvp.net/api/lol/euw/v1.4/summoner/'.urlencode(str_replace(' ', '',$_GET['lkid'])).'/runes'; 
		// lookup data from riot
		$url .= "?api_key=".$riot_api_key;
		$json = file_get_contents($url);
		$runepages = json_decode($json);
		
		$runepagename = $runepages->$_GET['lkid']->pages[0]->name;

		echo 'Your first Runepage is called: '.$runepagename.'</br>';
		
		$sql = "SELECT code FROM players2wordpress WHERE lkid = :lkid";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':lkid' => $_GET['lkid']));
		$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($codes as $code){
			if($code['code'] == $runepagename){
				echo 'Your account was successfully linked!';
				$sql = "UPDATE players2wordpress SET confirmed = 1 WHERE lkid = :lkid";
				$stmt = $db->prepare($sql);
				$stmt->execute(array(':lkid' => $_GET['lkid']));
			} else {
				echo 'The name of your first runepage is not <b>'.$code['code'].'</b>. Please rename it and try again.';
			}
		}

	}
	// TODO: error if no request exists
	// if a summoner was sent, check the toplist
	elseif(isset($_GET['summoner']) && !$already_confirmed && !$request_exists){
		$summoner = $_GET['summoner'];
		$sql = "SELECT * FROM `players` WHERE player = :player";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':player'=>$summoner));
		$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$in_toplist = false;
		foreach($players as $player){
			$in_toplist = true;
			$lkid = $player['lkid'];
		}
		if(!$in_toplist){
			$summoner = strtolower(str_replace(' ', '',$_GET['summoner']));
			// todo: add the player to the toplist first
			
			// check summoner name with riot
			$url = 'https://euw.api.pvp.net/api/lol/euw/v1.4/summoner/by-name/'.urlencode($summoner).'/'; 
			// lookup data from riot
			$url .= "?api_key=".$riot_api_key;
			
			$json = file_get_contents($url);
			$riot_players = json_decode($json);
			
			if(!$json){
				$request_failed = true;
				echo 'Request failed. Summoner was not found on <b>EUW</b> Please check if you spelled your summoner correctly.';
			} else {
				$lkid =  $riot_players->$summoner->id;
				$sql = "INSERT INTO players_notoplist (lkid, summoner) VALUES ($lkid, :summoner)";
				$stmt = $db->prepare($sql);
				$stmt->execute(array(':summoner' => $summoner));
			}
		}
		
		if(!$request_failed){
			// generate random code for rune page
			$code = get_rand_alphanumeric(12);
			echo '
			Please rename your first runepage to the following: '.$code.'</br>
			<form action="/poros_link_summoner/" method="get">
				<input type="hidden" name="code_given" value="true">
				<input type="hidden" name="lkid" value="'.$lkid.'">
				<input type="submit" value="ok"></br>
			</form>';
			
			$user_ID = get_current_user_id();
			$sql = "INSERT INTO players2wordpress (lkid,wordpress_id,code) VALUES ($lkid,$user_ID,'$code')";
			$stmt = $db->prepare($sql);
			$stmt->execute();
		}
		
	} else if (!$request_exists) {
		// if no summoner was provided, ask the user for his summoner name
		echo '
		Please enter the name of your Summoner on EUW:</br></br>
		<form action="/poros_link_summoner/" method="get">
			<input type="text" name="summoner"></br></br>
			<input type="submit" value="ok">
		</form>';
	}
}
function display_match_details($matchid){
	$html .= 
		"<table class='match-details' >
		";
	
	// REPLACE DB CONNECTION
	$sql = "
	SELECT winner,loser, datetime, json,riot_json, winner_teamside, match_identity, event, phase, vod
	FROM tournament_matches WHERE match_identity = '$matchid'";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($matches as $match) {
		// determine names of teams
		$sql = 'SELECT name, teampage FROM teams WHERE teamidentity = :teamidentity';
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':teamidentity' => $match['winner']));
		$winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$winner_page = '';
		foreach($winners as $winner){
			$winner_team = $winner['name'];
			$winner_page = $winner['teampage'];
		}
		
		$sql = 'SELECT name, teampage FROM teams WHERE teamidentity = :teamidentity';
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':teamidentity' => $match['loser']));
		$losers = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$loser_page = '';
		foreach($losers as $loser){
			$loser_team = $loser['name'];
			$loser_page = $loser['teampage'];
		}

		// get match information for the header
		$datetime = date("d.m H:i",$match['datetime']+7200);
		$date = date("d.m.",$match['datetime']+7200);
		$time = date("H:i",$match['datetime']+7200);
		$game = json_decode($match['json'],true);
		$event = $match['event'];
		$phase = $match['phase'];
		$riot_json = json_decode($match['riot_json']);
		$mode = $riot_json->matchMode;
		
		// displayer event info header
		$html .= '
			<tr>
				<td colspan="7">
					<table>
						<tr>
							<th>When</th>
							<th>Event</th>
							<th>Phase</th>
							<th>Mode</th>
							<th>Id</th>
						</tr>
						<tr>
							<td>'.$datetime.'</td>
							<td>'.$event.'</td>
							<td>'.$phase.'</td>
							<td>'.$mode.'</td>
							<td>'.$match['match_identity'].'</td>
						</tr>
					</table>
				</td>
			</tr>
				
		';
		if($match['vod'] != ''){
			$html .= '</tr><tr><td colspan="7">
			<div class="videoContainer">
				<iframe width="500" height="281" src="https://www.youtube.com/embed/'.$match['vod'].'?feature=oembed" frameborder="0" allowfullscreen=""></iframe>
			</div>
			</td></tr>';
		}
		

		
		$bans_0 = ($riot_json->teams[0]->bans);
		$bans_1 = ($riot_json->teams[1]->bans);
		
		// display winner header
		$html .= '
				</tr><tr>
				<td colspan="7" style="font-weight:bold; color: green">
					'.$winner_team;
					if($winner_page != '') $html .= ' - <b><a href="'.$winner_page.'">Teampage</a></b>';
					$html .= '	
				</td>
			';
		$html .= '
			<tr>
				<td colspan="7" class="bans"><p class="block_text">Bans:</p> ';
		if($match['winner_teamside'] == 0 || $match['winner_teamside'] == 1){
			foreach($bans_0 as $ban){
				$champion_key = get_champion_key($ban->championId);
				$version = '6.7.1';
				$html .= '<img style="display:block; float:left; height:30px;" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/champion/'.$champion_key.'.png">';
			}
		} else {
			foreach($bans_1 as $ban){
				$champion_key = get_champion_key($ban->championId);
				$version = '6.7.1';
				$html .= '<img style="display:block; float:left; height:30px;" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/champion/'.$champion_key.'.png">';
			}
		}
			$html .= '
				</td>';


		$counter = 0;
		
		// loop over the winners
		if($match['winner_teamside'] == 0 || $match['winner_teamside'] == 1){
			$html .= display_lol_match_players($game['team1'], $riot_json, $match['match_identity'],1);
		} else {
			$html .= display_lol_match_players($game['team2'], $riot_json, $match['match_identity'],2);
		}
		
		// display loser header
				$html .= '
				</tr><tr>
				<td  colspan="7" style="font-weight:bold; color: red">
					'.$loser_team;
					if($loser_page != '') $html .= ' - <b><a href="'.$loser_page.'">Teampage</a></b>';
					$html .= '	
				</td>
			</tr>
			';
		
			$html .= '
				<td colspan="7" class="bans"><p class="block_text">Bans:</p> ';
		if($match['winner_teamside'] == 0 || $match['winner_teamside'] == 1){
			foreach($bans_1 as $ban){
				$champion_key = get_champion_key($ban->championId);
				$version = '6.7.1';
				$html .= '<img style="display:block; float:left; height:30px;" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/champion/'.$champion_key.'.png">&nbsp;';
			}
		} else {
			foreach($bans_0 as $ban){
				$champion_key = get_champion_key($ban->championId);
				$version = '6.7.1';
				$html .= '<img style="display:block; float:left; height:30px;" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/champion/'.$champion_key.'.png">&nbsp;';
			}
		} 


		$counter = 0;
		
		// loop over the losers
		if($match['winner_teamside'] == 0 || $match['winner_teamside'] == 1){
			$html .= display_lol_match_players($game['team2'], $riot_json, $match['match_identity'],2);
		} else {
			$html .= display_lol_match_players($game['team1'], $riot_json, $match['match_identity'],1);
		}
		
		
		

	
	$time_end = microtime(true);

	//dividing with 60 will give the execution time in minutes other wise seconds
	$execution_time = ($time_end - $time_start);

	//execution time of the script
	// $html .= '<b>Total Execution Time:</b> '.$execution_time.' Secs';
	
	}
	
	$html .= '</table>';
	return $html;
}

// sanitized: 15.01.2017
function output_lol_toplist($atts){	
	global $wpdb;
	try{
		$results = $wpdb->get_results("SELECT rank, lastrank, player, leaguepoints, wins, losses, lastgame, division, teamidentity, lkid FROM loldata_players where rank != 0 AND division != '' ORDER BY rank ASC");
	
		if (count($results) > 0){
			$players = $results;
			
			// add the area for challenger
			
			$html .= '
				<div class="division_ranking">
					<div class="division">
						<div class="division_icon">
							<div class="division_icon_content">
								<img src="' . plugins_url('/assets/images/toplist_medals/challenger_1.png' , __FILE__ ) . '" alt="Division I">
								<span class="division_name">Challenger</span>
								<span class="division_number">I</span>
							</div>
						</div>
						<div class="division_content">
							<table>
								<thead>
									<tr>
										<th>Rank</th>
										<th class="hide550"><></th>
										<th>Player</th>
										<th>Team</th>
										<th class="hide550">LP</th>
										<!-- <th class="hide550">Win</th>
										<th class="hide850">Loss</th> -->
										<th>Lastgame</th>
									</tr>
							</thead>
							<tbody>
			';
			$current_league = "challenger_1";
			foreach($players as $player){
				
				// if the current player is in a different division, end the previous division and start the new one
				if( $player->division !== $current_league ){
					$division_number = substr( $player->division , -1);
					if($division_number === 1) $division_number = 'I';
					if($division_number === 2) $division_number = 'II';
					if($division_number === 3) $division_number = 'III';
					if($division_number === 4) $division_number = 'IV';
					if($division_number === 5) $division_number = 'V';
					$html .= '
							</tbody>
							</table>
						</div>
					</div>
				</div>
				<div class="style_abstand"></div>
				<div class="division_ranking">
					<div class="division">
						<div class="division_icon">
							<div class="division_icon_content">
								<img src="'.plugins_url('/assets/images/toplist_medals/' , __FILE__ ).$player->division.'.png" alt="Division I">
								<span class="division_name">'.ucfirst( substr( $player->division, 0, -2 ) ).'</span>
								<span class="division_number">'.$division_number.'</span>
							</div>
						</div>
						<div class="division_content">
							<table>
								<thead>
									<tr>
										<th>Rank</th>
										<th class="hide550"><></th>
										<th>Player</th>
										<th>Team</th>
										<th class="hide550">LP</th>
										<!-- 
										<th class="hide550">Win</th>
										<th class="hide850">Loss</th>
										-->
										<th>Lastgame</th>
									</tr>
							</thead>
							<tbody>
					';
					$current_league = $player->division; 
				}
				$date = new DateTime();
				$date->setTimestamp( $player->lastgame );	
				$team = '';

				$team = get_teamarray_by_teamid( $player->teamidentity );
				
				/*
									<td>'.$player['wins'].'</td>
					<td>'.$player['losses'].'</td>
				*/
				
				$html .= '
				<tr>
					<td>'.$player->rank.'</td>
					<td>'.$player->lastrank.'</td>
					<td><b><a href="'.site_url().'/poros-player-profile/?lkid='.$player->lkid.'">'.$player->player.'</a></b></td>
					<td><b><a href="'.$team->teampage.'">'.$team->name.'</a></b></td>
					<td>'.$player->leaguepoints.'</td>

					<td>'.getLastGame( $player->lastgame )/*$date->format('d.m.Y H:i:s')*/.'</td></tr>';
			}
			$html .= "</table>";
		}
		return $html;
	}
	catch( PDOException $ex ) {
		return 'Error: '.$ex->getMessage();
	}
	
}
function shortcode_lol_player_profile($atts){
	$atts = shortcode_atts(
		array(
			'lkid' => '',
		), $atts);
	if($atts['lkid'] == ''){
		$lkid = get_query_var( 'lkid', 1 ); 
	} else {
		$lkid = $atts['lkid'];
	}
	ouput_lol_player_profile($lkid);
}
function shortcode_lol_match_details($atts){
	$atts = shortcode_atts(
		array(
			'matchid' => '',
		), $atts);
	if($atts['matchid'] == ''){
		$matchid = get_query_var( 'matchid', 1 ); 
	} else {
		$matchid = $atts['matchid'];
	}
	echo display_match_details($matchid);
}
function ouput_lol_player_profile($lkid){
	global $wpdb;
	// get previous aliases
	$sql = "SELECT DISTINCT name FROM loldata_players2tournament_matches WHERE matched_lkid = $lkid";
	$query = $wpdb->prepare($sql);
	$aliases = $wpdb->get_results($query);
	
	$alias_string = "Also played under these names:</br>";
	foreach($aliases as $alias){
		// append each alias on a new line, beginning with the explanation text
		$alias_string .= $alias['name'].'</br>';
	}
	
	// select the column for the player
	$sql = "SELECT player, division, teamidentity, lkid, lastgame_champion FROM loldata_players WHERE lkid = '$lkid' LIMIT 1";
	$query =  $wpdb->prepare( $sql );
	$players = $wpdb->get_col( $query , 0 );
	foreach($players as $player){
		
		$teamid = $player['teamidentity'];
		$sql = "SELECT name, teampage FROM teams WHERE teamidentity = '$teamid' LIMIT 1";
		$query = $wpdb->prepare( $sql );
		$teams = $wpdb->get_col( $query , 0 );
		foreach ($teams as $team){
			$team_name = $team['name'];
			$team_page = $team['teampage'];
		}
		$champion_key = get_champion_key($player['lastgame_champion']);
		$version = '6.4.2';
		$champion_icon = '<img style="min-width:25px; height: 25px !important;" width="25px" height="25px" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/champion/'.$champion_key.'.png">';
		
		echo '
		<div style="overflow:hidden">
			<img style="float:left; height:auto;" src="/wordpress/wp-content/themes/gamerbase/img/toplist_medals/'.$player['division'].'.png">
			<div style="float:left; display:block; padding-left:10px;">
				<h3>
					<a href="http://www.lolking.net/summoner/euw/'.$player['lkid'].'">
					'.$player['player'].'
					</a>
				</h3>
				'.$alias_string.'
				Team: ';
				if($team_page != '') {
					echo '<b><a href="'.$team_page.'">'.$team_name.'</a></b>';
				} else {
					echo $team_name;
				}
				echo '</br>
				Letzter Champion: '.$champion_icon.'
			</div>
		</div';
		
		// display competitive matches of the player
		echo '
		<div>
		<table>
			<tr>
				<th>When<th>
				<th>Event</th>
				<th>Phase</th>
				<th>Team</th>
				<th>Opponent</th>
				<th>Champion</th>
				<th>Result</th>
			</tr>
		';
		$lkid = $player['lkid'];
		$sql = "
		SELECT zwt.matchid, zwt.champion_id, zwt.teamid, main.datetime, main.winner, main.loser, main.winner_teamside, main.event, main.phase
		FROM loldata_players2tournament_matches zwt 
		LEFT JOIN loldata_tournament_matches main ON zwt.matchid = main.match_identity 
		WHERE zwt.matched_lkid = '$lkid' 
		ORDER BY main.datetime DESC";
		$query = $wpdb->prepare( $sql );
		
		$matches = $wpdb->get_results( $query );
		
		foreach($matches as $match){
			$champion_key = get_champion_key($match['champion_id']);
			$version = '6.4.2';
			$champion_icon = '<img style="height: 25px !important;" height="25px" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/champion/'.$champion_key.'.png">';
			
			if($match['winner_teamside'] == $match['teamid']){
				$opponent = $match['loser'];
				$home_team = $match['winner'];
				$outcome = '<p style="color:green">Sieg</p>';
			} else {
				$opponent = $match['winner'];
				$home_team = $match['loser'];
				$outcome = '<p style="color:red">Niederlage</p>';
			}
			$opponent = get_teamarray_by_teamid($opponent);
			$home_team = get_teamarray_by_teamid($home_team);
			
			
			
			// skip mixed matches
			if($match['event'] != 'Mixed'){
				echo '
				
					<tr>
						<td>'.date("j. F Y - H:i",$match['datetime']+7200).'</td>
						
						<td>'.$match['event'].'</td>
						<td><a href="http://www.gamerbase.ch/poros-match-details/?matchid='.$match['matchid'].'">';
						if($match['phase'] != ''){
							echo $match['phase'];
						} else {
							echo 'Phase';
						}
						echo '</a></td>
						<td><a href="'.$home_team['teampage'].'">'.$home_team['name'].'</a>
						<td><a href="'.$opponent['teampage'].'">'.$opponent['name'].'</a></td>
						<td>'.$champion_icon.'</td><td>'.$outcome.'</td>
					</tr>
			
					';
			}
		}
		echo '</table>';
	}
}

// sanitized 15.01.2017
function get_teamarray_by_teamid( $teamid ){
	global $wpdb;
	$sql = "SELECT name, teampage FROM loldata_teams WHERE teamidentity = '$teamid' LIMIT 1";
	$query = $wpdb->prepare( $sql );
	$teams = $wpdb->get_col( $query );
	foreach ($teams as $team){
		return array('name' => $team['name'], 'teampage' => $team['teampage']);
	}
}

// sanitized 15.01.2017
function output_lol_team_history($atts){
	global $wpdb;
	$atts = shortcode_atts(
	array('teamid' => 0), $atts);
	$sql = "
	SELECT winner, loser, datetime, json,riot_json, winner_teamside, match_identity, event, phase, vod
	FROM tournament_matches WHERE event != 'mixed' AND (winner = %s OR loser = %s) ORDER BY datetime DESC";
	$matches = $wpdb->get_results( $wpdb->prepare( $sql , $atts['teamid'] ));
	$html .= display_lol_matches($matches, $db);
	$html .= '</table>';
	return $html;
}
function display_lol_matches($matches, $db){
	
	foreach ($matches as $match) {
		// determine names of teams
		$sql = 'SELECT name, teampage FROM teams WHERE teamidentity = :teamidentity';
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':teamidentity' => $match['winner']));
		$winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$winner_page = '';
		foreach($winners as $winner){
			$winner_team = $winner['name'];
			$winner_page = $winner['teampage'];
		}
		
		$sql = 'SELECT name, teampage FROM teams WHERE teamidentity = :teamidentity';
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':teamidentity' => $match['loser']));
		$losers = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$loser_page = '';
		foreach($losers as $loser){
			$loser_team = $loser['name'];
			$loser_page = $loser['teampage'];
		}

		// get match information for the header
		$datetime = date("j. F Y - H:i",$match['datetime']+7200);
		
		$date = date("d.m.",$match['datetime']+7200);
		$time = date("H:i",$match['datetime']+7200);
		$game = json_decode($match['json'],true);
		$riot_json = json_decode($match['riot_json']);
		$event = $match['event'];
		$phase = $match['phase'];
		$mode = $riot_json->matchMode;
		
		if($mode == 'ARAM' || $mode == 'KINGPORO'){
			continue;
		}
		
		$html .= 
		'<table class="match-ticker-match">';
		// displayer event info header
		$html .= '
		
		<tr>
			<td style="text-transform:none; color:black; font-size:16px; font-weight:bold; width:45%; text-align:left">'.$datetime.'</td>
			<td style="width:10%"></td>
			<td style="width:45%; color:black; font-size:16px; font-weight:bold; text-align:right">'.$event.'</td>
		</tr>
		<tr>
			<td colspan="3" style="color:#C12940; font-size:14px; font-weight:bold; text-align:center">
				'.$phase.'
			</td>
		</tr>
		';
		if($match['vod'] != ''){
			$html .= '<tr><td colspan="3">
			<div class="videoContainer">
				<iframe width="500" height="281" src="https://www.youtube.com/embed/'.$match['vod'].'?feature=oembed" frameborder="0" allowfullscreen=""></iframe>
			</div>
			</td></tr>';
		}
		
		$bans_0 = ($riot_json->teams[0]->bans);
		$bans_1 = ($riot_json->teams[1]->bans);
		
		
		$winner_onclick = '';
		$loser_onclick = '';
		
		if($winner_page != '') $winner_onclick .= 'style="cursor: pointer; text-decoration:underline" onClick="location.href=\''.$winner_page.'\'"';
		if($loser_page != '') $loser_onclick .= 'style="cursor: pointer; text-decoration:underline" onClick="location.href=\''.$loser_page.'\'"';
		
		$html .= '
						<tr style="font-size: 24px; ">
							<td style="width:45%;text-align:right; font-weight:bold">
								<img style="float:left;" src="http://matchhistory.na.leagueoflegends.com/assets/1.0.10/css/resources/images/normal/scoreboardicon_gem_100.png" />
				';
					if($match['winner_teamside'] == 1){
						$html .= '<span style="color:#bea152; font-size: 15px;">Victory</span></br>';
						$html .= '<span  '.$winner_onclick.'>'.$winner_team.'</span>';
					} else {
						$html .= '<span style="color:#a65f46; font-size: 15px;">Defeat</span></br>';
						$html .= '<span '.$loser_onclick.'>'.$loser_team.'</span>';
					}
					$html .= 		
							'</td>
							<td style="width:10%; text-align:center; vertical-align:middle; color:blue; font-weight:bold">
								<span>VS</span>
							</td>
							<td style="width:45%; text-align:left; font-weight:bold">
							<img style="float:right;" src="http://matchhistory.na.leagueoflegends.com/assets/1.0.10/css/resources/images/normal/scoreboardicon_gem_200.png" />
						';
					if($match['winner_teamside'] == 2){
						$html .= '<span style="color:#bea152; font-size: 15px;">Victory</span></br>';
						$html .= '<span '.$winner_onclick.'>'.$winner_team.'</span>';
					} else {
						$html .= '<span style="color:#a65f46; font-size: 15px;">Defeat</span></br>';
						$html .= '<span '.$loser_onclick.'>'.$loser_team.'</span>';
					}
					
					$html .= '
							
							</td>
						</tr>
						<tr>
							<td colspan="3" style="text-align:center; font-size: 14px;">
								<a style="font-weight:bold; text-decoration:underline;" href="http://www.gamerbase.ch/poros-match-details/?matchid='.$match['match_identity'].'">
									Match Details
								</a>
							</td>
					</tr>

			</table></br></br>
			';

		$counter = 0;

	$time_end = microtime(true);

	//dividing with 60 will give the execution time in minutes other wise seconds
	$execution_time = ($time_end - $time_start);

	//execution time of the script
	// $html .= '<b>Total Execution Time:</b> '.$execution_time.' Secs';
	
	}
	

return $html;
}
function get_summoner_spell_key($spellId){
	// REPLACE DB CONNECTION
	$sql = "
	SELECT keyname FROM summoner_spells WHERE id = '$spellId'";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$spells = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($spells as $spell){
		return $spell['keyname'];
	}
}
function get_champion_key($championId){
	// REPLACE DB CONNECTION
	$sql = "SELECT champion_key FROM champions WHERE id = '$championId'";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$champions = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($champions as $champion){
		return $champion['champion_key'];
	}
}
function get_item_name($itemId){
	// REPLACE DB CONNECTION
	$sql = "
	SELECT name FROM items WHERE id = '$itemId'";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($items as $item){
		return $item['name'];
	}
}
function display_lol_match_players($players, $riot_json, $matchid, $teamid){
	$position_counter = 0;
	
	// get the link to the players profiles
	// REPLACE DB CONNECTION
	$sql = "SELECT matched_lkid, position FROM players2tournament_matches WHERE matchid = '$matchid' and teamid = '$teamid'";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$playerlinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	foreach($playerlinks as $playerlink){
		$position = $playerlink['position'];
		$lkid = $playerlink['matched_lkid'];
		$playerhrefs[$position] = 'http://www.gamerbase.ch/poros-player-profile/?lkid='.$lkid;
		$sql = "SELECT division FROM players WHERE lkid = '$lkid'";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($divisions as $division){
			$playerdivisions[$position] = $division['division'];
		}
	}
		
	// looping over all 5 players
	foreach($players as $player){	
		// determine the patch version for ddragon calls
		// default to newest version	
		$version = '6.10.1';
		
		if($riot_json->matchVersion == '6.10.144.693'){
			$version = '6.10.1';
		}
			
		if($riot_json->matchVersion == '6.7.140.3288'){
			$version = '6.7.1';
		}
			
		if($riot_json->matchVersion == '5.11.0.279'){
			$version = '5.11.1';
		}
		
		if($riot_json->matchVersion == '5.15.0.336'){
			$version = '5.15.1';
		}
		
		if($riot_json->matchVersion == '5.16.0.344'){
			$version = '5.16.1';
		}
		if($riot_json->matchVersion == '5.17.0.326'){
			$version = '5.17.1';
		}

		// get the champion icon
		$champion_key = get_champion_key($player['championId']);
		$champion_icon = '<img style="height: 30px" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/champion/'.$champion_key.'.png">';
			
		// loop over all players in the riot json, because we need to identify the champion id that corresponds to the player we already know about (riot_json isnt sorted the same way as op.gg)
		foreach($riot_json->participants as $participant){
			if($player['championId'] == $participant->championId){
				// summoner spells
				$player_spell1Id = $participant->spell1Id;
				$player_spell2Id = $participant->spell2Id;
				$summoner_spell_icon_1 = '<img style="height: 30px !important" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/spell/'.get_summoner_spell_key($player_spell1Id).'.png">';
				$summoner_spell_icon_2 = '<img style="height: 30px !important" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/spell/'.get_summoner_spell_key($player_spell2Id).'.png">';
					
				// kda 
				$kills = $participant->stats->kills;
				$deaths = $participant->stats->deaths;
				$assists = $participant->stats->assists;
					
				// level
				$level = $participant->stats->champLevel;
				
				// gold
				$gold = round(($participant->stats->goldEarned)/1000,1);
				
				// items
				$item0 = '';
				if($participant->stats->item0 != 0) $item0 = '<img title="'.get_item_name($participant->stats->item0).'" style="height: 30px !important" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/item/'.$participant->stats->item0.'.png" >';
				$item1 = '';
				if($participant->stats->item1 != 0) $item1 = '<img title="'.get_item_name($participant->stats->item1).'" style="height: 30px !important" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/item/'.$participant->stats->item1.'.png" >';
				$item2 = '';
				if($participant->stats->item2 != 0) $item2 = '<img title="'.get_item_name($participant->stats->item2).'" style="height: 30px !important" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/item/'.$participant->stats->item2.'.png" >';
				$item3 = '';
				if($participant->stats->item3 != 0) $item3 = '<img title="'.get_item_name($participant->stats->item3).'" style="height: 30px !important" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/item/'.$participant->stats->item3.'.png" >';
				$item4 = '';
				if($participant->stats->item4 != 0) $item4 = '<img title="'.get_item_name($participant->stats->item4).'" style="height: 30px !important" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/item/'.$participant->stats->item4.'.png" >';
				$item5 = '';
				if($participant->stats->item5 != 0) $item5 = '<img title="'.get_item_name($participant->stats->item5).'" style="height: 30px !important" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/item/'.$participant->stats->item5.'.png" >';
				$item6 = '';
				if($participant->stats->item6 != 0) $item6 = '<img title="'.get_item_name($participant->stats->item6).'" style="height: 30px !important" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/item/'.$participant->stats->item6.'.png" >';
				$item7 = '';
				if($participant->stats->item7 != 0) $item7 = '<img title="'.get_item_name($participant->stats->item7).'" style="height: 30px !important" src="http://ddragon.leagueoflegends.com/cdn/'.$version.'/img/item/'.$participant->stats->item7.'.png" >';

				
				/* echo '<pre>';
				print_r($participant);
				echo '</pre>'; */							
				} 
			}  			
			$html .= '
			
		
			
			<tr class="match-details-player-row">
					<td>
						'.$champion_icon.'
					</td>
					<td>
						'.$level.'
					</td>
					<td>
						<p class="ticker_inline_text" >';
						if($playerdivisions[$position_counter] != ''){
							$html .= '<img style="float:left; height:40px;"  title="'.$playerdivisions[$position_counter].'" src="http://wwww.gamerbase.ch/wordpress/wp-content/themes/gamerbase/img/toplist_medals/'.$playerdivisions[$position_counter].'.png">';
						}
						if(isset($playerhrefs[$position_counter])){
							$html .= '<b><a href="'.$playerhrefs[$position_counter].'">'.$player['name'].'</a></b>';
						} else {
							$html .= $player['name'];
						}

						$html .= '
						</p>						
					</td>
					<td>
						'.$kills.'/'.$deaths.'/'.$assists.'
					</td>
					<td>
						'.$summoner_spell_icon_1.$summoner_spell_icon_2.'
					</td>
					<td>
						'.$item0.$item1.$item2.$item3.$item4.$item5.$item6.$item7.'
					</td>
					<td>
						'.$gold.'k
					</td>
				</tr>';
			$position_counter++;
		} 
		
	return $html;
}
function output_vod_ticker(){
	// REPLACE DB CONNECTION
	$sql = "
	SELECT winner,loser, datetime, json,riot_json, winner_teamside, match_identity, event, phase, vod
	FROM tournament_matches WHERE event != 'mixed' AND event != '' AND vod != '' ORDER BY datetime DESC";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$html .= display_lol_matches($matches,$db);
	$html .= '</table>';
	return $html;
}
function output_match_ticker(){
	// start counting time
	$time_start = microtime(true); 
	
	// determine whether to filter or not
		$atts = shortcode_atts(
		array(
			'type' => '',
		), $atts);
	if($atts['type'] == ''){
		$type = get_query_var( 'type', 1 ); 
	} else {
		$type = $atts['type'];
	}
	
	// REPLACE DB CONNECTION
	if($type == 'noscrims'){
		$sql = "
		SELECT winner,loser, datetime, json, winner_teamside, match_identity, event, phase, vod
		FROM tournament_matches WHERE event != 'mixed' AND event != '' AND event != 'scrim' ORDER BY datetime DESC LIMIT 36";		
	} elseif($type == 'eevent2016'){ 
		$sql = "
		SELECT winner,loser, datetime, json, winner_teamside, match_identity, event, phase, vod
		FROM tournament_matches WHERE event = 'Eevent 2016 LoL Turnier' ORDER BY datetime DESC";	
	} elseif($type == 'smp1'){ 
		$sql = "
		SELECT winner,loser, datetime, json, winner_teamside, match_identity, event, phase, vod
		FROM tournament_matches WHERE event = 'SwissSMP.ch LoL Turnier #1' ORDER BY datetime DESC";	
	} elseif($type == 'lal8'){ 
		$sql = "
		SELECT winner,loser, datetime, json, winner_teamside, match_identity, event, phase, vod
		FROM tournament_matches WHERE event = 'Lock and Load #8' ORDER BY datetime DESC";	
	} elseif($type == 'netgame20'){ 
		$sql = "
		SELECT winner,loser, datetime, json, winner_teamside, match_identity, event, phase, vod
		FROM tournament_matches WHERE event = '20th Netgame Convention' ORDER BY datetime DESC";	
	}
	elseif($type == 'switzerlan2015'){ 
		$sql = "
		SELECT winner,loser, datetime, json, winner_teamside, match_identity, event, phase, vod
		FROM tournament_matches WHERE event = 'Switzerlan 2015' ORDER BY datetime DESC";	
	} elseif($type == 'qualifier2') {
		$sql = "
		SELECT winner,loser, datetime, json, winner_teamside, match_identity, event, phase, vod
		FROM tournament_matches WHERE event = 'Swiss Nationals 2015 Qualifier 2' ORDER BY datetime DESC";	
	} elseif($type == 'qualifier3') {
		$sql = "
		SELECT winner,loser, datetime, json, winner_teamside, match_identity, event, phase, vod
		FROM tournament_matches WHERE event = 'Swiss Nationals 2015 Qualifier 3' ORDER BY datetime DESC";	
	} elseif($type == 'qualifier4') {
		$sql = "
		SELECT winner,loser, datetime, json, winner_teamside, match_identity, event, phase, vod
		FROM tournament_matches WHERE event = 'Swiss Nationals 2015 Qualifier 4' ORDER BY datetime DESC";	
	} elseif($type == 'polylan26') {
		$sql = "
		SELECT winner,loser, datetime, json, winner_teamside, match_identity, event, phase, vod
		FROM tournament_matches WHERE event = 'PolyLAN 26' ORDER BY datetime DESC";	
	} else {
		$sql = "
		SELECT winner,loser, datetime, json, winner_teamside, match_identity, event, phase, vod
		FROM tournament_matches WHERE event != 'mixed' AND event != '' ORDER BY datetime DESC LIMIT 36";
	}

	$stmt = $db->prepare($sql);
	$stmt->execute();
	$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$html .= display_lol_matches($matches,$db);
	$html .= '</table>';
	
	$time_end = microtime(true);

	//dividing with 60 will give the execution time in minutes other wise seconds
	$execution_time = ($time_end - $time_start);

	//execution time of the script
	$html .= '<b>Total Execution Time:</b> '.$execution_time.' Secs';
	
	return $html;
}
function output_match_ticker_unsorted(){
	// REPLACE DB CONNECTION
	$sql = "
	SELECT winner,loser, datetime, json,riot_json, winner_teamside, match_identity, event, phase, vod
	FROM tournament_matches WHERE event = '' ORDER BY datetime DESC LIMIT 100";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$html .= display_lol_matches($matches,$db);
	$html .= '</table>';
	return $html;
}

?>