<?php
declare(strict_types=1);

namespace Nelson\Puppeteer;

interface IGeneratorFactory
{
	public function create(): Generator;
}
