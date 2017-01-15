<?php
/*
Plugin Name: Gamerbase Legacy
Description: Contains all the Gamerbase Code that has not been cleaned up yet
Author:      freakpants - Christian Nyffenegger
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

@ini_set( 'upload_max_size' , '64M' );
@ini_set( 'post_max_size', '64M');
@ini_set( 'max_execution_time', '300' );

function my_scripts_enqueue() {
    wp_register_script( 'bootstrap-js', get_template_directory_uri() . '/js/bootstrap.min.js', array('jquery'), NULL, true );
    wp_register_style( 'bootstrap-css', get_template_directory_uri() . '/css/bootstrap.min.css', false, NULL, 'all' );
	
	wp_register_script( 'chart-js', get_template_directory_uri() . '/js/chart.bundle.js', array('jquery'), NULL, true );
	 wp_enqueue_script( 'chart-js' );

    wp_enqueue_script( 'bootstrap-js' );
    wp_enqueue_style( 'bootstrap-css' );
}
add_action( 'wp_enqueue_scripts', 'my_scripts_enqueue' );
date_default_timezone_set('Europe/Rome');

require('lol-functions.php');

// require('tournaments.php');

// helper function for put
function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data)));
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }


    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); 

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}

setlocale (LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');

add_shortcode( 'rocket_toplist', 'output_rocket_toplist' );
add_shortcode('bbpress_recent_replies_by_topic', 'custom_bbpress_recent_replies_by_topic');
add_shortcode( 'all_twitch_streams', 'list_all_twitch_streams' );
add_shortcode( 'twitch_streams', 'list_online_twitch_streams' );
add_shortcode( 'lanresults', 'output_lan_results' );
add_shortcode( 'tournaments', 'tournaments');
add_shortcode( 'switzerlan_tournaments', 'switzerlan_tournaments');
add_shortcode( 'update_all_social_data', 'update_all_social_data');
add_shortcode( 'overwatch_toplist', 'overwatch_toplist');
add_shortcode( 'promo_twitch_streams', 'promo_twitch_streams' );
add_shortcode( 'worldcup_nominees', 'worldcup_nominees' );
add_shortcode( 'steam_api', 'steam_api' );

function switzerlan_tournaments(){
	tournaments();
}

function worldcup_nominees(){
	$table = '
		<table>
			<tr>
				<th>Avatar</th>
				<th>Battletag</th>
				<th>Rank</th>
				<th>Level</th>
			</tr>
			';
	
	// REPLACE DB CONNECTION 
	$sql = "SELECT * FROM players WHERE world_cup = 1 ORDER BY rank DESC";
	$stmt = $db->prepare($sql);
    $stmt->execute();
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($results as $result){
		$link = '';
		$link = "https://playoverwatch.com/career/pc/eu/".str_replace("#","-",$result['battle_tag']);
		

		
		$table .= '
			<tr>
				<td><img width="64px" src="'.$result['profile_image'].'" /></td>
				<td><a href="'.$link.'">'.$result['battle_tag'].'</a></td>
				<td>'.$result['rank'].'</td>
				<td>'.$result['level'].'</td>
			</tr>';
	}
	$table .= '</table>';
	
	return $table;
		
/* return '
<table>
<tbody>
<tr style="height: 24px;">
<td style="height: 24px; text-align: center;"><strong>Name</strong></td>
<td style="height: 24px; text-align: center;"><strong>Facebook</strong></td>
<td style="height: 24px; text-align: center;"><strong>Twitch</strong></td>
<td style="height: 24px; text-align: center;"><strong>playOverwatch</strong></td>
<td style="height: 24px; text-align: center;"><strong>Role</strong></td>
</tr>
<tr style="height: 24px;">
<td style="height: 24px; text-align: center;">Tufan <em><strong>"HAXitoo#2238"</strong></em> Nergiz</td>
<td style="height: 24px; text-align: center;"><strong><a href="http://fb.com/HAXitoo">HAXitoo</a></strong></td>
<td style="height: 24px; text-align: center;"><strong><a href="https://www.twitch.tv/haxitoo">haxitoo</a></strong></td>
<td style="height: 24px; text-align: center;"><strong><a href="https://playoverwatch.com/de-de/career/pc/eu/HAXitoo-2238">HAXitoo-2238</a></strong></td>
<td style="height: 24px; text-align: center;">All (pref. Attack)</td>
</tr>
<tr style="height: 24px;">
<td style="height: 24px; text-align: center;">Marco<em><strong>"Mimi7#2729"</strong></em>Badertscher</td>
<td style="height: 24px; text-align: center;"><strong></strong></td>
<td style="height: 24px; text-align: center;"><strong></strong></td>
<td style="height: 24px; text-align: center;"><strong><a href="https://playoverwatch.com/de-de/career/pc/eu/Mimi7-2729">Mimi7#272</a></strong></td>
<td style="height: 24px; text-align: center;">Offense</td>
</tr>
<tr style="height: 24px;">
<td style="height: 24px; text-align: center;">Alex <em><strong>"R3M1X#21356"</strong></em> Badertscher</td>
<td style="height: 24px; text-align: center;"><strong></strong></td>
<td style="height: 24px; text-align: center;"><strong></strong></td>
<td style="height: 24px; text-align: center;"><strong><a href="https://playoverwatch.com/de-de/career/pc/eu/R3M1X-21356">R3M1X#21356</a></strong></td>
<td style="height: 24px; text-align: center;">Support</td>
</tr>
<tr style="height: 24px;">
<td style="height: 24px; text-align: center;">Cédric <em><strong>"Atlas#2592"</strong></em> Baumann</td>
<td style="height: 24px; text-align: center;"><strong></strong></td>
<td style="height: 24px; text-align: center;"><strong></strong></td>
<td style="height: 24px; text-align: center;"><strong><a href="https://playoverwatch.com/de-de/career/pc/eu/Atlas-2592">Atlas#2592</a></strong></td>
<td style="height: 24px; text-align: center;">All (pref. Tank)</td>
</tr>
<tr style="height: 24px;">
<td style="height: 24px; text-align: center;">Luca <em><strong>""Luux#21470""</strong></em> Locher</td>
<td style="height: 24px; text-align: center;"><strong></strong></td>
<td style="height: 24px; text-align: center;"><strong></strong></td>
<td style="height: 24px; text-align: center;"><strong><a href="https://playoverwatch.com/de-de/career/pc/eu/Luux-21470">Luux#21470</a></strong></td>
<td style="height: 24px; text-align: center;">Offense</td>
</tr>
</tbody>
</table>';
*/
}

