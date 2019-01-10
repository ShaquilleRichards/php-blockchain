<?php
if (!isset($_GLOBAL['selfinit'])) {
	die('you do not have permission to access this content');
}
class BLOCK
{
	function __construct ($data, $previousHash, $miner='')
	{
		$this->time = time();
		$this->previousHash = $previousHash;
		$this->nonce = 0;
		$this->miner = $miner;
		$this->reward = 0;
		$this->generated = 0;
		$this->data = $data;
		$this->hash = '';
	}
}
?>