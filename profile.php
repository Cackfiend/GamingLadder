<?php
session_start();
require 'conf/variables.php';
require_once 'autologin.inc.php';
require_once 'include/gametable.inc.php';

require 'top.php';
?>
<script type="text/javascript">
$(document).ready(function() 
    { 
        $("#games").tablesorter({sortList: [[0,1]], widgets: ['zebra'] }); 
        // Set the values to the last used values
        if ($.cookie('profileplay') == "-") {
            $("#availabletoplayexpand").html("[-]");
        } else {
            $("#availabletoplayexpand").html("[+]");
            $("#availabletoplaydiv").hide();
        }
        if ($.cookie('profilegames') == "-") {
            $("#gamesexpand").html("[-]");
        } else {
            $("#gamesexpand").html("[+]");
            $("#gamesdiv").hide();
        }
 
        // Handle the toggle of playing expansion/collapse 
        $("#availabletoplayexpand").click(function()
        {
            $("#availabletoplaydiv").slideToggle(600);
            if ($("#availabletoplayexpand").html() == "[-]") {
               $("#availabletoplayexpand").html("[+]");
               $.cookie('profileplay', "+");
            } else {
               $("#availabletoplayexpand").html("[-]");
               $.cookie('profileplay', "-");
            }
        });
 
        // Handle the toggle of games expansion/collapse 
        $("#gamesexpand").click(function()
        {
            $("#gamesdiv").slideToggle(600);
            if ($("#gamesexpand").html() == "[-]") {
               $("#gamesexpand").html("[+]");
               $.cookie('profilegames', '+');
            } else {
               $("#gamesexpand").html("[-]");
               $.cookie('profilegames', '-');
            }
        });
    } 
); 
</script>

<?php
// Get My Ladder Rank
$result = mysql_query($standingsSqlWithRestrictions." LIMIT ".$playersshown,$db) ;

// Loop through and find me
$cur = 1;
$rank = "";
while ($player = mysql_fetch_array($result)) {
    if ($player['name'] == $_GET['name']) {
        $rank = $cur;
        break;
    }
    $cur++;
}

// If the player has no rank he is passive, so make a second query grabbing the passive players
if ($rank == "") {
    $result = mysql_query("select * from $standingscachetable right join $playerstable USING (name) WHERE confirmation <> 'Deleted' AND name = '".$_GET['name']."'", $db);
	$player = mysql_fetch_array($result);
    $rank = "unranked";
}

// PASSIVE CHECK: Lets get to know how many days the player has left before hes put into passive mode -----------------
$sql = "SELECT reported_on + '".$passivedays." days' < now() AS passive, ".$passivedays." - (to_days(now()) - to_days(reported_on)) as daysleft  from $gamestable WHERE (winner = '$_GET[name]' OR loser = '$_GET[name]') AND contested_by_loser = 0 AND withdrawn = 0 ORDER BY reported_on DESC LIMIT 1";
$result = mysql_query($sql, $db);
list($passive, $daysleft) = mysql_fetch_row($result);

if ($player["approved"] == "no") {
$blocked = "(<font color='#FF0000'>blocked or not added yet</font>)";
}else{
$blocked = "";
}

$avan = $player["Avatar"];

if ($player["mail"] == "n/a") {
    $mailaddress = "n/a";
    $mailpic = "";
} else {
    if ($player['Joined'] != NULL) { 
        $joined = date("H:i d m y", $player['Joined']); 
    } else {
        $joined ="00:00 06 03 08"; 
    }

    // Read the mail from the db and make it spambotsafe...
    $mailaddress = $player['mail'];
    $mailaddress = str_replace("@", " (at) ", $mailaddress);
    $mailaddress = str_replace(".", " (dot) ", $mailaddress);
    $jabbername = $player['Jabber'];
    $jabbername = str_replace("@", " (at) ", $jabbername);
    $jabbername = str_replace(".", " (dot) ", $jabbername);
    $jabberpic = "<img border='1' src='images/jabber.gif' align='absmiddle' alt='Jabber' />";
    $mailpic = "<img border='1' src='images/mail.gif' align='absmiddle' alt='email' /></a>";
}
if ($player['icq'] == "n/a") {
    $icqnumber = "n/a";
    $icqpic = "";
} else {
    $icqnumber = $player['icq'];
    $icqpic = "<img border='1' src='images/icq.gif' align='absmiddle' alt='icq' />";
}
if ($player['aim'] == "n/a") {
    $aimname = "n/a";
    $aimpic = "";
} else {
    $aimname = $player['aim'];
    $aimpic = "<img border='1' src='images/aim.gif' align='absmiddle' alt='aim' />";
}
if ($player['msn'] == "n/a") {
    $msnname = "n/a";
    $msnpic = "";
} else {
    $msnname = $player['msn'];
    $msnname = str_replace("@", " (at) ", $msnname);
    $msnname = str_replace(".", " (dot) ", $msnname);
    $msnpic = "<img border='1' src='images/msn.gif' align='absmiddle' alt='msn' /></a>";
}

