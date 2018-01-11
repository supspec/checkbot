<?php

$bitrexFee = 0.25; //%
$poloniexFee = 0.25; //% // Maker
$BtxBtcFee = 0.001; // btc
$PolBtcFee = 0.0005;// btc

/*
1. bittrexBuy (bitrexFee) -> bittrexWithdraw (txFee) -> poloniexSell (poloniexFee) -> poloniexWithdraw (PolBtcFee)
2. poloniexBuy (poloniexFee) -> poloniexWithdraw (txFee) -> bittrexSell (bitrexFee) -> bittrexWithdraw (BtxBtcFee)
*/


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
		$keys = split("_", $k);
		if(!$v->isFrozen && $keys[0] == 'BTC' && isset($coins[$keys[1]]) ){
			$result[$keys[1]]['market'] = $k;
			$result[$keys[1]]['txFee'] = $coins[$keys[1]];
			$result[$keys[1]]['last'] = $v->last;
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
		$name = split('-', $market->MarketName);
		//$p = split('-', $name);
		if($name[0] == 'BTC' && isset($coins[$name[1]])){
			$result[$name[1]]['market'] = $market->MarketName;
			$result[$name[1]]['txFee'] = $coins[$name[1]];
			$result[$name[1]]['last'] = $market->Last;
			$result[$name[1]]['volume'] = $market->BaseVolume;
			//$result[$name[1]]['spred'] = $v->percentChange;
		}
	}
	
	return $result;	
	
}


var_dump(getBittrexMarkets());

//var_dump(getBittrexCoins());