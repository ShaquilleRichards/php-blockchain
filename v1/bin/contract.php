<?php

class CONTRACT
{
	function __construct($value, $address, $subtype, $fee=0.01)
	{
		/*
		contracts should either store text documents for legal representation,
		or a self script/program that can fulfill basic functions and
		send returned data to a predefined url in the contract
		*/

		$this->data = [];
		$this->data['guid'] = 'contract';
		$this->data['fee'] = $fee;

		if (base64_decode($value, true)) {
			$this->data['value'] = json_decode(base64_decode($value), true);
		} else {
			$this->data['value'] = $value;
		}

		$this->data['value'] =  str_replace("\n", "\\n", $this->data['value']);

		$this->data['subtype'] = $subtype;
		$this->data['address'] = $address;
		$this->data['time'] = time();
		$this->generateHash();

		if (strlen($address) < 32 || strpos($address, '0x') === false) {
			$this->status = 'fail';
			$this->error = 'invalid address';
			return;
		}
		if ($subtype != 'document' && $subtype != 'script') {
			$this->status = 'fail';
			$this->error = 'invalid contract subtype';
			return;
		}
		if (floatval($fee) < 0.01) {
			$this->status = 'fail';
			$this->error = 'invalid fee';
			return;
		}

		file_put_contents('contracts/'.$this->data['hash'], json_encode($this->data));
		$this->status = 'success';
		$this->error = null;
	}
	function generateHash () {
		$this->data['hash'] = hash('sha256', $this->data['time'].json_encode($this->data['value']).$this->data['address']);
	}
}
?>