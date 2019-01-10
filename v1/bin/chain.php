<?php
if (!isset($_GLOBAL['selfinit'])) {
	die('you do not have permission to access this content');
}

class BLOCKCHAIN
{
	function __construct () {
		$this->chain = [];
		$this->blocks = [];
		$this->addresses = [];
		$this->transactions = [];
		$this->difficulty = 1;

		// gets the current chain -> start
		if (!is_dir('blockchain')) {
			mkdir('blockchain');
		}

		$scan = scandir('blockchain');
		array_shift($scan);
		array_shift($scan);

		for ($i=0; $i < count($scan); $i++) {
			array_push($this->chain, json_decode(file_get_contents('blockchain/'.$scan[$i]), true));
		}
		usort($this->chain, function ($item1, $item2) {
		    return $item1['time'] <=> $item2['time'];
		});
		clearstatcache();
		// -> end


		// gets blocks waiting to be approved -> start
		if (!is_dir('blocks')) {
			mkdir('blocks');
		}

		$scan = scandir('blocks');
		array_shift($scan);
		array_shift($scan);

		for ($i=0; $i < count($scan); $i++) {
			array_push($this->blocks, json_decode(file_get_contents('blocks/'.$scan[$i]), true));
		}
		usort($this->blocks, function ($item1, $item2) {
		    return $item1['time'] <=> $item2['time'];
		});
		clearstatcache();
		// -> end


		// gets addresses currently on this chain -> start
		if (!is_dir('addresses')) {
			mkdir('addresses');
		}

		$scan = scandir('addresses');
		array_shift($scan);
		array_shift($scan);

		for ($i=0; $i < count($scan); $i++) {
			array_push($this->addresses, $scan[$i]);
		}
		sort($this->addresses);
		clearstatcache();
		// -> end

		// gets addresses currently stored locally on this chain -> start
		if (!is_dir('transactions')) {
			mkdir('transactions');
		}

		$scan = scandir('transactions');
		array_shift($scan);
		array_shift($scan);

		for ($i=0; $i < count($scan); $i++) {
			array_push($this->transactions, $scan[$i]);
		}
		sort($this->transactions);
		clearstatcache();
		// -> end

		// increment difficulty -> start
		for ($i=0;$i<count($this->chain);$i++) {
		    $this->difficulty += $i % 1000000 == 0 ? 1 : 0 ;
		}

		// blocks generation calculation
		$this->blocks_generate_base = number_format(100/$this->difficulty, 8, '.', '');

		// -> end
		/*global $_address;
		if (count($this->chain) == 0) {
			$this->makeGenesisBlock();
		}*/

	}
	function makeGenesisBlock() {
		global $_address;

		$addr = $_address->newAddress(true);
		$addr = json_decode(json_encode($addr));

		$transaction = new TRANSACT('genesis', 'genesis', '', 0, 0, false);

		$block = new BLOCK([$transaction], '');
		$block->hash = $this->generateHash($block);

		array_push($this->chain, $block);
		file_put_contents('blockchain/'.time(), json_encode($block));
	}
	function generateHash ($block) {
		$block = json_decode(json_encode($block));
		$hash = hash('sha256', $block->time.$block->nonce.$block->previousHash.json_encode($block->data));
		return $hash;
	}
	function validate() {
		$out = [];
		for ($i=1; $i < count($this->chain); $i++) {
			$block = json_decode(json_encode($this->chain[$i]));
			$prevblock = json_decode(json_encode($this->chain[$i-1]));

			if ($prevblock->hash == $block->previousHash) {
				$testprevblockhash = $this->generateHash($prevblock);

				if ($testprevblockhash == $prevblock->hash) {
					array_push($out, [$prevblock->hash, $block->hash]);
				} else {
					$out = 'error @ '.$block->hash.', expected '.$prevblock->previousHash.', found '.$testprevblockhash;
					break;
				}

			} else {
				$out = 'error @ '.$block->hash.', expected '.$block->previousHash.', found '.$prevblock->hash;
				break;
			}
		}
		return 'valid';
	}
}

?>