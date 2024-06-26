<?php

declare(strict_types=1);

namespace EventSauce\LaravelEventSauce\Console;

use function class_exists;
use EventSauce\EventSourcing\CodeGeneration\CodeDumper;
use EventSauce\EventSourcing\CodeGeneration\YamlDefinitionLoader;
use EventSauce\LaravelEventSauce\Exceptions\CodeGenerationFailed;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class GenerateCommand extends Command
{
    protected $signature = 'eventsauce:generate';

    protected $description = 'Generate commands and events for aggregate roots.';

    public function __construct(protected Filesystem $filesystem)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        if (! class_exists(CodeDumper::class)) {
            throw CodeGenerationFailed::codeGenerationNotInstalled();
        }

        $this->info('Start generating code...');

        collect(config('eventsauce.repositories', []))
            ->reject(fn(string $repository) => $repository::inputFile() === '')
            ->each(function (string $repository) {
                $this->generateCode($repository::inputFile(), $repository::outputFile());
            });

        $this->info('All done!');
    }

    private function generateCode(string $inputFile, string $outputFile): void
    {
        $this->assertFileExists($inputFile);

        $loadedYamlContent = (new YamlDefinitionLoader())->load($inputFile);
        $phpCode = (new CodeDumper())->dump($loadedYamlContent);

        $this->filesystem->put($outputFile, $phpCode);

        $this->warn("Written code to `{$outputFile}`");
    }

    private function assertFileExists(string $file): void
    {
        if (! file_exists($file)) {
            throw CodeGenerationFailed::definitionFileDoesNotExist($file);
        }
    }
}
