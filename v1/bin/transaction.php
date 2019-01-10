<?php
if (!isset($_GLOBAL['selfinit'])) {
	die('you do not have permission to access this content');
}

class TRANSACT
{
	function __construct($to,$from,$key,$value,$fee=0.01,$record=true) {
		global $_blockchain;
		global $_address;

		$value = number_format(floatval($value), 8, '.', '');
		$fee = number_format(floatval($fee), 8, '.', '');

		if ($fee < 0.01) {
			$fee = 0.01000000;
		}

		if ($to == $from) {
			$this->error = 'receiver violation';
			return;
		}

		if (!isset($_address)) {
			return;
		}

		if (!isset($_blockchain)) {
			$chain = [];
		} else {
			$chain = $_blockchain->chain;
		}

		$_address->address = $from;
		$_address->privatekey = $key;
		$_address->getRecords();

		if (count($chain) > 0) {
			if (strlen($from) < 32) {
				$this->error = 'invalid sender';
				return;
			} elseif (strlen($to) < 32) {
				$this->error = 'invalid receiver';
				return;
			} elseif ($_address->genAddress($key) != $from) {
				$this->error = 'invalid key';
				return;
			} elseif ($_address->records['final_value'] < ($value+$fee)) {
				$this->error = 'invalid sender balance';
				$this->sender_balance = $_address->records['final_value'];
				return;
			} else {
				$this->guid = 'transaction';
				$this->address_to = $to;
				$this->address_from = $from;
				$this->value = number_format($value, 8, '.', '');
				$this->total = number_format(($value+$fee), 8, '.', '');
				$this->fee = number_format($fee, 8, '.', '');
				$this->time = time();
			}
		}

		$this->generateHash();

		if ($record) {
			file_put_contents('transactions/'.$this->time, json_encode($this));
		}
	}
	function generateHash () {
		$this->hash = hash('sha256', $this->time.$this->value.$this->address_to.$this->address_from);
	}
}

class BLOCKTRANSACT
{
	function __construct($from, $to, $value)
	{
		$fee = 0.0100000;
		$value = $value-$fee;

		$this->guid = 'transaction';
		$this->address_to = $to;
		$this->address_from = $from;
		$this->value = number_format($value, 8, '.', '');
		$this->total = number_format(($value+$fee), 8, '.', '');
		$this->fee = number_format($fee, 8, '.', '');
		$this->time = time();
		$this->generateHash();

		file_put_contents('transactions/'.$this->time, json_encode($this));

		$this->debug = ['status'=>'success', 'data'=>$this];

	}
	function generateHash () {
		$this->hash = hash('sha256', $this->time.$this->value.$this->address_to.$this->address_from);
	}
}
?>