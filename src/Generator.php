<?php
declare(strict_types=1);

namespace Nelson\Puppeteer;

use Exception;
use Nelson\Puppeteer\Presets\APreset;
use Nette\Http\UrlScript;
use Nette\SmartObject;
use Nette\Utils\FileSystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class Generator
{
	use SmartObject;

	/** @var int */
	public const GENERATE_PDF = 1;

	/** @var int */
	public const GENERATE_IMAGE = 2;

	/** @var int */
	public const GENERATE_BOTH = 3;

	/** @var string */
	public const SCRIPT_PATH = __DIR__ . '/assets/generator.js';

	/** @var array<string|null> */
	private array $options = [];

	private int $timeout;
	private string $tempDir;
	private string $nodeCommand;
	private ?string $sandbox = null;
	private ?APreset $preset = null;
	private ?string $tempFileName = null;

	/**
	 * @var array{
	 * 	command: string[],
	 * 	console: string,
	 * 	pdf: ?string,
	 * 	image: ?string,
	 * }
	 */
	private array $output;


	public function __construct(GeneratorConfig $generatorConfig)
	{
		$this->tempDir = $generatorConfig->getTempDir();
		$this->sandbox = $generatorConfig->getSandbox();
		$this->timeout = $generatorConfig->getTimeout();
		$this->nodeCommand = $generatorConfig->getNodeCommand();
	}


	/**
	 * @param string $html
	 * @param int $mode
	 * @return array{
	 * 	command: string[],
	 * 	console: string,
	 * 	pdf: ?string,
	 * 	image: ?string,
	 * }
	 * @throws Exception
	 */
	public function generateFromHtml(string $html, int $mode): array
	{
		$htmlFilePath = $this->getTempFilePath('html');
		FileSystem::write($htmlFilePath, $html);

		$this->setOption('--inputMode', 'file');
		$this->setOption('--input', $htmlFilePath);
		$this->generate($mode);

		FileSystem::delete($htmlFilePath);

		return $this->output;
	}


	/**
	 * @param UrlScript $url
	 * @param int $mode
	 * @return array{
	 * 	command: string[],
	 * 	console: string,
	 * 	pdf: ?string,
	 * 	image: ?string,
	 * }
	 * @throws Exception
	 */
	public function generateFromUrl(UrlScript $url, int $mode): array
	{
		$this->setOption('--inputMode', 'url');
		$this->setOption('--input', (string) $url);
		$this->generate($mode);
		return $this->output;
	}


	public function setPreset(?APreset $preset): void
	{
		$this->preset = $preset;
	}


	public function setOption(string $name, ?string $value = null): void
	{
		$this->options[$name] = $value;
	}


	/** @param array<string|null> $options */
	public function setOptions(array $options): void
	{
		foreach ($options as $name => $value) {
			$this->setOption($name, $value);
		}
	}


	private function getTempFileName(): string
	{
		if (empty($this->tempFileName)) {
			$this->tempFileName = time() . '_-_' . md5((string) mt_rand());
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


	private function generate(int $mode): void
	{
		if ($this->preset) {
			$this->setOptions($this->preset->getOptions());
		}

		switch ($mode) {
			case self::GENERATE_PDF:
				$this->outputPdf();
				break;
			case self::GENERATE_IMAGE:
				$this->outputImage();
				break;
			case self::GENERATE_BOTH:
				$this->outputPdf();
				$this->outputImage();
				break;
			default:
				throw new Exception('Mode ' . $mode . ' is not defined.');
		}

		$this->setOption('--output', $this->getTempFilePath());

		if ($this->sandbox) {
			$env = $_ENV + ['CHROME_DEVEL_SANDBOX' => $this->sandbox];
		} else {
			$env = $_ENV;
			$this->setOption('--no-sandbox');
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

		$this->output['command'] = $this->getCommand();
		$this->output['console'] = $process->getOutput();

		$process->stop();
	}


	private function outputPdf(): void
	{
		$this->setOption('--pdf');
		$this->output['pdf'] = $this->getTempFilePath() . '.pdf';
	}


	private function outputImage(): void
	{
		$this->setOption('--image');
		$this->output['image'] = $this->getTempFilePath() . '.png';
	}


	/**
	 * @return array<string>
	 */
	private function getCommand(): array
	{
		$command = [];

		foreach ($this->options as $key => $value) {
			if (!empty($value)) {
				$command[] = (string) ($key . '=' . $value);
			} else {
				$command[] = (string) $key;
			}
		}

		array_unshift($command, $this->nodeCommand, self::SCRIPT_PATH);

		return $command;
	}
}