// require get parameters
function add_query_vars_filter( $vars ){
  $vars[0] = "lkid";
  $vars[1] = "type";
  $vars[2] = "matchid";
  $vars[3] = "team";
  return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

// lan results
function output_lan_results($atts){
	$atts = shortcode_atts(
		array(
			'display' => '',
		), $atts);
		
	
	
	// REPLACE DB CONNECTION
	
	if($atts['display'] == 'full'){
		$sql = "SELECT * FROM results WHERE 1 ORDER BY timestamp DESC";
	}
	else {
		$sql = "SELECT * FROM results WHERE 1 ORDER BY timestamp DESC LIMIT 5 ";
	}

	
	$stmt = $db->prepare($sql);
    $stmt->execute();
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($results as $result){
		// $html .= $result['tournament'].' - '.$result['round'].'</br>';
		// $html .= '<b><font size="2.0em" color="green">'.$result['winner'].'</font> VS <font size="2.0em"  color="red">'.$result['loser'].'</font></b></br><hr>';
		
		$html .= '
		<div class="lan_trmt">
			<div class="lan_head">
				<span class="lan_head_trmt">'.$result['tournament'].'</span> -
				<span class="lan_head_round">'.$result['round'].'</span>
			</div>
			<div class="lan_datetime">'.strftime("%A %H:%M",$result['timestamp']+3600).'</div>
			<div class="lan_body">
				<span class="lan_body_winner">'.$result['winner'].'</span>
				<span class="lan_body_winner_result">'.$result['winnerscore'].'</span>
				<span class="lan_body_vs">VS</span>
				<span class="lan_body_loser_result">'.$result['loserscore'].'</span>
				<span class="lan_body_loser">'.$result['loser'].'</span>
			</div>
		</div>
	';	
	}
	if($atts['display'] != 'full'){
		$html .= '
	
		<div class="lan_trmt">
			<div class="lan_head">
				<b><a href="http://www.gamerbase.ch/lock-and-load-8-resultate/">Alle Resultate</a></b>
			</div>
		</div>
		';
	}
	return $html; 
} 

function get_teamlink_by_battle_tag($battle_tag){
	$team_id = 0;
	$team_name = '';
	$user = get_users(array('meta_key' => 'battletag', 'meta_value' => $battle_tag))[0];
	// REPLACE DB CONNECTION
	$user_id = $user->ID;
	$sql = "SELECT team_id FROM team_members WHERE wordpress_id = $user_id";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$team_id = $stmt->fetch()[0];
	
	if($team_id > 0){
		$sql = "SELECT name FROM teams WHERE team_id = $team_id";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$team_name = $stmt->fetch()[0];
	}
	return '<a href="/overwatch-toplist/?team='.$team_id.'">'.$team_name.'</a>';
}

function get_teamname_by_battle_tag($battle_tag){
	$team_id = 0;
	$team_name = '';
	$user = get_users(array('meta_key' => 'battletag', 'meta_value' => $battle_tag))[0];
	// REPLACE DB CONNECTION
	$user_id = $user->ID;
	$sql = "SELECT team_id FROM team_members WHERE wordpress_id = $user_id";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$team_id = $stmt->fetch()[0];
	
	if($team_id > 0){
		$sql = "SELECT name FROM teams WHERE team_id = $team_id";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$team_name = $stmt->fetch()[0];
	}
	return $team_name;
}

function overwatch_toplist(){
	// determine if there are filters
	$atts = shortcode_atts(
		array(
			'team' => 0,
		), $atts);
	if($atts['team'] == ''){
		$filter_team = get_query_var( 'team', 0 ); 
	} else {
		$filter_team = $atts['team'];
	}
	
	$html .= '

<script src="http://www.gamerbase.ch/wordpress/jquery.tablesorter.js" type="text/javascript"></script>
<script type="text/javascript">

	function showRanked(){
		document.getElementById("myTable").style.display = "table";
		document.getElementById("unranked").style.display = "none";
		document.getElementById("ranked_button").style.backgroundColor = "red";
		document.getElementById("unranked_button").style.backgroundColor = "#029bff";
	}
	
	function showUnRanked(){
		document.getElementById("unranked").style.display = "table";
		document.getElementById("myTable").style.display = "none";
		document.getElementById("unranked_button").style.backgroundColor = "red";
		document.getElementById("ranked_button").style.backgroundColor = "#029bff";
	}

	jQuery(document).ready(function() { 
		jQuery("#myTable").tablesorter(); 
		jQuery("#unranked").tablesorter(); 
	} );
	
	jQuery(document).ready(function() { 
		jQuery(".extend").click(function() {

			extended_id = this.id + "_extended";	
			extended_element = document.getElementById(extended_id);
			
			if(extended_element == null){
				console.log("not found");
				var url = "http://www.gamerbase.ch/wordpress/wp-content/themes/gamerbase/score_history.php?battle_tag=" + encodeURIComponent(this.id);
				console.log(url);
				jQuery.ajax({
				url: url,
				context: this,
				}).done(function(data){
					jQuery(this).after(data);
				});
			} else {
				console.log("found" + extended_element);
				extended_element.remove();
			}
		});
	});
	
</script>';
	
	$html .= '
	<span>
		The table is sortable by every column by clicking the respective column. Hold Shift to sort by multiple columns at once.
	</span>
		<div class="clearfix both" />
	<input id="ranked_button" onclick="showRanked();" style="float: left; background-color:red;" type="submit" value="Competitive Mode">
	<input id="unranked_button" onclick="showUnRanked();" style="float: left; " type="submit" value="Quick Play">
	<table id="myTable" style="float:left">
		<thead>
			<tr>
				<th>#</th>
				<th></th>
				<th>Level</th>
				<th>Battletag</th>
				<th>Rating</th>
				<th>Wins</th>
				<th>Losses</th>
				<th>Total</th>
				<th>Win %</th>
				<th>Team/Clan</th>
				<th>Last Change</th>
			</tr>
		</thead>
		<tbody>
			';
	
	// REPLACE DB CONNECTION
	
	$where = '';
	if($filter_team > 0){
		$i = 0;
		// REPLACE DB CONNECTION
		$sql = "select wordpress_id from team_members WHERE team_id = $filter_team";
		$stmt = $tournament_db->prepare($sql);
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($results as $team_member){
			if($i == 0){
				$where .= " battle_tag = '".get_user_meta($team_member['wordpress_id'], 'battletag', $single)[0]."'";
			} else {
				$where .= " OR battle_tag = '".get_user_meta($team_member['wordpress_id'], 'battletag', $single)[0]."'";
			}
			$i++;
		}
	}
	if($where != ''){
		$where = " AND (".$where.")";
	}
	
	$sql = "
		SELECT rank, rank_image, battle_tag, profile_image, wins, losses, last_update_time, last_change_time, level, world_cup 
		FROM players WHERE rank > 100 ".$where." ORDER BY rank DESC"; 

	$stmt = $overwatch_db->prepare($sql);
	
	$stmt->execute();
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$i = 1;
	$highest_update_time = 0;
	foreach ($results as $player){		
		$team_link = get_teamlink_by_battle_tag($player['battle_tag']);
		
		if($player['last_update_time'] > $highest_update_time) $highest_update_time = $player['last_update_time'];
		$total = $player['wins'] + $player['losses'];
		$win_percent = round(100 / $total * $player['wins'], 2);
		
		// select historic data
		$battle_tag = $player['battle_tag'];
		$sql = "SELECT rank, wins, losses, time FROM players_historic WHERE battle_tag = '$battle_tag' AND rank > 0 ORDER BY time DESC";
		$stmt = $overwatch_db->prepare($sql);
		$stmt->execute();
		$historic_datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$diff = "";
		$previous_rank = "";
		if(count($historic_datasets) > 1){
			$previous_rank = $historic_datasets[1]['rank'];
			// compare current stats with previous stats
			if($player['rank'] > $previous_rank){
				$diff = 'positive';
			}
			if($player['rank'] < $previous_rank){
				$diff = 'negative';
			}
		}
		
		if($win_percent >= 50){
			$win_percent = '<font color="green">'.$win_percent.'</font>';
		} else {
			$win_percent = '<font color="red">'.$win_percent.'</font>';
		}
		
		$last_change_time = '';
		if($player['last_change_time'] > 0){
			$last_change_time = date("r", $player['last_change_time']);
			
			$difference = time() - $player['last_change_time'];
			
			if($difference > 60){
				$difference = $difference.'s ';
			}
			if($difference > 59 && $difference < 3600 ){
				$difference = round($difference / 60).'m ';
			}
			if($difference > 3599 && $difference < 86400 ){
				$difference = round($difference / 3600).'h ';
			}
			if($difference > 86399 && $difference < 604800){
				$difference = round($difference / 86400).'d ';
			}
			if($difference > 604699){
				$difference = round($difference / 604800).'w ';
			}
			
			$last_change_time = '
			<span class="pointer" data-toggle="tooltip" title="'.date("r", $player['last_change_time']).'" >'
				.$difference.' ago
			</span>';
		}
		
		$nominated = '';
		if($player['world_cup'] == 1){
			// $nominated = 'style="background-color:orange"';
		}
		
	
		$rank_image = '<img src="'.get_template_directory_uri().'/img/overwatch_medals/bronze.png" />';
		if($player['rank'] >= 1500) $rank_image = '<img src="'.get_template_directory_uri().'/img/overwatch_medals/silver.png" />';  
		if($player['rank'] >= 2000) $rank_image = '<img src="'.get_template_directory_uri().'/img/overwatch_medals/gold.png" />';  
		if($player['rank'] >= 2500) $rank_image = '<img src="'.get_template_directory_uri().'/img/overwatch_medals/platinum.png" />';
		if($player['rank'] >= 3000) $rank_image = '<img src="'.get_template_directory_uri().'/img/overwatch_medals/diamond.png" />';
		if($player['rank'] >= 3500) $rank_image = '<img width="64px" src="'.get_template_directory_uri().'/img/overwatch_medals/master.png" />';
		if($player['rank'] >= 4000) $rank_image = '<img width="64px" src="'.get_template_directory_uri().'/img/overwatch_medals/grandmaster.png" />';
		
		$html .= '
		<tr '.$nominated.' id="'.$player['battle_tag'].'" class="extend">
			<td>'.$i.'.</td>
			<td><img width="64px" src="'.$player['profile_image'].'" /></td>
			<td>'.$player['level'].'</td>
			<td><a href="http://playoverwatch.com/en-en/career/pc/eu/'.str_replace("#","-",$player['battle_tag']).'">'.$player['battle_tag'].'</a></td>
		';
		if($diff == 'negative'){
			$html .= '
			<td class="pointer" data-toggle="tooltip" title="previous rating was '.$previous_rank.'">
				'.$rank_image.'
				<i style="color:red; vertical-align: middle;" class="icon-circle-arrow-down fa-2x"></i>
				<font color="red">
					<b>'.$player['rank'].'</b>
				</font>
			</td>';
		} else if ($diff == 'positive'){
			$html .= '
			<td class="pointer" data-toggle="tooltip" title="previous rating was '.$previous_rank.'">
				'.$rank_image.'
				<i style="color:green; vertical-align: middle;" class="icon-circle-arrow-up fa-2x"></i>
				<font color="green">
					<b>'.$player['rank'].'</b>
				</font>
			</td>';
		} else {
			$html .= '<td>'.$rank_image.$player['rank'].'</td>';
		}
		$html .= '
			<td>'.$player['wins'].'</td>
			<td>'.$player['losses'].'</td>
			<td>'.$total.'</td>
			<td><b>'.$win_percent.'</b></td>
			<td>'.$team_link.'</td>
			<td>'.$last_change_time.'</td>
		</tr>';
		$i++;
	}
	$html .= '</tbody></table>';
	// unranked table
	$html .= '
	<div class="clearfix both" ></div>
	<table id="unranked" style="display:none">
		<thead>
			<tr>
				<th></th>
				<th>Level</th>
				<th>Battletag</th>
				<th>Wins</th>
				<th>Losses</th>
				<th>Total</th>
				<th>Win %</th>
				<th>Team/Clan</th>
				<th>Last Change</th>
			</tr>
		</thead>
		<tbody>
			';
	$sql = "
		SELECT  battle_tag, profile_image, wins, losses, last_update_time, last_change_time, level 
		FROM players WHERE rank = 0 AND level > 0 ORDER BY level DESC"; 

	$stmt = $overwatch_db->prepare($sql);
	$stmt->execute();
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($results as $player){	
		$total = $player['wins'] + $player['losses'];
		$win_percent = round(100 / $total * $player['wins'], 2);
		if($win_percent >= 50){
			$win_percent = '<font color="green">'.$win_percent.'</font>';
		} else {
			$win_percent = '<font color="red">'.$win_percent.'</font>';
		}
		$html .= '
		<tr id="'.$player['battle_tag'].'">
			<td><img width="64px" src="'.$player['profile_image'].'" /></td>
			<td>'.$player['level'].'</td>
			<td><a href="http://playoverwatch.com/en-en/career/pc/eu/'.str_replace("#","-",$player['battle_tag']).'">'.$player['battle_tag'].'</a></td>
			<td>'.$player['wins'].'</td>
			<td>'.$player['losses'].'</td>
			<td>'.$total.'</td>
			<td><b>'.$win_percent.'</b></td>
			<td>'.$team_link.'</td>
			<td>'.$last_change_time.'</td>
		</tr>';
	}
	$html .= '</tbody></table>';
	$html .= '<span style="float:left">Last full passthrough: '.date("r",$highest_update_time).'</span>';
	return $html; 
}

function output_rocket_toplist(){

	
	$queues = array('ranked_duel','ranked_doubles','ranked_solo_standard','ranked_standard');
	
	$buttons = '
		<input id="duel_button" onclick="showDuel();" style="float: left;" type="submit" value="Duel (1v1)">
		<input id="doubles_button" onclick="showDoubles();" style="float: left;" type="submit" value="Doubles (2v2)">
		<input id="solo_button" onclick="showSolo();" style="float: left;" type="submit" value="Solo Standard (3v3)">
		<input id="standard_button" onclick="showStandard();" style="background: red; float: left;" type="submit" value="Standard (3v3)">';
		
	$javascript = '
		<script>
			function showDuel(){
				document.getElementById("ranked_duel").style.display = "table";
				document.getElementById("ranked_doubles").style.display = "none";
				document.getElementById("ranked_solo_standard").style.display = "none";
				document.getElementById("ranked_standard").style.display = "none";
				document.getElementById("duel_button").style.backgroundColor = "red";
				document.getElementById("doubles_button").style.backgroundColor = "#029bff";
				document.getElementById("solo_button").style.backgroundColor = "#029bff";
				document.getElementById("standard_button").style.backgroundColor = "#029bff";
			}
			function showDoubles(){
				document.getElementById("ranked_duel").style.display = "none";
				document.getElementById("ranked_doubles").style.display = "table";
				document.getElementById("ranked_solo_standard").style.display = "none";
				document.getElementById("ranked_standard").style.display = "none";
				document.getElementById("duel_button").style.backgroundColor = "#029bff";
				document.getElementById("doubles_button").style.backgroundColor = "red";
				document.getElementById("solo_button").style.backgroundColor = "#029bff";
				document.getElementById("standard_button").style.backgroundColor = "#029bff";
			}
			function showSolo(){
				document.getElementById("ranked_duel").style.display = "none";
				document.getElementById("ranked_doubles").style.display = "none";
				document.getElementById("ranked_solo_standard").style.display = "table";
				document.getElementById("ranked_standard").style.display = "none";
				document.getElementById("duel_button").style.backgroundColor = "#029bff";
				document.getElementById("doubles_button").style.backgroundColor = "#029bff";
				document.getElementById("solo_button").style.backgroundColor = "red";
				document.getElementById("standard_button").style.backgroundColor = "#029bff";
			}
			function showStandard(){
				document.getElementById("ranked_duel").style.display = "none";
				document.getElementById("ranked_doubles").style.display = "none";
				document.getElementById("ranked_solo_standard").style.display = "none";
				document.getElementById("ranked_standard").style.display = "table";
				document.getElementById("duel_button").style.backgroundColor = "#029bff";
				document.getElementById("doubles_button").style.backgroundColor = "#029bff";
				document.getElementById("solo_button").style.backgroundColor = "#029bff";
				document.getElementById("standard_button").style.backgroundColor = "red";
			}
		</script>';

	$html .= $javascript;	
	$html .= $buttons;
	// REPLACE DB CONNECTION
	
	foreach($queues as $queue){
		$display = 'none';
		switch($queue){
			case 'ranked_duel':
				$sql = "SELECT displayname, userid, platform, ranked_duel_rating, ranked_duel_division, ranked_duel_tier FROM players WHERE ranked_duel_rating > 0 ORDER BY ranked_duel_rating DESC";
				$title = "Ranked Duel (1v1)";
			break;
			case 'ranked_doubles':
				$sql = "SELECT displayname, userid, platform, ranked_doubles_rating, ranked_doubles_division, ranked_doubles_tier FROM players WHERE ranked_doubles_rating > 0 ORDER BY ranked_doubles_rating DESC";
				$title = "Ranked Doubles (2v2)";
			break;
			case 'ranked_solo_standard':
				$sql = "
				SELECT displayname, userid, platform, ranked_solo_standard_rating, ranked_solo_standard_division, ranked_solo_standard_tier 
				FROM players WHERE ranked_solo_standard_rating > 0 ORDER BY ranked_solo_standard_rating DESC";
				$title = "Ranked Solo Standard (3v3 Random Teams)";
			break;
			case 'ranked_standard':
				$display = 'table';
				$sql = "
				SELECT displayname, userid, platform, ranked_standard_rating, ranked_standard_division, ranked_standard_tier 
				FROM players WHERE ranked_standard_rating > 0 ORDER BY ranked_standard_rating DESC";
				$title = "Ranked Standard (3v3 Premade Teams)";
			break;
		}
	
		$html .= '
		<table id='.$queue.' style="display:'.$display.';" class="rocket_toplist">
			<tr>
				<td style="width:5%" >&nbsp;</th>
				<td style="width:20%" >&nbsp;</th>
				<td style="width:10%" >&nbsp;</th>
				<td style="width:15%" >&nbsp;</th>
				<td style="width:25%" >&nbsp;</th>
				<td style="width:25%" >&nbsp;</th>
			</tr>
			<tr><th style="text-align:center;" colspan="6">'.$title.'</th></tr>
			<tr>
				<th style="text-align:center;">Rank</th>
				<th style="text-align:center;">Player</th>
				<th style="text-align:center;">Rating</th>
				<th style="text-align:center;">Tier</th>
				<th style="text-align:center;">Division</th>
				<th style="text-align:center;">Platform</th>
			</tr>
		';

		$stmt = $rocket_db->prepare($sql);
		$stmt->execute();
		$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$rank = 0;
	
		foreach($players as $player){
			$rank++;
			
			switch($player['platform']){
				case 1:
					$platform = '<img alt="Steam" width="45px" src="/wordpress/wp-content/themes/gamerbase/img/steam.png">';
					$profilelink = 'http://steamcommunity.com/profiles/'.$player['userid'];
				break;
				case 2:
					$platform = '<img alt="Playstation 4" width="45px" src="/wordpress/wp-content/themes/gamerbase/img/ps4.png">';
					$profilelink = 'https://rocketleaguestats.com/profile/PS4/' . urlencode($player['userid']);
				break;
				case 3:
					$platform = '<img alt="Xbox 1" width="45px" src="/wordpress/wp-content/themes/gamerbase/img/x1.png">';
					$profilelink = 'https://rocketleaguestats.com/profile/XboxOne/' . urlencode($player['userid']);
				break;
			}
			
			switch($queue){
				case 'ranked_duel':
					// change tier into Name
					$tierId = $player['ranked_duel_tier'];
					$sql = "SELECT tierName FROM tiers WHERE tierID = $tierId LIMIT 1";
					$stmt = $rocket_db->prepare($sql);
					$stmt->execute();
					$tier_name = $stmt->fetch()[0];
					$html .= '
					<tr>
						<td style="text-align:center;">'.$rank.'.</td>
						<td style="text-align:center;">
							<a href="'.$profilelink.'">'.$player['displayname'].'</a>
						</td>
						<td style="text-align:center;">'.$player['ranked_duel_rating'].'</td>
						<td style="text-align:center;"><img alt="'.$tier_name.'" width="55px" src="/wordpress/wp-content/themes/gamerbase/img/rocket_league_tiers/'.$tierId.'.png"></br> '.$tier_name.'</td>
						<td style="text-align:center;">'.$player['ranked_duel_division'].'</td>
						<td style="text-align:center;">'.$platform.'</td>
					</tr>';
				break;
				case 'ranked_doubles':
					// change tier into Name
					$tierId = $player['ranked_doubles_tier'];
					$sql = "SELECT tierName FROM tiers WHERE tierID = $tierId LIMIT 1";
					$stmt = $rocket_db->prepare($sql);
					$stmt->execute();
					$tier_name = $stmt->fetch()[0];
					$html .= '
					<tr>
						<td style="text-align:center;">'.$rank.'.</td>
						<td style="text-align:center;">
							<a href="'.$profilelink.'">'.$player['displayname'].'</a>
						</td>
						<td style="text-align:center;">'.$player['ranked_doubles_rating'].'</td>
						<td style="text-align:center;"><img alt="'.$tier_name.'" width="55px" src="/wordpress/wp-content/themes/gamerbase/img/rocket_league_tiers/'.$tierId.'.png"></br> '.$tier_name.'</td>
						<td style="text-align:center;">'.$player['ranked_doubles_division'].'</td>
						<td style="text-align:center;">'.$platform.'</td>
					</tr>';
				break;				
				case 'ranked_solo_standard':
					// change tier into Name
					$tierId = $player['ranked_solo_standard_tier'];
					$sql = "SELECT tierName FROM tiers WHERE tierID = $tierId LIMIT 1";
					$stmt = $rocket_db->prepare($sql);
					$stmt->execute();
					$tier_name = $stmt->fetch()[0];
					$html .= '
					<tr>
						<td style="text-align:center;">'.$rank.'.</td>
						<td style="text-align:center;">
							<a href="'.$profilelink.'">'.$player['displayname'].'</a>
						</td>
						<td style="text-align:center;">'.$player['ranked_solo_standard_rating'].'</td>
						<td style="text-align:center;"><img alt="'.$tier_name.'" width="55px" src="/wordpress/wp-content/themes/gamerbase/img/rocket_league_tiers/'.$tierId.'.png"></br> '.$tier_name.'</td>
						<td style="text-align:center;">'.$player['ranked_solo_standard_division'].'</td>
						<td style="text-align:center;">'.$platform.'</td>
					</tr>';
				break;
				case 'ranked_standard':
					// change tier into Name
					$tierId = $player['ranked_standard_tier'];
					$tierName = tierId_to_tierName($tierId, $rocket_db);
					$html .= '
					<tr>
						<td style="text-align:center;">'.$rank.'.</td>
						<td style="text-align:center;">
							<a href="'.$profilelink.'">'.$player['displayname'].'</a>
						</td>
						<td style="text-align:center;">'.$player['ranked_standard_rating'].'</td>
						<td style="text-align:center;"><img alt="'.$tier_name.'" width="55px" src="/wordpress/wp-content/themes/gamerbase/img/rocket_league_tiers/'.$tierId.'.png"></br> '.$tierName.'</td>
						<td style="text-align:center;">'.$player['ranked_standard_division'].'</td>
						<td style="text-align:center;">'.$platform.'</td>
					</tr>';
				break;
			}
		}
		$html .= "</table>";
	}
	return $html;
}

function tierId_to_tierName($tierId, $rocket_db){
	$sql = "SELECT tierName FROM tiers WHERE tierID = $tierId LIMIT 1";
	$stmt = $rocket_db->prepare($sql);
	$stmt->execute();
	return $stmt->fetch()[0];
}

//*******************************************************************************
// Social Login: Action called before logging in the user
//*******************************************************************************

//This function will be called before Social Login logs the the user in
function oa_social_login_do_before_user_login ($user_data, $identity, $new_registration)
{
	//true for new and false for returning users
	if ($new_registration)
	{
		echo "I am a new user";
	}
	else
	{
		echo "I am a returning user";
	}

	//These are the fields from the WordPress database
	print_r($user_data);
	
	//This is the full social network profile of this user
	print_r($identity);
}
// add_action ('oa_social_login_action_before_user_login', 'oa_social_login_do_before_user_login', 10, 3);

// update steam identity into the wordpress db (if existing)
function update_steam_meta($oa_social_login_user_token, $identity_token, $wordpress_user_id){
	
	global $site_public_key, $site_private_key;
	
	echo 'updating steam meta</br></br>';

	//Your Site Settings
	$site_subdomain = 'poros';
 
	//API Access Domain
	$site_domain = $site_subdomain.'.api.oneall.com';
 
	//Connection Resource
	$resource_uri = 'https://'.$site_domain.'/users/'.$oa_social_login_user_token.'.json';
	
	// force sync with steam profile
	//Setup connection
	$url = 'https://'.$site_domain.'/identities/'.$identity_token.'/synchronize.json';
	$curl = curl_init();
	$data = array();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_USERPWD, $site_public_key . ":" . $site_private_key);
	curl_setopt($curl, CURLOPT_TIMEOUT, 15);
	curl_setopt($curl, CURLOPT_VERBOSE, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_FAILONERROR, 0);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
	curl_setopt($curl, CURLOPT_POSTFIELDS,http_build_query($data));
	
	// echo 'calling synch url '.$url.'</br>';
	$result = json_decode(curl_exec($curl));
	curl_close($curl);
	
	/* echo '<pre>';
	print_r($result);
	echo '</pre>'; */
 
	//Setup connection
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $resource_uri);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_USERPWD, $site_public_key . ":" . $site_private_key);
	curl_setopt($curl, CURLOPT_TIMEOUT, 15);
	curl_setopt($curl, CURLOPT_VERBOSE, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_FAILONERROR, 0);
 
	//Send request
	$result_json = json_decode(curl_exec($curl));
	curl_close($curl);

   
    // update profile fields for steam
   foreach($result_json->response->result->data->user->identities as $identity){
		if($identity->provider == 'steam'){
			update_user_meta ($wordpress_user_id, 'steam_profile', $identity->profileUrl);
			update_user_meta ($wordpress_user_id, 'steam_picture', $identity->pictureUrl);
			update_user_meta ($wordpress_user_id, 'steam_displayName', $identity->displayName);
			update_user_meta ($wordpress_user_id, 'steam_preferredUsername', $identity->preferredUsername);
			/* echo '<pre>';
			print_r($identity); 
			echo '</pre>'; */
		} 
	} 
}

