<?php

namespace App\Core\Middleware;

use InvalidArgumentException;
use JsonException;
use Lsr\Core\Routing\Middleware;
use Lsr\Interfaces\RequestInterface;

class IPCheck implements Middleware
{

	private int $ipFrom;
	private int $ipTo;

	/**
	 * @param string $ipRange Valid IPv4 IP range - single IP, IP-IP, IP/mask
	 */
	public function __construct(
		string $ipRange
	) {
		// Parse IP range to "from" "to" addresses

		// Only one address
		if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $ipRange) > 0) {
			$ip = ip2long($ipRange);
			if ($ip === false) {
				throw new InvalidArgumentException('Invalid IP address');
			}
			$this->ipFrom = $ip;
			$this->ipTo = $this->ipFrom;
			return;
		}
		if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})-(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/', $ipRange, $matches) > 0) {
			$ip = ip2long($matches[1]);
			if ($ip === false) {
				throw new InvalidArgumentException('Invalid IP address');
			}
			$this->ipFrom = $ip;
			$ip = ip2long($matches[2]);
			if ($ip === false) {
				throw new InvalidArgumentException('Invalid IP address');
			}
			$this->ipTo = $ip;
			return;
		}
		if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/(\d{1,3})$/', $ipRange, $matches) > 0) {
			$mask = (~((1 << (32 - ((int) $matches[2]))) - 1)); // Binary mask - (32 - mask) LSB '0'
			$ip = ip2long($matches[1]);
			if ($ip === false) {
				throw new InvalidArgumentException('Invalid IP address');
			}
			$subnet = $ip & $mask; // Masked subnet
			$this->ipFrom = $subnet;
			// Maximum available IP in the subnet
			$this->ipTo = $this->ipFrom + (~$mask);
		}
	}

	/**
	 * @inheritDoc
	 * @throws JsonException
	 */
	public function handle(RequestInterface $request) : bool {
		$ip = $_SERVER['REMOTE_ADDR'];
		if (empty($ip) || !isset($this->ipFrom, $this->ipTo) || ($ip = ip2long($ip)) === false) {
			http_response_code(403);
			echo 'Blocked IP';
			exit(0);
		}

		if ($ip < $this->ipFrom || $ip > $this->ipTo) {
			http_response_code(403);
			echo 'Blocked IP';
			exit(0);
		}
		return true;
	}
}