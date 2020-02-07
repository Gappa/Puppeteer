<?php
declare(strict_types=1);

namespace Nelson\Puppeteer\Presets;

abstract class APreset
{
	/** @var string[] */
	protected $defaultOptions = [];

	/** @var string[] */
	protected $options = [];


	/**
	 * @return string[]
	 */
	public function getOptions(): array
	{
		return array_merge($this->defaultOptions, $this->options);
	}
}
