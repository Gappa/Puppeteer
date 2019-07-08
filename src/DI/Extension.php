<?php
declare(strict_types=1);

namespace Nelson\Puppeteer\DI;

use Nette\DI\CompilerExtension;
use Nelson\Puppeteer\GeneratorConfig;
use Nelson\Puppeteer\IGeneratorFactory;

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
