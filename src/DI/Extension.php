<?php
declare(strict_types=1);

namespace Nelson\Puppeteer\DI;

use Nelson\Puppeteer\GeneratorConfig;
use Nelson\Puppeteer\IGeneratorFactory;
use Nette\DI\CompilerExtension;

final class Extension extends CompilerExtension
{

	/** @var array */
	private $defaults = [
		'tempDir' => '%tempDir%/puppeteer/',
		'timeout' => 120,
		'sandbox' => null,
		'nodeCommand' => 'node',
	];


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$config = $this->validateConfig(
			$this->getContainerBuilder()->expand($this->defaults),
			$this->config
		);

		$builder->addDefinition($this->prefix('config'))
			->setType(GeneratorConfig::class)
			->addSetup('setup', [$config]);

		// Add factory
		$builder->addDefinition($this->prefix('generator'))
			->setImplement(IGeneratorFactory::class);
	}
}
