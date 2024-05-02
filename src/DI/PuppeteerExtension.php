<?php
declare(strict_types=1);

namespace Nelson\Puppeteer\DI;

use Nelson\Puppeteer\GeneratorConfig;
use Nelson\Puppeteer\Generator;
use Nelson\Puppeteer\IGeneratorFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class PuppeteerExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'tempDir' => Expect::string()->default('%tempDir%/puppeteer/'),
			'timeout' => Expect::int()->default(120)->min(10)->max(999),
			'sandbox' => Expect::string()->nullable()->default(null),
			'nodeCommand' => Expect::string()->default('node'),
			'user' => Expect::string()->nullable()->default(null),
			'pass' => Expect::string()->nullable()->default(null),
		]);
	}


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$params = $this->getContainerBuilder()->parameters;
		$config = Helpers::expand((array) $this->getConfig(), $params);

		$builder->addDefinition($this->prefix('config'))
			->setType(GeneratorConfig::class)
			->addSetup('setup', [$config]);

		// Add factory
		$builder->addFactoryDefinition($this->prefix('generator'))
			->setImplement(IGeneratorFactory::class)
			->getResultDefinition()
			->setFactory(Generator::class);
	}
}
