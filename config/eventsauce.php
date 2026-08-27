<?php

return [

    /*
     * The default database connection name, used to store messages.
     * When null is provided it'll use the default application connection.
     */

    'connection' => env('EVENTSAUCE_CONNECTION'),

    /*
     * The default database table name, used to store messages.
     */

    'table' => env('EVENTSAUCE_TABLE', 'domain_messages'),

    /*
     * Snapshots may use a separate database connection and table. When the
     * connection is null, the regular EventSauce connection is used.
     */

    'snapshot_connection' => env('EVENTSAUCE_SNAPSHOT_CONNECTION'),

    'snapshot_table' => env('EVENTSAUCE_SNAPSHOT_TABLE', 'domain_snapshots'),

    'snapshot_state_serializer' => EventSauce\LaravelEventSauce\Snapshotting\NativeSnapshotStateSerializer::class,

    /*
     * Here you specify all of your aggregate root repositories.
     * We'll use this info to generate commands and events.
     *
     * More info on code generation here:
     * https://eventsauce.io/docs/event-sourcing/create-events-and-commands
     */

    'repositories' => [
        // App\Domain\MyAggregateRoot\MyAggregateRootRepository::class,
    ],

];
