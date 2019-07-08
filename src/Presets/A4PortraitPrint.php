<?php
declare(strict_types=1);

namespace Nelson\Puppeteer\Presets;

final class A4PortraitPrint extends APreset
{

	/**
	 * Margin bleed, in mm
	 * @var int
	 */
	private $bleed = 5;


	public function __construct()
	{
		$this->options = [
			'--pageWidth' => 210 + (2 * $this->bleed),
			'--pageHeight' => 297 + (2 * $this->bleed),
			'--viewportWidth' => 794,
			'--viewportHeight' => 1122,
		];
	}
}