if ($player['games'] <= 0) {
    $totalpercentage = 0.000;
} else {
    $totalpercentage = round($player['wins'] / $player['games'] * 100, 0);
}

?>
<table width="100%" cellpadding="1px">
<tr>
<td valign="top">

<h1><?php


if ($_SESSION['username'] == $_GET[name]) {
	    echo "<a href='edit.php'>$player[name] $blocked</a>";
	} else {
	    echo "$player[name] $blocked";
	}
?>
</h1>


<?php 
// Show the players title if he has one...
if ( $player["Titles"]  != "" ) {
	echo "<b>" . $player["Titles"] . "</b><br />";
}

// Show if he's provisional...
if ( $player["provisional"]  == "1" ) {
	echo "<a href=\"faq.php#provisional\">provisional player</a><br />";
}

if ($rank != "unranked") { 
    if ($daysleft >= 0) {
        echo " ($daysleft days left)";
    }
} 

// If we are logged in and displaying somebody elses profile, tell us about my win/loss
if (isset($_SESSION['username']) && $player['name'] != $_SESSION['username']) {
    // Print the comma separator only if the player is active and we are logged in.
    if ($rank != "unranked" && $daysleft >= 0) {
        echo ", ";
    }
    require_once 'include/elo.class.php';
    $elo = new Elo($db);
    $winresult = $elo->RankGame($_SESSION['username'],$player['name'], date("Y-m-d H:i:s"));
    $lossresult = $elo->RankGame($player['name'],$_SESSION['username'], date("Y-m-d H:i:s"));
    $drawresult = $elo->RankGame($player['name'],$_SESSION['username'], date("Y-m-d H:i:s"), true);

    echo "Points for Win/Loss/Draw: ".$winresult['winnerChange']."/".$lossresult['loserChange']."/".$drawresult['loserChange'];
}
?>
</td>
<td valign="top">
<img src='avatars/<?php echo "$player[Avatar].gif"; ?>' alt='<?php echo $player[Avatar] ?>' />
<?php 
	echo "<br/> <p class='text'><img src='graphics/flags/$player[country].bmp' align='middle' border='1' alt='' /> $player[country] </p>"; 


?>
</td>
</tr>
</table>

<table width="100%" class="tablesorter">
<thead>
<tr>
<th>Rank</th>
<th>Rating</th>
<th>Percent</th>
<th>Wins</th>
<th>Losses</th>
<th>Played</th>
<th>Average P WLT</th>
<th>Streak</th>
<th>Sportsmanship</th>
<th>Revoked Games</th>
</tr>
</thead>
<tbody>
<tr>
<td>
<?php

// we need some info to get to know how many points the player wins in average WHEN he wins, and the same about when he loses...

$sql = "SELECT round(avg(loser_points),0) FROM $gamestable WHERE loser = '$_GET[name]'";
$result = mysql_query($sql, $db);
$row = mysql_fetch_row($result);
$avgPointsOnLoss = $row[0];

$sql = "SELECT round(avg(winner_points),0) FROM $gamestable WHERE winner = '$_GET[name]'";
$result = mysql_query($sql, $db);
$row = mysql_fetch_row($result);
$avgPointsOnWin = $row[0];