function steam_api(){
	// 953F0C6D5F14F5F4444D877A416486A2
	echo 'sali';
}

function update_battlenet_meta($oa_social_login_user_token, $wordpress_user_id){
	
	global $site_public_key, $site_private_key;
	
	echo 'updating battlenet meta</br></br>';
	
	//Your Site Settings
	$site_subdomain = 'poros';
 
	//API Access Domain
	$site_domain = $site_subdomain.'.api.oneall.com';
 
	//Connection Resource
	$resource_uri = 'https://'.$site_domain.'/users/'.$oa_social_login_user_token.'.json';
 
	  //Setup connection
	  $curl = curl_init();
	  curl_setopt($curl, CURLOPT_URL, $resource_uri);
	  curl_setopt($curl, CURLOPT_HEADER, 0);
	  curl_setopt($curl, CURLOPT_USERPWD, $site_public_key . ":" . $site_private_key);
	  curl_setopt($curl, CURLOPT_TIMEOUT, 15);
	  curl_setopt($curl, CURLOPT_VERBOSE, 0);
	  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	  curl_setopt($curl, CURLOPT_FAILONERROR, 0);
 
	//Send request
	$result_json = json_decode(curl_exec($curl));
	curl_close($curl);
   
    // update profile fields for steam
   foreach($result_json->response->result->data->user->identities as $identity){
		if($identity->provider == 'battlenet'){
			$account = $identity->accounts[0];
			update_user_meta ($wordpress_user_id, 'battletag', $account->username);
			/* echo '<pre>';
			print_r($identity);
			echo '</pre>'; */
		}
	} 
}


