<?php
declare(strict_types=1);

namespace Nelson\Puppeteer;

use Nette\SmartObject;

final class GeneratorConfig
{
	use SmartObject;

	/** @var array */
	private $config;


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

}
