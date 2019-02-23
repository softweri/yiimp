<?php

$algo = user()->getState('yaamp-algo');

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.cursor.js");
JavascriptFile('/yaamp/ui/js/auto_refresh.js');

$height = '240px';

$min_payout = floatval(YAAMP_PAYMENTS_MINI);
$btc_min_payout = floatval(YAAMP_BTC_PAYMENTS_MINI);
$min_sunday = $min_payout/10;

$payout_freq = (YAAMP_PAYMENTS_FREQ / 3600)." hours";
$btc_payout_freq = (YAAMP_BTC_PAYMENTS_FREQ / 3600)." hours";
?>

<div id='resume_update_button' style='color: #444; background-color: #ffd; border: 1px solid #eea;
	padding: 10px; margin-left: 20px; margin-right: 20px; margin-top: 15px; cursor: pointer; display: none;'
	onclick='auto_page_resume();' align=center>
	<b>Auto refresh is paused - Click to resume</b></div>

<table cellspacing=20 width=100%>
<tr><td valign=top width=50%>

<!--  -->

<div class="main-left-box">
<div class="main-left-title">thepool.life</div>
<div class="main-left-inner">

<ul>

<li><img src="/images/logo.png" align="left" height="150px" hspace="10px" <="" img=""></li>
<div></div>
	<li>Welcome to thepool.life mining pool.</li>
	<li>thepool.life offers multiple world-wide stratum servers to bring you the best mining experience.</li>
	<li>&nbsp;</li>
	<li>No registration is required to mine with us. All payouts are done in the currency you mine. Use your wallet address as your username.</li>
	<!-- <li>Coins that are exchange enabled can be mined with and paid out to your BTC wallet address.</li> -->
	<li>See payout information below in our stratum sections.</li>
	<!-- <li><div style="float:right;">For some coins, there is an initial delay before the first payout, please wait at least 6 hours before asking for support.</div></li> -->
	<!-- <li>Block reward is split according to the number of valid shares submitted.</li> -->

<br/>

</ul>

</div></div>
<br/>

<div class="main-left-box">
<div class="main-left-title">thepool.life announcements</div>
<div class="main-left-inner">

<div></div><ul>
        <li><b>February promotion on thepool.life</b></li>
        <li>Mine any of our coins during the month of February and have a chance to win 0.1 BTC worth of that coin!</li>
        <li>&nbsp;</li>
        <li>To win all you have to do is mine any of our coins from now until February 28th 2019. On Saturday March 2nd one random wallet will be selected as the winner.</li>
        <li>0.1BTC worth of the winning wallets mined coin will be purchased and then sent to the selected address.</li>
	<li>Good luck and happy mining!</li>
<br/>
	<li><b>Our Twitter and Facebook pages are now active! Click on the links below to like and follow to stay up to date with new coin releases and promos!</li>
</ul>

</div></div>
<br/>

<!--  -->

<div class="main-left-box">
<div class="main-left-title">How to mine with thepool.life</div>
<div class="main-left-inner">

<table>
<thead>
<tr>
<th>Stratum Location</th>
<th>Coin</th>
<th>Wallet Address</th>
<th>Rig Name</th>
</tr>
</thead>
<tbody><tr>
<td>
<select id="drop-stratum" colspan="2" style="min-width: 140px; border-style:solid; padding: 3px; font-family: monospace; border-radius: 5px;">
	<option value="mine.">US East</option>
	<option value="us.west.">US West</option>
	<option value="aus.">AUS Stratum</option>
	<option value="cad.">CAD Stratum</option>
	<option value="uk.">UK Stratum</option>
</select>
</td>
<td>
<select id="drop-coin" style="border-style:solid; padding: 3px; font-family: monospace; border-radius: 5px;">


<?php
$list = getdbolist('db_coins', "enable and visible and auto_ready order by algo asc");