//*******************************************************************************
// Social Login: Action called before redirecting the user
//*******************************************************************************

function oa_social_login_do_before_user_redirect ($user_data, $identity, $redirect_to)
{
	echo "User will be redirected to ".$redirect_to;
	
	// get social login token so we can get meta data
	$oa_social_login_user_token = get_user_meta($user_data->data->ID, 'oa_social_login_user_token', $single)[0];
	update_steam_meta($oa_social_login_user_token, $identity->identity_token, $user_data->data->ID);
	
	// only activate the following code to get all users metadata updated
	// update_all_social_data();
	
}
add_action ('oa_social_login_action_before_user_redirect', 'oa_social_login_do_before_user_redirect', 10, 3);

function update_all_social_data(){
	global $site_public_key, $site_private_key;
	//Your Site Settings
	$site_subdomain = 'poros';

	//API Access Domain
	$site_domain = $site_subdomain.'.api.oneall.com';

	//Connection Resource
	$resource_uri = 'https://'.$site_domain.'/users.json';

	//Setup connection
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $resource_uri);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_USERPWD, $site_public_key . ":" . $site_private_key);
	curl_setopt($curl, CURLOPT_TIMEOUT, 15);
	curl_setopt($curl, CURLOPT_VERBOSE, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_FAILONERROR, 0);

	//Send request
	$result_json = json_decode(curl_exec($curl));
	curl_close($curl);

	echo '<pre>';
	print_r($result_json);
	echo '</pre>';

	// error_reporting(E_ALL);
	foreach($result_json->response->result->data->users->entries as $entry){  
		$wordpress_user_id = get_users(array('meta_key' => 'oa_social_login_user_token', 'meta_value' => $entry->user_token))[0]->data->ID;
		foreach($entry->identities as $identity){
			switch($identity->provider){
				case 'steam':
					update_steam_meta($entry->user_token, $identity->identity_token, $wordpress_user_id);
				break;
				case 'battlenet':
					update_battlenet_meta($entry->user_token, $wordpress_user_id);
				break;
			}
		}
	}
}


