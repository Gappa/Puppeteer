<?php
declare(strict_types=1);

namespace Nelson\Puppeteer\Presets;

abstract class APreset
{
	/** @var array */
	protected $defaultOptions = [];

	/** @var array */
	protected $options = [];


	/**
	 * Array of merged options
	 */
	public function getOptions(): array
	{
		return array_merge($this->defaultOptions, $this->options);
	}
}
