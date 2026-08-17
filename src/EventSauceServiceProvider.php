<?php

declare(strict_types=1);

namespace EventSauce\LaravelEventSauce;

use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\LaravelEventSauce\Console\GenerateCommand;
use EventSauce\LaravelEventSauce\Console\MakeAggregateRootCommand;
use EventSauce\LaravelEventSauce\Console\MakeConsumerCommand;
use EventSauce\LaravelEventSauce\Snapshotting\SnapshotStateSerializer;
use Illuminate\Support\ServiceProvider;

final class EventSauceServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/eventsauce.php' => $this->app->configPath('eventsauce.php'),
            ], ['eventsauce', 'eventsauce-config']);

            $this->publishes([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], ['eventsauce', 'eventsauce-migrations']);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/eventsauce.php', 'eventsauce');

        $this->commands([
            GenerateCommand::class,
            MakeAggregateRootCommand::class,
            MakeConsumerCommand::class,
        ]);

        $this->app->bind(MessageSerializer::class, fn($app) => $app->make(ConstructingMessageSerializer::class));

        $this->app->bind(MessageDecorator::class, fn($app) => $app->make(DefaultHeadersDecorator::class));

        $this->app->bind(SnapshotStateSerializer::class, function ($app) {
            return $app->make($app['config']->get('eventsauce.snapshot_state_serializer'));
        });
    }

    public function provides()
    {
        return [
            GenerateCommand::class,
            MakeAggregateRootCommand::class,
            MakeConsumerCommand::class,
        ];
    }
}
