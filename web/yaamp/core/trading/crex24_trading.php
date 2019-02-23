<?php
function doCrex24CancelOrder($OrderID=false)
{
	if(!$OrderID) return;

	$params = array('ids'=>array($OrderID));
	$res = crex24_api_user('trading/cancelOrdersById', '', json_encode($params));
	if($res && is_array($res)) {
		$db_order = getdbosql('db_orders', "market=:market AND uuid=:uuid", array(
			':market'=>'crex24', ':uuid'=>$OrderID
		));
		if($db_order) $db_order->delete();
	}
}
function doCrex24Trading($quick=false)
{
	$exchange = 'crex24';
	$updatebalances = true;
	if (exchange_get($exchange, 'disabled')) return;
	$data = crex24_api_user('account/balance','nonZeroOnly=false');
	if (!is_array($data) || empty($data)) return;
	$savebalance = getdbosql('db_balances', "name='$exchange'");
	foreach($data as $balance)
	{
		if ($balance->currency == 'BTC') {
			if (is_object($savebalance)) {
				$savebalance->balance = $balance->available;
				$savebalance->onsell = $balance->reserved;
				$savebalance->save();
			}
			continue;
		}
		if ($updatebalances) {
			// store available balance in market table
			$coins = getdbolist('db_coins', "symbol=:symbol OR symbol2=:symbol",
				array(':symbol'=>$balance->currency)
			);
			if (empty($coins)) continue;
			foreach ($coins as $coin) {
				$market = getdbosql('db_markets', "coinid=:coinid AND name='$exchange'", array(':coinid'=>$coin->id));
				if (!$market) continue;
				$market->balance = $balance->available;
				$market->ontrade = $balance->reserved;
				$market->balancetime = time();
				$market->save();
			}
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;

    	$flushall = rand(0, 8) == 0;
	if($quick) $flushall = false;

	$min_btc_trade = exchange_get($exchange, 'min_btc_trade', 0.00050000); // minimum allowed by the exchange
	$sell_ask_pct = 1.01;        // sell on ask price + 1%
	$cancel_ask_pct = 1.20;      // cancel order if our price is more than ask price + 20%

	// auto trade
	foreach ($data as $balance)
	{
		if ($balance->available+$balance->reserved == 0) continue;
		if ($balance->currency == 'BTC') continue;

		$coin = getdbosql('db_coins', "symbol=:symbol AND dontsell=0", array(':symbol'=>$balance->currency));
		if(!$coin) continue;
		$symbol = $coin->symbol;
		if (!empty($coin->symbol2)) $symbol = $coin->symbol2;

		$market = getdbosql('db_markets', "coinid=:coinid AND name='crex24'", array(':coinid'=>$coin->id));
		if(!$market) continue;
		$market->balance = $balance->available;
		//$market->message = $balance->message;

		$orders = NULL;
		if ($balance->available > 0) {
			sleep(1);
			$params = array('instrument'=>$balance->currency."-BTC");
			$orders = crex24_api_user('trading/activeOrders', $params);
		}

		sleep(1);
		$tickers = crex24_api_query('tickers', "instrument={$balance->currency}-BTC");
		if(!$tickers) continue;

            	if(!is_array($tickers) || empty($tickers)) continue;
		$ticker = $tickers[0];

		if(is_array($orders) && !empty($orders))
		{
			foreach($orders as $order)
			{
				$pairs = explode("-", $order->instrument);
				$pair = $order->instrument;
				if ($pairs[1] != 'BTC') continue;

				// ignore buy orders
				if(stripos($order->side, 'sell') === false) continue;

				$ask = bitcoinvaluetoa($ticker->ask);
				$sellprice = bitcoinvaluetoa($order->price);

				// cancel orders not on the wanted ask range
				if($sellprice > $ask*$cancel_ask_pct || $flushall)
				{
				    debuglog("crex24: cancel order $pair at $sellprice, ask price is now $ask");
				    sleep(1);
				    doCrex24CancelOrder($order->id);
				}
				// store existing orders
				else
				{
				    $db_order = getdbosql('db_orders', "market=:market AND uuid=:uuid", array(
					':market'=>'crex24', ':uuid'=>$order->id
				    ));
				    if($db_order) continue;

				    // debuglog("crex24: store order of {$order->Amount} {$symbol} at $sellprice BTC");
				    $db_order = new db_orders;
				    $db_order->market = 'crex24';
				    $db_order->coinid = $coin->id;
				    $db_order->amount = $order->volume;
				    $db_order->price = $sellprice;
				    $db_order->ask = $ticker->ask;
				    $db_order->bid = $ticker->bid;
				    $db_order->uuid = $order->id;
				    $db_order->created = time(); // $order->TimeStamp 2016-03-07T20:04:05.3947572"
				    $db_order->save();
                		}
			}
		}

		// drop obsolete orders
		$list = getdbolist('db_orders', "coinid={$coin->id} AND market='crex24'");
		//$list_text = var_export($list,true);
		//debuglog($list_text);
		foreach($list as $db_order)
		{
			$found = false;
			if(is_array($orders))
			foreach($orders as $order) {
				if(stripos($order->side, 'sell') === false) continue;
				if($order->id == $db_order->uuid) {
					$found = true;
					break;
				}
			}

			if(!$found) {
				// debuglog("crex24: delete db order {$db_order->amount} {$coin->symbol} at {$db_order->price} BTC");
				$db_order->delete();
			}
		}

		if($coin->dontsell) continue;

		$market->lasttraded = time();
		$market->save();

		// new orders
		$amount = floatval($balance->available);
		if(!$amount) continue;

		debuglog("Autotrade with $balance->currency / $amount");

		if($amount*$coin->price < $min_btc_trade) continue;

		//debuglog("min-btc-trade is passed");

		sleep(1);
		$data = crex24_api_query('orderBook', "instrument={$ticker->instrument}&limit=5");
		if(!$data) continue;

		//debuglog("is checked for orders");

		if($coin->sellonbid)
		for($i = 0; $i < 5 && $amount >= 0; $i++)
		{
			if(!isset($data->buyLevels[$i])) break;

			$nextbuy = $data->buyLevels[$i];
			if($amount*1.1 < $nextbuy->volume) break;

			$sellprice = bitcoinvaluetoa($nextbuy->price);
			$sellamount = min($amount, $nextbuy->volume);

			if($sellamount*$sellprice < $min_btc_trade) continue;

			debuglog("crex24: selling $sellamount $symbol at $sellprice");
			sleep(1);
			$params = array('instrument'=>$ticker->instrument, 'side'=>'sell', 'price'=>$sellprice, 'volume'=>$sellamount);
			$res = crex24_api_user('trading/placeOrder', '', json_encode($params));
			if(!$res) {
				debuglog("crex24 SubmitTrade err: ".json_encode($res));
				break;
			}

			$amount -= $sellamount;
		}

		if($amount <= 0) continue;

		//debuglog("sell-on-bid passed");

		if($coin->sellonbid)
			$sellprice = bitcoinvaluetoa($ticker->bid);
		else
			$sellprice = bitcoinvaluetoa($ticker->ask * $sell_ask_pct); // lowest ask price +5%
		if($amount*$sellprice < $min_btc_trade) continue;

		debuglog("crex24: selling $amount $symbol at $sellprice");

		sleep(1);
        	$params = array('instrument'=>$ticker->instrument, 'side'=>'sell', 'price'=>$sellprice, 'volume'=>$amount);
        	debuglog("crex24: selling params ".json_encode($params));
		$res = crex24_api_user('trading/placeOrder', '', json_encode($params));
		if(!$res) {
			debuglog("crex24 SubmitTrade err: ".json_encode($res));
			continue;
		}

		$db_order = new db_orders;
		$db_order->market = 'crex24';
		$db_order->coinid = $coin->id;
		$db_order->amount = $amount;
		$db_order->price = $sellprice;
		$db_order->ask = $ticker->ask;
		$db_order->bid = $ticker->bid;
		$db_order->uuid = $res->id;
		$db_order->created = time();
		$db_order->save();
	}

	$withdraw_min = exchange_get($exchange, 'withdraw_min_btc', EXCH_AUTO_WITHDRAW);
	$withdraw_fee = exchange_get($exchange, 'withdraw_fee_btc', 0.001);

	// auto withdraw
	if(is_object($savebalance))
	if(floatval($withdraw_min) > 0 && $savebalance->balance >= ($withdraw_min + $withdraw_fee))
	{
		// $btcaddr = exchange_get($exchange, 'withdraw_btc_address', YAAMP_BTCADDRESS);
		$btcaddr = YAAMP_BTCADDRESS;
		$amount = $savebalance->balance - $withdraw_fee;
		debuglog("CREX24: withdraw $amount BTC to $btcaddr");

		sleep(1);
		$params = array("currency"=>"BTC", "amount"=>$amount, "address"=>$btcaddr);
        	$res = crex24_api_user('account/withdraw', '', json_encode($params));
        	debuglog("*** crex24 withdraw result:\n".json_encode($res));
		if(is_object($res) && $res->Success)
		{
			$withdraw = new db_withdraws;
			$withdraw->market = 'crex24';
			$withdraw->address = $btcaddr;
			$withdraw->amount = $amount;
			$withdraw->time = time();
			$withdraw->uuid = $res->id;
			$withdraw->save();

			$savebalance->balance = 0;
			$savebalance->save();
		} else {
			debuglog("crex24 withdraw BTC error: ".json_encode($res));
		}
	}

}
