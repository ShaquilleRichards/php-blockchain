<?php
header("Access-Control-Allow-Origin: *");

$_GLOBAL['selfinit'] = 1;
ini_set('memory_limit', '16M');
set_time_limit(0);

require 'address.php';
require 'contract.php';
require 'transaction.php';
require 'block.php';
require 'chain.php';

$_address = new ADDRESS;
$_blockchain = new BLOCKCHAIN;

if (!isset($_REQUEST['k1']) || $_REQUEST['k1'] == 'bin' || $_REQUEST['k1'] == 'run.php') {
	unset($_REQUEST['k1']);
}
if (!isset($_REQUEST['k2']) || $_REQUEST['k2'] == 'bin' || $_REQUEST['k2'] == 'run.php') {
	unset($_REQUEST['k2']);
}

$act = isset($_REQUEST['k1']) ? $_REQUEST['k1'] : '';
$act = str_replace('/', '', $act);

function getchainlist ($chain, $offset=0, $limit=0, $hash='') {
	$length = count($chain);
	$list = ['chain_length'=>$length, 'block_list'=>[]];
	$limit = (intval($limit) == 0) ? $length : intval($limit);
	for ($i=$offset; $i < $length; $i++) {
		if (count($list['block_list']) < $limit) {
			$key = $length-$i-1;
			$conf = $length-$key-1;
			$block = $chain[$key];
			$height = $length-$i;
			$size = strlen(json_encode($block));
			if ($hash == '') {
				array_push($list['block_list'], ['confirmations'=>$conf, 'height'=>$height, 'size'=>$size, 'block'=>$block]);
			} elseif ($hash == $block['hash']) {
				array_push($list['block_list'], ['confirmations'=>$conf, 'height'=>$height, 'size'=>$size, 'block'=>$block]);
				break;
			}
		} else {
			break;
		}
	}
	return $list;
}
function getTransactionDetails ($chain, $hash) {
	for ($i=0; $i < count($chain['block_list']); $i++) {
		for ($j=0; $j < count($chain['block_list'][$i]['data']); $j++) {
			if ($chain['block_list'][$i]['data'][$j]['hash'] == $hash) {

				$data = ['transaction'=>$chain['block_list'][$i]['data'][$j], 'block'=>$data[$i]];

				return $data;
				break;
			}
		}
	}
}

