<?php

$bitrexFee = 0.0025; //0.25%
$poloniexFee = 0.0025; //0.25% // Maker
$BtxBtcFee = 0.001; // btc
$PolBtcFee = 0.0005;// btc

/*
1. bittrexBuy (bitrexFee) -> bittrexWithdraw (txFee) -> poloniexSell (poloniexFee) -> poloniexWithdraw (PolBtcFee)
2. poloniexBuy (poloniexFee) -> poloniexWithdraw (txFee) -> bittrexSell (bitrexFee) -> bittrexWithdraw (BtxBtcFee)
*/

function table($data) {
 
    // Find longest string in each column
    $columns = [];
    foreach ($data as $row_key => $row) {
        foreach ($row as $cell_key => $cell) {
            $length = strlen($cell);
            if (empty($columns[$cell_key]) || $columns[$cell_key] < $length) {
                $columns[$cell_key] = $length;
            }
        }
    }
 
    // Output table, padding columns
    $table = '';
    foreach ($data as $row_key => $row) {
        foreach ($row as $cell_key => $cell)
            $table .= str_pad($cell, $columns[$cell_key]) . '   ';
        $table .= PHP_EOL;
    }
    return $table;
 
}

function getBittrexCoins(){
	$data = json_decode(file_get_contents("https://bittrex.com/api/v1.1/public/getcurrencies"));
	$result = array();
	foreach($data->result as $coin){
		//echo $coin->Currency . "\n";
		if($coin->IsActive && !$coin->Notice)
			$result[$coin->Currency] = $coin->TxFee;
		
	}
	
	return $result;
	
}


function getPoloniexCoins(){
	$data = json_decode(file_get_contents("https://poloniex.com/public?command=returnCurrencies"));
	
	$result = array();
	foreach($data as $k=>$v){
		if(!$v->disabled)
			$result[$k] = $v->txFee;
	}
	
	return $result;
}


function getPoloniexMarkets(){
	$data = json_decode(file_get_contents("https://poloniex.com/public?command=returnTicker"));
	$result = array();
	$coins = getPoloniexCoins();
	foreach($data as $k=>$v){
		$keys = explode("_", $k);
		if(!$v->isFrozen && $keys[0] == 'BTC' && isset($coins[$keys[1]]) ){
			$result[$keys[1]]['market'] = $k;
			$result[$keys[1]]['txFee'] = $coins[$keys[1]];
			$result[$keys[1]]['last'] = (float)$v->last;
			$result[$keys[1]]['ask'] = (float)$v->lowestAsk;
			$result[$keys[1]]['bid'] = (float)$v->highestBid;
			$result[$keys[1]]['volume'] = $v->baseVolume;
			$result[$keys[1]]['spred'] = $v->percentChange;
		}
	}
	
	return $result;
}

function getBittrexMarkets(){
	$data = json_decode(file_get_contents("https://bittrex.com/api/v1.1/public/getmarketsummaries"));
	$result = array();
	$coins = getBittrexCoins();
	foreach($data->result as $market){
		$name = explode('-', $market->MarketName);
		//$p = explode('-', $name);
		if($name[0] == 'BTC' && isset($coins[$name[1]])){
			$result[$name[1]]['market'] = $market->MarketName;
			$result[$name[1]]['txFee'] = $coins[$name[1]];
			$result[$name[1]]['last'] = (float)$market->Last;
			$result[$name[1]]['ask'] = (float)$market->Ask;
			$result[$name[1]]['bid'] = (float)$market->Bid;
			$result[$name[1]]['volume'] = $market->BaseVolume;
			//$result[$name[1]]['spred'] = $v->percentChange;
		}
	}
	
	return $result;	
	
}

