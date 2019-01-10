<?php
if (!isset($_GLOBAL['selfinit'])) {
	die('you do not have permission to access this content');
}

class ADDRESS
{
	function __construct () {
		$this->privatekey = '';
		$this->address = '';
		$this->records = '';
	}
	function newAddress ($record=false) {
		$this->privatekey = hash('sha1', random_int(1, 99999).time().random_int(1, 99999));
		$this->address = $this->genAddress($this->privatekey);
		$this->records = $this->getRecords();

		if ($record) {
			file_put_contents('addresses/'.$this->address, json_encode($this));
		}

		return json_decode(json_encode($this), true);
	}
	function genAddress ($key) {
		$addr = hash('sha1', $key);
		$addr = '0x'.$addr;
		$addr = hash('sha1', $addr);
		$addr = hash('sha1', $addr);
		$addr = '0x'.$addr;
		$addr = substr($addr, 0, 34);

		return $addr;
	}
	function getRecords ($chain=[]) {
		global $_blockchain;

		if (empty($this->address)) {
			return;
		}

		$records = ['transactions'=>[], 'final_value'=>0, 'pending_transactions'=>[], 'pending_values'=>['in'=>0, 'out'=>0], 'total_transactions'=>[]];
		$conftransdata = [];

		if (isset($_blockchain)) {
			$chain = $_blockchain->chain;
		} else {
			$this->records = $records;
			return $records;
		}

		for ($i=0; $i < count($chain); $i++) {
			$block = $chain[$i]['data'];

			for ($j=0; $j < count($block); $j++) {
				$trans = $block[$j];

				if (isset($trans['address_to']) && isset($trans['address_from'])) {
					if ($trans['address_from'] == $this->address || $trans['address_to'] == $this->address) {
						$trans['status'] = 'complete';
						array_push($conftransdata, $trans);
					}
				}
			}
		}

		usort($conftransdata, function($val1, $val2) {
			return $val1['time'] <=> $val2['time'];
		});
		$records['transactions'] = $conftransdata;

		for ($i=0; $i < count($conftransdata); $i++) {
			$trans = $conftransdata[$i];
			$tote = floatval($trans['value']);
			if ($trans['address_to'] == $this->address) {
				$records['final_value'] += $tote;
			} elseif ($trans['address_from'] == $this->address) {
				$records['final_value'] -= $tote+floatval($trans['fee']);
			}
		}

		$pendingtranslist = [];
		$rawpendingdata = scandir('transactions');
		array_shift($rawpendingdata);
		array_shift($rawpendingdata);
		for ($i=0; $i < count($rawpendingdata); $i++) {
			$data = json_decode(file_get_contents('transactions/'.$rawpendingdata[$i]), true);

			if ($data['address_from'] == $this->address || $data['address_to'] == $this->address) {
				$data['status'] = 'pending';
				array_push($pendingtranslist, $data);
			}
		}
		usort($pendingtranslist, function($val1, $val2) {
			return $val1['time'] <=> $val2['time'];
		});
		$records['pending_transactions'] = $pendingtranslist;

		for ($i=0; $i < count($pendingtranslist); $i++) {
			if ($pendingtranslist[$i]['address_from'] == $this->address) {
				$records['pending_values']['out'] += $pendingtranslist[$i]['total'];
			} else {
				$records['pending_values']['in'] += $pendingtranslist[$i]['total'];
			}
		}

		$records['total_transactions'] = array_merge($records['transactions'], $records['pending_transactions']);
		usort($records['total_transactions'], function($val1, $val2) {
			return $val1['time'] <=> $val2['time'];
		});

		$this->records = $records;
		return $records;
	}
}

?>