$sql = "SELECT coalesce(sum(withdrawn),0), coalesce(sum(contested_by_loser),0) from $gamestable WHERE winner = '".$_GET['name']."'";
$result = mysql_query($sql, $db);
list($withdrawn, $contestedByOthers) = mysql_fetch_row($result);
$sql = "SELECT coalesce(sum(contested_by_loser),0) from $gamestable WHERE loser = '".$_GET['name']."'";
$result = mysql_query($sql, $db);
list($contested) = mysql_fetch_row($result);

// get the players averahe points / game...
if ($player[games] > 0) {
	$avgPointsPerGame = round((($player[rating] - BASE_RATING)/$player[games]),2);
} else {
    $avgPointsPerGame = 0;
}

if ($player[games] < $gamestorank) {
    echo "unranked"; 
} else {
    // Get to know how many points the player gets in an average game... this will also say something about him as a player choosing his opponents.
    if ($daysleft >= 0) {
        echo $rank;
    } else {
        echo "<a href=\"faq.php#passive\">(passive)</a>";
    }  
}
// Get average sportsmanship. This will get the points one has gotten from others while one is the loser of the game.
$sql = "SELECT sum(loser_stars) as total_stars, count(loser_stars) as count FROM $gamestable WHERE loser = '".$_GET['name']."'  AND loser_stars IS NOT NULL";
$result = mysql_query($sql, $db);
$row = mysql_fetch_array($result);
$SportsmanshipAsLoser = $row['total_stars'];
$SportsmanshipRatedAsLoser = $row['count'];

// This will get the points one has gotten from others while one is the winner of the game.
$sql = "SELECT sum(winner_stars) as total_stars, count(winner_stars) as count FROM $gamestable WHERE winner = '".$_GET['name']."'  AND winner_stars IS NOT NULL";
$result = mysql_query($sql, $db);
$row = mysql_fetch_array($result);
$SportsmanshipAsWinner = $row[total_stars];
$SportsmanshipRatedAsWinner = $row['count'];

// We must to account of the fact that a user may only have a sportsmanship rating as either a winner or a loser.
// You must average at the last possible moment, so we can't create a total sportsmanship average in the SQL.
// Instead we do that here.
if (($SportsmanshipRatedAsLoser+$SportsmanshipRatedAsWinner) > 0) {
    $sportsmanship = round((($SportsmanshipAsWinner+$SportsmanshipAsLoser)/($SportsmanshipRatedAsLoser+$SportsmanshipRatedAsWinner)),0);
} else {
    $sportsmanship = "-";
}
?>
</td>
<td><? if ($player['games'] <= 0) { echo BASE_RATING ;} else { echo round($player[rating],0); } ?></td>
<td><?echo $totalpercentage ?>%</td>
<td><?echo "$player[wins]" ?></td>
<td><?echo "$player[losses]" ?></td>
<td><?echo "$player[games]" ?></td>
<td><? if ($player['games'] > 0) { echo "$avgPointsOnWin / $avgPointsOnLoss / $avgPointsPerGame"; } else { echo "-"; } ?></td>

<td><? if ($player['games'] > 0) { echo "$player[streak]"; } else { echo "-"; }  ?></td>
<td><?echo $sportsmanship; ?></td>
<td><?php 

// Avoid division by zero problems...

if ($player['games'] > 0) {
echo sprintf("%0.0f%% (%d / %d / %d)",($withdrawn+$contestedByOthers+$contested)/($player['games']+$withdrawn+$contestedByOthers+$contested)*100, $withdrawn, $contestedByOthers, $contested);
} else {
echo "-";
}

?></td>
</tr>
</tbody>
</table>

<table class="tablesorter"><tbody><tr><td>
<?php 
if ($player[MsgMe] == "Yes") {
    echo "<b><font color=\"#0D3D02\">Contact me to play!</font></b>";
} else {
    echo "<font color=\"#9E005D\">Please don't message me asking for a game.</font>";
}
		
if ($_SESSION['username'] && $player[MsgMe] == "Yes") {
    echo "  <a href=\"challenge.php?challenger=".urlencode($_SESSION['username'])."&challenged=$_GET[name]\">[Challenge]</a>";
}
?>
</td>
</tr>
</tbody>
</table>