function CheckProfit($summ, $price_1, $price_2, $comm_1, $comm_2, $fee_1, $btc_fee){
	$amount_1 = $summ/$price_1;
	$amount_1 -= $amount_1*$comm_1;
	$amount_1 -= $fee_1;
	$amount = $amount_1*$price_2;
	$amount -= $amount*$comm_2;
	$profit = $amount - $summ;
	$perc = round(100*$profit/$amount, 3);
	$amount_finish = $amount - $btc_fee;
	$profit_finish = $amount_finish - $summ;
	$perc_f = round(100*$profit_finish/$amount_finish, 3);
	return array(
		'amount' => $amount,
		'amount_finish' => $amount_finish,
		'profit' => $profit,
		'profit_finish' => $profit_finish,
		'summ' => $summ,
		'perc' => $perc,
		'perc_f' => $perc_f,
	);
}

$bittrexInfo = getBittrexMarkets();
$polniexInfo = getPoloniexMarkets();


function Bittrex2Poloniex(){
	global $bittrexInfo, $polniexInfo, $bitrexFee, $poloniexFee, $BtxBtcFee, $PolBtcFee;
	echo "Bittrex to Poloniex\n";
	$table = [['Coin', 'Polo (Bid)', 'Bittrex (Ask)', 'Spred (%)', '0.1 Profit (BTC)', '0.1 Final Profit (BTC)', '0.05 Profit (BTC)', '0.05 Final Profit (BTC)', ]];
	$table[] = ['', '', '', '', '', '', '', '', ];
	foreach($bittrexInfo as $k=>$v){
		if(isset($polniexInfo[$k])){
			$bid = $polniexInfo[$k]['bid'];
			$ask = $bittrexInfo[$k]['ask'];
			if($bid > $ask){
				$p = round(100*($bid - $ask)/$bid, 2);
				if($p > 1.5){
					
					$coms_10 = CheckProfit(0.1, $ask, $bid, $bitrexFee, $poloniexFee, $bittrexInfo[$k]['txFee'], $PolBtcFee);
					$coms_05 = CheckProfit(0.05, $ask, $bid, $bitrexFee, $poloniexFee, $bittrexInfo[$k]['txFee'], $PolBtcFee);
					
										
					$table[] = [$k, $bid, $ask, $p, $coms_10['profit'], $coms_10['profit_finish'], $coms_05['profit'], $coms_05['profit_finish']];
				}
			}
		
		}
	}
	echo table($table);
	echo "--------------\n";
}


function Poloniex2Bittrex(){
	global $bittrexInfo, $polniexInfo, $bitrexFee, $poloniexFee, $BtxBtcFee, $PolBtcFee;
	echo "Poloniex to Bittrex\n";
	$table = [['Coin', 'Bittrex', 'Polo', 'Spred', '0.1 Profit', '0.1 Final Profit', '0.05 Profit', '0.05 Final Profit', ]];
	$table[] = ['', '', '', '', '', '', '', '', ];
	foreach($bittrexInfo as $k=>$v){
		if(isset($polniexInfo[$k])){
			
			$bid = $bittrexInfo[$k]['bid'];
			$ask = $polniexInfo[$k]['ask'];
			
			if($bid > $ask){
				$p = round(100*($bid - $ask)/$bid, 2);
				if($p > 1.5){
					
					$coms_10 = CheckProfit(0.1, $ask, $bid, $poloniexFee, $bitrexFee, $polniexInfo[$k]['txFee'], $BtxBtcFee);
					$coms_05 = CheckProfit(0.05, $ask, $bid, $poloniexFee, $bitrexFee, $polniexInfo[$k]['txFee'], $BtxBtcFee);
										
					$table[] = [$k, $bid, $ask, $p, $coms_10['profit'], $coms_10['profit_finish'], $coms_05['profit'], $coms_05['profit_finish']];
					
					
				}
			}
		
		}
	}
	echo table($table);
	echo "--------------\n";
}






Bittrex2Poloniex();
Poloniex2Bittrex();
//var_dump(getBittrexMarkets());

//var_dump(getBittrexCoins());