$algoheading="";
$count=0;
foreach($list as $coin)
			{
			$name = substr($coin->name, 0, 18);
			$symbol = $coin->getOfficialSymbol();
          $id = $coin->id;
          $algo = $coin->algo;

$port_count = getdbocount('db_stratums', "algo=:algo and symbol=:symbol", array(
':algo' => $algo,
':symbol' => $symbol
));

$port_db = getdbosql('db_stratums', "algo=:algo and symbol=:symbol", array(
':algo' => $algo,
':symbol' => $symbol
));

if ($port_count >= 1){$port = $port_db->port;}else{$port = '0.0.0.0';}
if($count == 0){ echo "<option disabled=''>$algo";}elseif($algo != $algoheading){echo "<option disabled=''>$algo</option>";}
echo "<option data-port='$port' data-algo='-a $algo' data-symbol='$symbol'>$name ($symbol)</option>";

$count=$count+1;
$algoheading=$algo;
}
?>
</select>
</td>
<td>
<input id="text-wallet" type="text" size="44" placeholder="RF9D1R3Vt7CECzvb1SawieUC9cYmAY1qoj" style="border-style:solid; border-width: thin; padding: 3px; font-family: monospace; border-radius: 5px;">
</td>
<td>
<input id="text-rig-name" type="text" size="10" placeholder="001" style="border-style:solid; border-width: thin; padding: 3px; font-family: monospace; border-radius: 5px;">
</td>
<td>
<input id="Generate!" type="button" value="Start Mining" onclick="generate()" style="border-style:solid; padding: 3px; font-family: monospace; border-radius: 5px;">
</td>
</tr>
<tr><td colspan="5"><p class="main-left-box" style="padding: 3px; background-color: #ffffee; font-family: monospace;" id="output">-a  -o stratum+tcp://mine.thepool.life:0000 -u . -p c=</p>
</td>
</tr>
</tbody></table>
<ul>
<li>&lt;WALLET_ADDRESS&gt; must be valid for the currency you mine. <b>DO NOT USE a BTC address here, the auto exchange is disabled on these stratums</b>!</li>
<li><b>Our stratums are now NiceHASH compatible, please message support if you have any issues.</b></li>
<li>See the "thepool.life coins" area on the right for PORT numbers. You may mine any coin regardless if the coin is enabled or not for autoexchange. Payouts will only be made in that coins currency.</li>
<li>Payouts are made automatically every hour for all balances above <b><?= $min_payout ?></b>, or <b><?= $min_sunday ?></b> on Sunday.</li>
<br>

</ul>
</div></div><br>

<!--  -->
<!--
<div class="main-left-box">
<div class="main-left-title">thepool.life autoexchanged stratum</div>
<div class="main-left-inner">

	<table>
	<thead>
	<tr>
	<th>Stratum Location</th>
	<th>Coin</th>
	<th>Wallet Address</th>
	<th>Rig Name</th>
	</tr>
	</thead>
	<tbody><tr>
	<td>
	<select id="drop-stratumb" colspan="2" style="min-width: 140px; border-style:solid; padding: 3px; font-family: monospace; border-radius: 5px;">
		<option value="autoexchange.">Exchange Stratum</option>
	</select>
	</td>
	<td>
	<select id="drop-coinb" style="border-style:solid; padding: 3px; font-family: monospace; border-radius: 5px;">


	<?php
	$list = getdbolist('db_coins', "enable and visible and not dontsell and auto_ready order by algo asc");

	$algoheading="";
	$count=0;
	foreach($list as $coin)
				{
				$name = substr($coin->name, 0, 18);
				$symbol = $coin->getOfficialSymbol();
	          $id = $coin->id;
	          $algo = $coin->algo;

	$port_count = getdbocount('db_stratums', "algo=:algo and symbol=:symbol", array(
	':algo' => $algo,
	':symbol' => $symbol
	));

	$port_db = getdbosql('db_stratums', "algo=:algo and symbol=:symbol", array(
	':algo' => $algo,
	':symbol' => $symbol
	));

	$port = $port_db->port;

	if($count == 0){ echo "<option disabled=''>$algo";}elseif($algo != $algoheading){echo "<option disabled=''>$algo</option>";}
	echo "<option data-port='$port' data-algo='-a $algo' data-symbol='$symbol'>$name ($symbol)</option>";

	$count=$count+1;
	$algoheading=$algo;
	}
	?>
	</select>
	</td>
	<td>
	<input id="text-walletb" type="text" size="44" placeholder="RF9D1R3Vt7CECzvb1SawieUC9cYmAY1qoj" style="border-style:solid; border-width: thin; padding: 3px; font-family: monospace; border-radius: 5px;">
	</td>
	<td>
	<input id="text-rig-nameb" type="text" size="10" placeholder="001" style="border-style:solid; border-width: thin; padding: 3px; font-family: monospace; border-radius: 5px;">
	</td>
	<td>
	<input id="Generate!" type="button" value="Start Mining" onclick="generateb()" style="border-style:solid; padding: 3px; font-family: monospace; border-radius: 5px;">
	</td>
	</tr>
	<tr><td colspan="5"><p class="main-left-box" style="padding: 3px; background-color: #ffffee; font-family: monospace;" id="outputb">-a -o stratum+tcp://autoexchange.thepool.life:0000 -u . -p c=</p>
	</td>
	</tr>
	</tbody></table>
	<ul>
		<li>&lt;WALLET_ADDRESS&gt; must be a valid BTC address. <b>Your password, must use <b>-p c=&lt;BTC&gt;</b> or payouts maybe delayed</b>!</li>
		<li>See the "Pool Status" area on the right for autoexchange enabled coins. Only those coins can be mined on this stratum.</li>
		<li>Payouts are made automatically every <?= $btc_payout_freq ?> for all balances above <b><?= $btc_min_payout ?></b> BTC.</li>
	<br>

	</ul>
	</div></div><br>
