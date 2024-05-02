<?php
declare(strict_types=1);

namespace Nelson\Puppeteer;

use Nette\SmartObject;

/**
 * @phpstan-type ConfigArray array{
 * 	tempDir: string,
 *		timeout: int,
 *		sandbox: ?string,
 *		nodeCommand: string,
 *    httpUser: ?string,
 *    httpPass: ?string,
 *	}
 */
final class GeneratorConfig
{
	use SmartObject;

	/** @var ConfigArray */
	private array $config;


	/** @param ConfigArray $config */
	public function setup(array $config): void
	{
		$this->config = $config;
	}


	public function getTempDir(): string
	{
		return $this->config['tempDir'];
	}


	public function getNodeCommand(): string
	{
		return $this->config['nodeCommand'];
	}


	public function getTimeout(): int
	{
		return $this->config['timeout'];
	}


	public function getSandbox(): ?string
	{
		return $this->config['sandbox'];
	}


	public function getHttpUser(): ?string
	{
		return $this->config['httpUser'];
	}


	public function getHttpPass(): ?string
	{
		return $this->config['httpPass'];
	}
}