if ($act == 'chain') {
	$chain = $_blockchain->chain;
	$len = count($_blockchain->chain);
	$sortdata = ['chain_length'=>$len, 'block_list'=>[]];

	if (isset($_REQUEST['k2']) && isset($_REQUEST['k3'])) {
		$act2 = $_REQUEST['k2'];
		$val = $_REQUEST['k3'];
		$val2 = isset($_REQUEST['k4']) ? intval($_REQUEST['k4']) : 0;
		if ($act2 == 'hash') {
			echo json_encode(getchainlist($chain, 0, 0, $val));
		} elseif ($act2 == 'limit') {
			$val = intval($_REQUEST['k3']);
			echo json_encode(getchainlist($chain, $val2, $val));
		}
	} else {
		echo json_encode(getchainlist($chain));
	}
} elseif ($act == 'blocks') {
	echo json_encode($_blockchain->blocks);
} elseif ($act == 'address') {
	$_address->newAddress();
	echo json_encode($_address);
} elseif ($act == 'balance') {
	$address = isset($_REQUEST['k2']) ? $_REQUEST['k2'] : '';

	if ($address) {
		$_address->address = $address;
		$_address->getRecords();
		$_address->records['address'] = $address;
		echo json_encode($_address->records);
	}
} elseif ($act == 'fetch') {
	$senders = [];
	$scan = scandir('transactions');
	array_shift($scan);
	array_shift($scan);

	for ($i=0; $i < count($scan); $i++) {
		$scan[$i] = json_decode(file_get_contents('transactions/'.$scan[$i]), true);
		if (!isset($senders[$scan[$i]['address_from']])) {
			$senders[$scan[$i]['address_from']] = $scan[$i];
		}
	}

	$transactions = [];
	foreach ($senders as $key => $value) {
		array_push($transactions, $value);
	}


	$contracts = scandir('contracts');
	array_shift($contracts);
	array_shift($contracts);

	for ($i=0; $i < count($contracts); $i++) {
		$contracts[$i] = json_decode(file_get_contents('contracts/'.$contracts[$i]), true);
	}

	echo json_encode(array_merge($transactions, $contracts));
} elseif ($act == 'contract') {
	$address = isset($_REQUEST['k2']) ? $_REQUEST['k2'] : '';
	$subtype = isset($_REQUEST['k3']) ? $_REQUEST['k3'] : '';
	$value = isset($_REQUEST['k4']) ? $_REQUEST['k4'] : '';
	$fee = isset($_REQUEST['k5']) ? $_REQUEST['k5'] : 0.01;

	$contract = new CONTRACT($value, $address, $subtype, $fee);
	echo json_encode($contract);
} elseif ($act == 'send') {
	$address_1 = isset($_REQUEST['k2']) ? $_REQUEST['k2'] : '';
	$address_2 = isset($_REQUEST['k3']) ? $_REQUEST['k3'] : '';
	$value = isset($_REQUEST['k4']) ? $_REQUEST['k4'] : '';
	$key = isset($_REQUEST['k5']) ? $_REQUEST['k5'] : '';
	$fee = isset($_REQUEST['k6']) ? $_REQUEST['k6'] : '';

	$value = floatval(base64_decode($value)) ? base64_decode($value) : $value;

	if ($address_1 && $address_2 &&  $value &&  $key) {
		$trans = new TRANSACT($address_2, $address_1, $key, $value, $fee);
		echo json_encode($trans);
	} else {
		echo json_encode([
			'error'=> 'one or more fields missing.',
			'data'=> [
				'sender'=>$address_1,
				'key'=>$key,
				'receiver'=>$address_2,
				'value'=>$value
			]
		]);
	}
} elseif ($act == 'validate') {
	$val = $_blockchain->validate();
	echo json_encode(['status'=>$val]);
} elseif ($act == 'difficulty') {
	$val = $_blockchain->difficulty;
	echo json_encode(['difficulty'=>$val]);
} elseif ($act == 'update') {
	function calcReward($data) {
		if (is_array($data)) {
			$reward = 0;
			for ($i=0; $i < count($data); $i++) {
				if (!is_array($data[$i])) {
					$t = json_decode($data[$i], true);
					if (json_last_error() == JSON_ERROR_NONE) {
						$data[$i] = $t;
					}
				} elseif (isset($data[$i]['fee'])) {
					$reward = floatval($data[$i]['fee']);
				}
			}
			return $reward;
		}
		return 0;
	}
	function get_contracts () {
		if (is_dir('contracts')) {
			$contracts = scandir('contracts');
			array_shift($contracts);
			array_shift($contracts);

			for ($i=0; $i < count($contracts); $i++) {
				$c = $contracts[$i];
				$cf = file_get_contents('contracts/'.$c);
				$cj = json_decode($cf, true);

				$contracts[$i] = $cj;
			}

			return $contracts;
		} else {
			return [];
		}
	}
	function clearContracts () {
		$contracts = scandir('contracts');
		array_shift($contracts);
		array_shift($contracts);

		for ($i=0; $i < count($contracts); $i++) {
			unlink('contracts/'.$contracts[$i]);
		}
	}

	$contracts = get_contracts();
	$generated = $_blockchain->blocks_generate_base;
	$reward = calcReward($contracts);
	$reward += $generated;

	$generated = number_format($generated, 8, '.', '');
	$reward = number_format($reward, 8, '.', '');

	if (is_array($_blockchain->blocks) && count($_blockchain->blocks)) {
		$block = array_pop($_blockchain->blocks);

		if (is_array($block)) {
			file_put_contents('blockchain/'.$block['time'], json_encode($block));

			for ($i=0; $i < count($block['data']); $i++) {
				$trans = $block['data'][$i];
				if (file_exists('transactions/'.$trans['time'])) {
					unlink('transactions/'.$trans['time']);
				}
				if (file_exists('contracts/'.$trans['hash'])) {
					unlink('contracts/'.$trans['hash']);
				}
			}

			$blocksc = scandir('blocks');
			array_shift($blocksc);
			array_shift($blocksc);

			for ($i=0; $i < count($blocksc); $i++) {
				unlink('blocks/'.$blocksc[$i]);
			}


			$blockrewardtransaction = new BLOCKTRANSACT($block['hash'], $block['miner'], $block['reward']);
			echo json_encode(['status'=>'success', 'block'=>$block]);
		} else {
			echo json_encode(['status'=>'fail', 'block'=>$block]);
		}
	} elseif (count($_blockchain->chain) == 0) {
		$block = new BLOCK($contracts,'','');
		$block->generated = $generated;
		$block->reward = $reward;
		$block->hash = $_blockchain->generateHash($block);
		file_put_contents('blockchain/'.$block->time, json_encode($block));
		echo json_encode(['status'=>'success', 'block'=>$block]);
		clearContracts();
	} else {
		$prevHash = array_pop($_blockchain->chain);
		$block = new BLOCK($contracts,$prevHash['hash'],'');
		$block->generated = $generated;
		$block->reward = $reward;
		$block->hash = $_blockchain->generateHash($block);
		file_put_contents('blockchain/'.$block->time, json_encode($block));
		echo json_encode(['status'=>'success', 'block'=>$block]);
		clearContracts();
	}
} elseif ($act == 'sblock' && isset($_REQUEST['k2'])) {
	$blockRaw = base64_decode($_REQUEST['k2']);
	$block = json_decode($blockRaw);

	if (is_object($block)) {
		$checkhash = $_blockchain->generateHash($block);
		$lasthash = $_blockchain->chain[count($_blockchain->chain)-1]['hash'];

		if (is_object($block) && property_exists($block, 'time')) {
			$blockexists = file_exists('blocks/'.$block->time);
		} else {
			$blockexists = true;
		}
		$_block_g = json_decode(json_encode($block), true);
		$reward = 0;

		for ($i=0; $i < count($_block_g['data']); $i++) {
			if (isset($_block_g['data'][$i]['fee'])) {
				$reward += floatval($_block_g['data'][$i]['fee']);
			}
		}

		$generated = $_blockchain->blocks_generate_base;
		$reward += $generated;

		$block->reward = number_format($reward, 8, '.', '');
		$block->generated = number_format($generated, 8, '.', '');

		if (!$blockexists && $checkhash===$block->hash && $lasthash===$block->previousHash && count($block->data)) {
			file_put_contents('blocks/'.$block->time, json_encode($block));
			echo json_encode(['status'=>'success', 'data'=>'block submitted to validation que.', 'block'=>$block]);
		} else {
			echo json_encode(['status'=>'fail', 'data'=>'block did not pass initial validation check.', ['chash'=>$checkhash, 'lhash'=>$lasthash, 'block'=>$block]]);
		}
	} else  {
		echo json_encode(['block_raw'=>$blockRaw, 'block'=>$block, 'data_sent'=>$_REQUEST]);
	}
} else {
	// echo json_encode($_REQUEST);
}

?>