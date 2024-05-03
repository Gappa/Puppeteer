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

/**
 * @phpstan-type OutputArray array{
 * 	command: string[],
 * 	console: string,
 * 	pdf: ?string,
 * 	image: ?string,
 *	 }
 */
final class Generator
{
	use SmartObject;

	public const int GENERATE_PDF = 1;
	public const int GENERATE_IMAGE = 2;
	public const int GENERATE_BOTH = 3;
	public const string SCRIPT_PATH = __DIR__ . '/assets/generator.js';

	/** @var array<string|null> */
	private array $options = [];

	private int $timeout;
	private string $tempDir;
	private string $nodeCommand;
	private ?string $sandbox = null;
	private ?APreset $preset = null;
	private ?string $tempFileName = null;
	private ?string $httpUser = null;
	private ?string $httpPass = null;

	/** @var OutputArray */
	private array $output;


	public function __construct(GeneratorConfig $generatorConfig)
	{
		$this->tempDir = $generatorConfig->getTempDir();
		$this->sandbox = $generatorConfig->getSandbox();
		$this->timeout = $generatorConfig->getTimeout();
		$this->httpUser = $generatorConfig->getHttpUser();
		$this->httpPass = $generatorConfig->getHttpPass();
		$this->nodeCommand = $generatorConfig->getNodeCommand();
	}


	/**
	 * @param string $html
	 * @param int $mode
	 * @return OutputArray
	 * @throws Exception
	 */
	public function generateFromHtml(string $html, int $mode): array
	{
		$htmlFilePath = $this->getTempFilePath('.html');
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
	 * @return OutputArray
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
		if ($this->tempFileName === null || trim($this->tempFileName) === '') {
			$this->tempFileName = time() . '_-_' . md5((string) mt_rand());
		}

		return $this->tempFileName;
	}


	private function getTempFilePath(?string $suffix = null): string
	{
		$suffix = match(true) {
			is_null($suffix), trim($suffix) === '' => '',
			default => $suffix,
		};

		$parts = [
			$this->tempDir,
			$this->getTempFileName(),
			$suffix,
		];

		return implode('', $parts);
	}


	private function generate(int $mode): void
	{
		if ($this->preset !== null) {
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

		$this->setOption('--httpUser', $this->httpUser);
		$this->setOption('--httpPass', $this->httpPass);

		if ($this->sandbox !== null) {
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


	/** @return array<int, string> */
	private function getCommand(): array
	{
		$command = [];

		foreach ($this->options as $key => $value) {
			$command[] = match(true) {
				is_null($value), trim($value) === '' => (string) $key,
				default => $key . '=' . $value,
			};
		}

		array_unshift($command, $this->nodeCommand, self::SCRIPT_PATH);

		return $command;
	}
}
