<?php
declare(strict_types=1);

namespace Nelson\Puppeteer\Presets;

abstract class APreset
{
	/** @var array<string|null> */
	protected array $defaultOptions = [];

	/** @var array<string|null> */
	protected array $options = [];


	/** @return array<string|null> */
	public function getOptions(): array
	{
		return array_merge($this->defaultOptions, $this->options);
	}
}
