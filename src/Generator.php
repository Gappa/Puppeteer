<?php
declare(strict_types=1);

namespace Nelson\Puppeteer;

use Exception;
use Nette\Http\UrlScript;
use Nette\SmartObject;
use Nette\Utils\FileSystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Nelson\Puppeteer\Presets\APreset;


interface IGeneratorFactory
{
	public function create(): Generator;
}


final class Generator
{
	use SmartObject;

	/** @var int */
	public const GENERATE_PDF = 1;

	/** @var int */
	public const GENERATE_IMAGE = 2;

	/** @var int */
	public const GENERATE_BOTH = 3;

	/** @var int */
	private $timeout;

	/** @var string */
	private $tempDir;

	/** @var array */
	private $options = [];

	/** @var array */
	private $masterCommand;

	/** @var string|null */
	private $sandbox;

	/** @var APreset|null */
	private $preset;

	/** @var string|null */
	private $tempFileName;


	public function __construct(GeneratorConfig $generatorConfig)
	{
		$this->tempDir = $generatorConfig->getTempDir();
		$this->sandbox = $generatorConfig->getSandbox();
		$this->timeout = $generatorConfig->getTimeout();
		$this->masterCommand = [
			$generatorConfig->getNodeCommand() => __DIR__ . '/assets/generator.js'
		];
	}


	public function generateFromHtml(string $html, int $mode): string
	{
		$htmlFilePath = $this->getTempFilePath('html');
		FileSystem::write($htmlFilePath, $html);

		$this->setOption('--inputMode', 'file');
		$this->setOption('--input', $htmlFilePath);

		$output = $this->generate($mode);
		FileSystem::delete($htmlFilePath);

		return $output;
	}


	public function generateFromUrl(UrlScript $url, int $mode): string
	{
		$this->setOption('--inputMode', 'url');
		$this->setOption('--input', (string)$url);
		return $this->generate($mode);
	}


	public function setPreset(?APreset $preset)
	{
		$this->preset = $preset;
	}


	public function setOption(string $name, ?string $value = null): void
	{
		$this->options[$name] = $value;
	}


	public function setOptions(array $options): void
	{
		foreach ($options as $name => $value) {
			$this->setOption($name, $value);
		}
	}


	private function getTempFileName(): string
	{
		if (empty($this->tempFileName)) {
			$this->tempFileName = time() . '_-_' . md5((string)mt_rand());
		}

		return $this->tempFileName;
	}


	private function getTempFilePath(?string $suffix = null): string
	{
		$parts = [
			$this->tempDir,
			$this->getTempFileName(),
			!empty($suffix) ? '.' . $suffix : '',
		];

		return implode('', $parts);
	}


	private function generate(int $mode): string
	{
		if ($this->preset) {
			$this->setOptions($this->preset->getOptions());
		}

		switch ($mode) {
			case self::GENERATE_PDF:
				$this->setOption('--pdf');
				break;
			case self::GENERATE_IMAGE:
				$this->setOption('--image');
				break;
			case self::GENERATE_BOTH:
				$this->setOption('--pdf');
				$this->setOption('--image');
				break;
			default:
				throw new Exception('Mode ' . $mode . ' is not defined.');
				break;
		}

		$this->setOption('--output', $this->getTempFilePath());

		if ($this->sandbox) {
			$env = $_ENV + ['CHROME_DEVEL_SANDBOX' => $this->sandbox];
		} else {
			$env = $_ENV;
		}

		$process = new Process(
			$this->getCommand(),
			null,
			$env,
			null,
			(float) $this->timeout
		);
		$process->run();

		// executes after the command finishes
		if (!$process->isSuccessful()) {
			throw new ProcessFailedException($process);
		}


		dump($this->getCommand());
		dump($process->getOutput());
		exit;

		return $this->getTempFilePath();
	}


	private function getCommand(): array
	{
		$command = [];

		foreach ($this->options as $key => $value) {
			if (!empty($value)) {
				$command[] = $key . '=' . $value;
			} else {
				$command[] = $key;
			}
		}

		array_unshift($command,
			array_keys($this->masterCommand)[0],
			array_values($this->masterCommand)[0]
		);

		return $command;
	}
}