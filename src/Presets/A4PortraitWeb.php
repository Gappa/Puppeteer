<?php
declare(strict_types=1);

namespace Nelson\Puppeteer\Presets;

final class A4PortraitWeb extends APreset
{
	public function __construct()
	{
		$this->options = [
			'--pageFormat' => 'A4',
			'--viewportWidth' => '794',
			'--viewportHeight' => '1122',
		];
	}
}