<?php // Only show contact info if the user wants to be contacted 
if ($player[MsgMe] == "Yes") {
?>
	<table class="tablesorter">
    <thead>
        <tr>
        <th>Mail <?php echo $mailpic ?></th>
        <th>ICQ <?php echo $icqpic ?></th>
        <th>AIM <?php echo $aimpic ?></th>
        <th>MSN <?php echo $msnpic ?></th>
        <th>Jabber <?php echo $jabberpic ?></th>
        </tr>
    </thead>
    <tbody>
	<tr>
        <td><?php echo $mailaddress ?></td>
        <td><?php echo $icqnumber ?></td>
        <td><?php echo $aimname ?></td>
        <td><?php echo $msnname ?></td>
        <td><?php echo $jabbername ?></td>
    </tr>
    </tbody>	
</table>

<?php 

if ($player[CanPlay] != "") { ?>
    <h2>Available to play <a id="availabletoplayexpand"></a></h2>	
	<p class="text">Uses <?echo "$player[HaveVersion]" ?> version of Wesnoth & can usually play (GMT):</p>
<div id="availabletoplaydiv">
	<table id="availabletoplay" class="tablesorter">
    <thead>	
	<tr>
	<th></th>
	<th>Morning</th>
	<th>Noon</th>
	<th>Afternoon</th>
	<th>Evening</th>
	<th>Night</th>
	</tr>
    </thead>
    <tbody>
<?php
    $days = array("Monday" => "Mon", "Tuesday" => "Tue", "Wednesday" => "Wed", 
                  "Thursday" => "Thu", "Friday" => "Fri", "Saturday" => "Sat", "Sunday" => "Sun");	
foreach($days as $name => $abbrev) {
    if ($class == "odd") {
        $class = "even";
    } else {
        $class = "odd";
    }
?>
	<tr class="<?php echo $class ?>">
		<td style="text-align: right; font-weight: bold"><?php echo $name ?></td>
		
		<td><?php $pos1 = strpos("$player[CanPlay]", $abbrev."M");
		if ($pos1 != FALSE) {echo "<img border=\"0\" height='20px' src=\"images/streakplus.gif\" />";}?></td>
		<td><?php $pos1 = strpos("$player[CanPlay]", $abbrev."N");
		if ($pos1 != FALSE) {echo "<img border=\"0\" height='20px' src=\"images/streakplus.gif\" />";} ?></td>
		<td><?php $pos1 = strpos("$player[CanPlay]", $abbrev."A");
		if ($pos1 != FALSE) {echo "<img border=\"0\" height='20px' src=\"images/streakplus.gif\" />";} ?></td>
		<td><?php $pos1 = strpos("$player[CanPlay]", $abbrev."E");
		if ($pos1 != FALSE) {echo "<img border=\"0\" height='20px' src=\"images/streakplus.gif\" />";} ?></td>
		<td><?php $pos1 = strpos("$player[CanPlay]", $abbrev."G");
		if ($pos1 != FALSE) {echo "<img border=\"0\" height='20px' src=\"images/streakplus.gif\" />";} ?></td>
	</tr>
	
	<?php } ?>
    </tbody>
	</table>
    </div>
	<?php } ?>
<?php }

// Only show game history if there are any played games...

if ($player[games] > 0) { 


    $sql = "SELECT reported_on, DATE_FORMAT(reported_on, '".$GLOBALS['displayDateFormat']."') as report_time, unix_timestamp(reported_on) as unixtime, winner, loser, winner_points, loser_points, winner_elo, loser_elo, length(replay) as is_replay, replay_downloads, withdrawn, contested_by_loser, winner_comment, loser_comment, winner_stars, loser_stars, winner_games, loser_games FROM $gamestable WHERE winner = '$_GET[name]' OR loser = '$_GET[name]'  ORDER BY reported_on DESC LIMIT 30";

$result = mysql_query($sql,$db);
?>

<h2>Recent Games <a id="gamesexpand"></a></h2>
<div id="gamesdiv">
<table id="games" class="tablesorter">
	<?php echo gameTableTHead(); ?>
	<?php echo gameTableTBody($result, $_GET['name']); ?>
</table>
</div>
<br />
<?php
}
require('bottom.php');
?>