-->
<div class="main-left-box">
<div class="main-left-title">thepool.life Support</div>
<div class="main-left-inner">

<ul class="social-icons">
    <li><a href="https://www.facebook.com/mine.thepool.life/"><img src='/images/Facebook.png' alt="www.facebook.com/mine.thepool.life" /></a></li>
    <li><a href="https://twitter.com/thepool_life"><img src='/images/Twitter.png' alt="www.twitter.com/thepool_life" /></a></li>
    <li><a href="https://www.youtube.com/channel/UCcuXk3XxuiP1ogaKgcfcdIQ"><img src='/images/YouTube.png' alt="Cryptopool.builders YouTube Tutorial Videos" /></a></li>
    <li><a href="https://github.com/cryptopool-builders/Multi-Pool-Installer"><img src='/images/Github.png' alt="Multi-Pool Installer on GitHub" /></a></li>
    <li><a href="https://discord.gg/CAFmbyH"><img src='/images/discord.png' alt="thepool.life Discord Channel" /></a></li>
		<li><a href="https://play.google.com/store/apps/details?id=kg.stark.jarvis"><img src='/images/mpm.png' alt="MPM - Multiple Pool Monitor" /></a></li>
		<li><a href="https://www.crypto-coinz.net/crypto-calculator"><img src='/images/calculator.png' alt="Crypto Calculator" /></a></li>
</ul>

</div></div><br>
</td><td valign=top>
<!--  -->

<div id='pool_current_results'>
<br><br><br><br><br><br><br><br><br><br>
</div>

<div id='pool_history_results'>
<br><br><br><br><br><br><br><br><br><br>
</div>

</td></tr></table>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

<script>

function page_refresh()
{
	pool_current_refresh();
	pool_history_refresh();
}

function select_algo(algo)
{
	window.location.href = '/site/algo?algo='+algo+'&r=/';
}

////////////////////////////////////////////////////

function pool_current_ready(data)
{
	$('#pool_current_results').html(data);
}

function pool_current_refresh()
{
	var url = "/site/current_results";
	$.get(url, '', pool_current_ready);
}

////////////////////////////////////////////////////

function pool_history_ready(data)
{
	$('#pool_history_results').html(data);
}

function pool_history_refresh()
{
	var url = "/site/history_results";
	$.get(url, '', pool_history_ready);
}

</script>

<script>
function getLastUpdated(){
	var drop1 = document.getElementById('drop-stratum');
	var drop2 = document.getElementById('drop-coin');
	var rigName = document.getElementById('text-rig-name').value;
	var result = '';

	result += drop2.options[drop2.selectedIndex].dataset.algo + ' -o stratum+tcp://';
	result += drop1.value + 'thepool.life:';
	result += drop2.options[drop2.selectedIndex].dataset.port + ' -u ';
	result += document.getElementById('text-wallet').value;
	if (rigName) result += '.' + rigName;
	result += ' -p c=';
	result += drop2.options[drop2.selectedIndex].dataset.symbol;
	return result;
}
function generate(){
  	var result = getLastUpdated()
		document.getElementById('output').innerHTML = result;
}
generate();
</script>

<script>
function getLastUpdatedb(){
	var drop1 = document.getElementById('drop-stratumb');
	var drop2 = document.getElementById('drop-coinb');
	var rigName = document.getElementById('text-rig-nameb').value;
	var result = '';

	result += drop2.options[drop2.selectedIndex].dataset.algo + ' -o stratum+tcp://';
	result += drop1.value + 'thepool.life:';
	result += drop2.options[drop2.selectedIndex].dataset.port + ' -u ';
	result += document.getElementById('text-walletb').value;
	if (rigName) result += '.' + rigName;
	result += ' -p c=BTC';
	return result;
}
function generateb(){
  	var result = getLastUpdatedb()
		document.getElementById('outputb').innerHTML = result;
}
generateb();
</script>
