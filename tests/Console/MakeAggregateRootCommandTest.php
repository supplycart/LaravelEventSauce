<?php

declare(strict_types=1);

namespace Tests\Console;

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class MakeAggregateRootCommandTest extends TestCase
{
    public function test_it_can_generate_aggregate_root_classes_and_a_migration()
    {
        $domainDirectory = $this->app->basePath('app/Domain');

        $filesystem = $this->filesystem();
        $migrationPattern = $this->app->databasePath('migrations/*_create_registration_domain_messages_table.php');

        if ($filesystem->exists($domainDirectory)) {
            $filesystem->deleteDirectory($domainDirectory);
        }

        $filesystem->delete($filesystem->glob($migrationPattern));

        $this->artisan('make:aggregate-root', ['namespace' => 'Domain/Registration']);

        $this->assertFileExists($domainDirectory.'/Registration.php');
        $this->assertFileExists($domainDirectory.'/RegistrationId.php');
        $this->assertFileExists($domainDirectory.'/RegistrationRepository.php');
        $migrationFiles = $filesystem->glob($migrationPattern);
        $this->assertCount(1, $migrationFiles);
        $this->assertFileExists($migrationFiles[0]);

        $filesystem->delete($migrationFiles);
    }

    private function filesystem(): Filesystem
    {
        return $this->app->make(Filesystem::class);
    }
}