// enable page excerpts
add_action( 'init', 'my_add_excerpts_to_pages' );
function my_add_excerpts_to_pages() {
     add_post_type_support( 'page', 'excerpt' );
}

// get all online twitch streams out of the db
function list_online_twitch_streams($atts ) {
	wp_enqueue_script( "hover_jquery", "/hover.js");
	$atts = shortcode_atts(
		array(
			'widget' => false,
			'filter' => "none"
		), $atts);

	// establish db connection 
	// REPLACE DB CONNECTION
	
	// determine amount of online streams
	$query = $db->prepare("SELECT displayname, name, stream_title, viewers, medium_thumbnail, game FROM streamers where status = 'online'");
	$query->execute();
	$online_streams = $query->rowCount();
	
	if($atts['widget']){
		// $query = $db->prepare("SELECT displayname, name, stream_title, viewers, medium_thumbnail, game FROM streamers where status = 'online' AND `name` = 'gamerbase_ch' ORDER BY RAND() LIMIT 1");
		$query = $db->prepare("SELECT displayname, name, stream_title, viewers, medium_thumbnail, game, language FROM streamers where status = 'online' ORDER BY RAND() LIMIT 1");
	} else {	
		switch($atts['filter']){
			case "lol":
				$query = $db->prepare(
				"SELECT displayname, name, stream_title, viewers, medium_thumbnail, game, language 
				FROM streamers 
				WHERE status = 'online' AND game = 'League of Legends'
				ORDER BY viewers DESC");
			break;
			case "csgo":
				$query = $db->prepare(
				"SELECT displayname, name, stream_title, viewers, medium_thumbnail, game, language 
				FROM streamers 
				WHERE status = 'online' AND game = 'Counter-Strike: Global Offensive'
				ORDER BY viewers DESC");
			break;
			case "hearth":
				$query = $db->prepare(
				"SELECT displayname, name, stream_title, viewers, medium_thumbnail, game, language
				FROM streamers 
				WHERE status = 'online' AND game = 'Hearthstone: Heroes of Warcraft'
				ORDER BY viewers DESC");
			break;
			case "none":
				$query = $db->prepare("SELECT status, displayname, name, stream_title, viewers, medium_thumbnail,game, language FROM streamers where status = 'online' ORDER BY viewers DESC");
			break;	
		}
		
	}
	try{
		$query->execute();
		if ($query->rowCount() > 0){
			$streamers = $query->fetchAll(PDO::FETCH_ASSOC);
			if($atts['widget']){
				$html .= '<div class="container-fluid twitch_container_widget">';
			} else {
				$html .= '<div class="container-fluid twitch_container">';
			}
			
			$counter = 0;
			foreach ($streamers as $streamer){
				if($atts['widget']){
					$html .= '<div class="col-md-12">';
				} else {
					$html .= '<div class="col-md-4">';
				}
				$html .= display_streamer($db, $streamer);
				$html .= '</div>';	
				$counter++;
			}
			
			if($atts['widget']){
				if($online_streams == 1){ 
					$html .= '<a class="online_streams_link" href="/streams/">'.$online_streams.' swiss Stream is online.</a>';
				} else {
					$html .= '<a class="online_streams_link" href="/streams/">'.$online_streams.' swiss Streams are online.</a>';
				}
			} 

			$html .= '</div>';
		}  else {
			$html = '<div class="nostreams">Momentan sind keine Schweizer Streamer online</br>
				<a href="http://www.gamerbase.ch/all-streams/">Komplettes Streamerverzeichnis</a>
			</div>';
		}
	} 
	catch(PDOException $ex) {
		return 'Error: '.$ex->getMessage();
	}
	return $html;
	
}


// determine textual representation of a single language
function get_language_name_by_id($id, $db){
	$language = 'No Language';				
	$sql = "SELECT name FROM languages WHERE id = $id";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $languages[0]['name'];
}

// get all twitch streams out of the db
function list_all_twitch_streams($atts ) {
	wp_enqueue_script( "hover_jquery", "/hover.js");
	
	// display language selector for admin
	$language_selector = false;
	if(get_current_user_id() == 1) $language_selector = true;

	// establish db connection 
	// REPLACE DB CONNECTION
	
	$query = $db->prepare("SELECT displayname, name, stream_title, viewers, medium_thumbnail,game, language, status, last_seen_online FROM streamers where displayname != '' ORDER BY last_seen_online DESC");
	

	try{
		$query->execute();
		if ($query->rowCount() > 0){
			$streamers = $query->fetchAll(PDO::FETCH_ASSOC);
			$html .= '<div class="container-fluid twitch_container">';
			foreach ($streamers as $streamer){
				$html .= '<div class="col-md-4">';
				$html .= display_streamer($db, $streamer);
				$html .= '</div>';	
			}
			$html .= '</div>';
		} 
	} 
	catch(PDOException $ex) {
		return 'Error: '.$ex->getMessage();
	}
	return $html;
	
}

function display_streamer($db, $streamer){			
	$filename = home_url().'/twitchdata/'.$streamer['name'].'_medium_thumb.jpg';
			
	$html .= '
	<div class="stream-item';
	if($streamer['status'] == 'online'){
		$html .= ' stream_active ">';
	} else {
		$html .= '">';
	}

	$html .= '
	<a target="_blank" href="http://www.twitch.com/'.$streamer['name'].'" data-image="'.home_url().'/twitchdata/'.$streamer['name'].'_large_thumb.jpg" href="http://twitch.tv/'.$streamer['name'].'/profile" class=" ';

	// if medium thumb doesnt exist, just show the grayed out 404 image, and dont try to hover either
	if(file_exists('twitchdata/'.$streamer['name'].'_medium_thumb.jpg')){
		// only display hover thumb if the large thumb file exists 
		if(file_exists('twitchdata/'.$streamer['name'].'_large_thumb.jpg')){
			$html .=  'preview">';
		} else {
			$html .=  '">';
		}
		$html .= '<img  src="'.$filename.'" >';
	} else {
		$html .=  '">';
		$html .= '<img style="opacity:0.2" src="'.home_url().'/twitchdata/404_preview-320x180.jpg." />';
	} 
	$html .= '<span class="name">'.htmlentities($streamer['displayname']).'</span>';
			
	if(!empty($streamer['stream_title'])){
		$html .= '<span class="title">'.htmlentities($streamer['stream_title']).'</span>';
	} 
	if(!empty($streamer['game'])){
		$html .= '<span class="game"><b>Game</b>: '.$streamer['game'].'</span>';
	} 
	
	// determine languages
	$name = $streamer['name'];
	$sql = "SELECT language_id, priority FROM languages_to_streamers WHERE streamer_name = '$name' ORDER by priority DESC";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$streamer_languages = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

	if(count($streamer_languages) > 0){	
		$html .= '<span class="game"><b>Language(s): </b>';
		$i = 0;
		foreach($streamer_languages as $key => $language){
			$html .= get_language_name_by_id($key, $db);
			if($i < count($streamer_languages) && count($streamer_languages) > 1) $html .= ', ';
			$i++;
		}
		$html .= '</span>';
	}
			
	if($streamer['status'] == 'online'){
		$html .= '<span class="viewers"><b>Viewers: '.$streamer['viewers'].'</b></span>';
	}
	
	if($streamer['last_seen_online'] > 0){
		$date_string = date("l, j. F - H:i",$streamer['last_seen_online']);
		if($streamer['status'] == 'online') $date_string = '<b>Right Now!</b>';
		$html .= '<span class="game"><b>Last Stream:</span><span class="game">'.$date_string.'</b></span>';
	}
	
	$html .= '</a>';
	
	if($language_selector){
		$html .= '
		<form style="display:block; text-align:left !important;">';
		$sql = "SELECT id, name FROM languages WHERE 1";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($languages as $language){
			$checked = '';
			$prio = '';
			if(isset($streamer_languages[$language['id']])){
				$checked = 'checked';
				$prio = $streamer_languages[$language['id']];
			}
			
			$html .= '
			<label>Prio:<input size="1" type="text" value="'.$prio.'"></label>
			<label><input type="checkbox" '.$checked.' name="language-'.$language['id'].'" value="'.$language['name'].'" >'.$language['name'].'</label>
			</br>';
		}
		$html .= '
		</form>
	';
	}
	$html .= '</div>';
	return $html;
}


function promo_twitch_streams(){
	wp_enqueue_script( "hover_jquery", "/hover.js");
	// REPLACE DB CONNECTION
	
	$sql = "
	SELECT displayname, name, stream_title, viewers, medium_thumbnail,game, language, status, last_seen_online, promo_week 
	FROM streamers WHERE promo_week != 0 ORDER BY promo_week ASC";
	
	$query = $db->prepare($sql);
	$query->execute();
	$streamers = $query->fetchAll(PDO::FETCH_ASSOC);
	
	$html .= '
	<div class="row twitch_container">';
	
	foreach ($streamers as $streamer){
		$html .= '<div class="col-md-2">';
		$html .= '<span class="week">Woche '.$streamer['promo_week'].'</span>';
		$html .= display_streamer($db, $streamer);
		$html .= '</div>';
	}
	
	$html .= '</div>';
	return $html;
}

/*
 * Get the most recently replied-to topics, and their most recent reply
 */
function custom_bbpress_recent_replies_by_topic($atts){
  $short_array = shortcode_atts(array('show' => 5, 'forum' => false, 'include_empty_topics' => false), $atts);
  extract($short_array);
  
  // default values
  $post_types = array('reply');
  $meta_key = '_bbp_last_reply_id';
  
  // allow for topics with no replies
  if ($include_empty_topics) {
    $meta_key = '_bbp_last_active_id';
    $post_types[] = 'topic';
  }
  
  // get the 5 topics with the most recent replie
  $args = array(
    'posts_per_page' => $show,
    'post_type' => array('topic'),
    'post_status' => array('publish'),
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'meta_key' => $meta_key,
  );

  // allow for specific forum limit
  if ($forum){
    $args['post_parent'] = $forum;
  }
  
  $query = new WP_Query($args);
  $reply_ids = array();  
  
  // get the reply post->IDs for these most-recently-replied-to-topics
  while($query->have_posts()){
    $query->the_post();
    if ($reply_post_id = get_post_meta(get_the_ID(), $meta_key, true)){
      $reply_ids[] = $reply_post_id;
    }
  }
  
  // get the actual replies themselves
  $args = array(
    'posts_per_page' => $show,
    'post_type' => $post_types,
    'post__in' => $reply_ids,
    'orderby' => 'date',
    'order' => 'DESC'
  );
  
  $query = new WP_Query($args);
  ob_start();
    // loop through results and output our rows
    while($query->have_posts()){
      $query->the_post();
      
      // custom function for a single reply row
      custom_bbpress_recent_reply_row_template( $query->current_post + 1 );
    }
  $output = ob_get_clean();
  return $output;
}

/*
 * Executed during our custom loop
 *  - this should be the only thing you need to edit
 */
function custom_bbpress_recent_reply_row_template( $row_number ){
  // get the reply title
  $title = get_the_title();
  
  // optional title adjustments -- delete or comment out to remove
  // remove "Reply To: " from beginning of title
  $title = str_replace('Reply To: ', '', $title);
  
  // trim title to specific number of characters (55 characters)
  $full_title = str_replace("Antworte auf:", "", $title);
  
  // trim title to specific number of words (5 words)...
  // $title = wp_trim_words( $title, 5, '...');
  
  	// determine on which page the latest reply is so we can jump to it
	$reply_page = ceil( (int) bbp_get_reply_position( bbp_get_forum_last_reply_id(get_the_ID() ), get_the_ID()) / (int) bbp_get_replies_per_page() );
			
  
  // determine if odd of even row
  $row_class = ($row_number % 2) ? 'odd' : 'even';  
  ?>
    <div class="bbpress-recent-reply-row <?php print $row_class; ?>">
	  <div class="bbpress_widget_avatar"><?php print get_avatar( get_the_author_meta( 'ID' ), '48' ); ?></div>
      <div class="bbpress_widget_line_1"><a title="<?php echo $full_title; ?>" href="<?php print get_permalink( get_post_meta( get_the_ID(), '_bbp_topic_id', true) ); ?>page/<?php echo $reply_page; ?>/#post-<?php the_ID(); ?>"><?php print ereg_replace("Antworte auf:", "", $title); ?></a></div>
	  <div class="bbpress_widget_line_2"><?php print bbp_user_profile_link( get_the_author_meta( 'ID' ) ); ?> antwortete vor <?php print human_time_diff( get_the_time('U'), time() ) ?></div>
      <!-- <div>Excerpt: <?php the_excerpt(); ?></div> -->
      <!-- <div>Author: <?php the_author(); ?></div> -->
      <!--  <div>Link To Reply: <a href="<?php the_permalink(); ?>">view reply</a></div> -->
      <!-- <div>Link To Topic/page/#/#Reply: <a href="<?php bbp_reply_url( get_the_ID() ); ?>">view reply paged</a></div> -->
      <!-- <div>Time Ago: <?php print human_time_diff( get_the_time('U'), time() ) . ' ago'; ?></div> -->
	  <!-- <div>Avatar linked to bbPress Profile:<a href="<?php print esc_url( bbp_get_user_profile_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php print get_avatar( get_the_author_meta( 'ID' ) ); ?></a></div> -->
	  <div class="bbpress_widget_line_divider">&nbsp;</div>
    </div>
  <?php
  
  // Refs
  // http://codex.wordpress.org/Template_Tags#Post_tags
  // http://codex.wordpress.org/Function_Reference/get_avatar
  // http://codex.wordpress.org/Function_Reference/human_time_diff
  // (template tags for bbpress)
  // https://bbpress.trac.wordpress.org/browser/trunk/src/includes/users/template.php  
  // https://bbpress.trac.wordpress.org/browser/trunk/src/includes/replies/template.php
}

// allow shortcodes to run in widgets
add_filter( 'widget_text', 'do_shortcode');
// don't auto-wrap shortcode that appears on a line of it's own
add_filter( 'widget_text', 'shortcode_unautop');


function getLastGame($date){
	$word = 'second';
	$suffix = 's';
	$dif = (time()-$date);
	
	if($dif > 60){
		$word = 'minute';
		$suffix = 's';
		$dif = $dif/60;
		
		if($dif > 60){
			$word = 'hour';
			$suffix = 's';
			$dif = $dif/60;
			
			if($dif > 24){
				$word = 'day';
				$suffix = 's';
				$dif = $dif/24;
			}
		}
	}
	$dif = floor($dif);
	if($dif > 1){
		$word = $word.$suffix;
	}
	
	return $dif.' '.$word.' ago';
}
function custom_css() {
	wp_register_style( 'FontAwesome', get_template_directory_uri().'/css/font-awesome.min.css' );
	wp_enqueue_style( 'FontAwesome' );
	wp_register_style( 'OpenSans', 'http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700,800' );
	wp_enqueue_style( 'OpenSans' );
	wp_register_style( 'TopList', get_template_directory_uri().'/css/toplist.css' );
	wp_enqueue_style( 'TopList' );
	wp_register_style( 'bbPress', get_template_directory_uri().'/bbpress.css' );
	wp_enqueue_style('bbPress');
}
add_action( 'wp_enqueue_scripts', 'custom_css' );
function custom_js() {
		wp_register_script('commentReply', includes_url().'/js/comment-reply.min.js', false);
		wp_enqueue_script( 'commentReply' );
}
add_action('wp_enqueue_scripts', 'custom_js');
if ( function_exists('register_nav_menus') ){
	register_nav_menus( array(
		'primNav' => 'Primary Navigation',
		'topNav' => 'Top Navigation',
		'mobileNav' => 'Mobile Navigation',
		'bottomNav' => 'Bottom Navigation'
	) );
}

/*if ( function_exists('register_sidebar') ){
	register_sidebar( array(
		'name' => __('Primary Sidebar', 1),
		'before_widget' => '<article class="sideElement">',
		'after_widget' => "</article>",
		'before_title' => '<header class="head"><h3>',
		'after_title' => '</h4></header>'
	) );
	
	register_sidebar( array(
		'name' => __('Footer', 2),
		'before_widget' => '<article class="footerElement">',
		'after_widget' => "</article>",
		'before_title' => '<header class="head"><h3>',
		'after_title' => '</h4></header>'
	) );
} */
add_theme_support( 'custom-header', array() );
add_theme_support( 'post-thumbnails' ); 
function swisslol_comment($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment;
	extract($args, EXTR_SKIP);
	
	if ( 'div' == $args['style'] ) {
		$tag = 'div';
	} else {
		$tag = 'li';
	}
	
	$o = '<'.$tag.' id="comment-'.get_comment_ID().'">';
	$o .= '<div class="comment clearfix">';
	$o .= '<div class="profile">';
	$o .= '<div class="avatar">'.get_avatar( $comment, $args['avatar_size'] ).'</div>';
	$o .= '<div class="nickname">'.get_comment_author_link().'</div>';
	$o .= '</div>';
	$o .= '<div class="commentContent">';
	$o .= '<p><span class="date">'.get_comment_date('j. F Y').' | '.get_comment_time('H:i').'</span> '; 
	if(is_user_logged_in()){ 
		$o .= '<span class="reply right">'.get_comment_reply_link( array_merge( $args, array( 'before' => ' | ', 'add_below' => $add_below, 'depth' => $depth, 'max_depth' => $args['max_depth'], 'reply_text' => '<i class="fa fa-reply"></i>' ) ) ).'</span>';
		$o .= '<span class="edit right"><a href="'.get_edit_comment_link().'"><i class="fa fa-pencil"></i></a></span>';
	}
	$o .= '</p>';
	$o .= '<p>'.get_comment_text().'</p>';
	$o .= '</div></div>';
	
	echo $o;
}
/*  Add responsive container to embeds
/* ------------------------------------ */ 
function alx_embed_html( $html ) {
    return '<div class="videoContainer">' . $html . '</div>';
}
add_filter( 'embed_oembed_html', 'alx_embed_html', 10, 3 );
add_filter( 'video_embed_html', 'alx_embed_html' ); // Jetpack

add_action( 'wp_enqueue_scripts', 'add_my_script' );

?>


