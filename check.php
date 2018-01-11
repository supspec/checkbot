<?php

$bitrexFee = 0.25;
$poloniexFee = 0.25; // Maker

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

var_dump(getPoloniexCoins());

//var_dump(getBittrexCoins());