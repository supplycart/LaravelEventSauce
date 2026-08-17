<?php

declare(strict_types=1);

namespace EventSauce\LaravelEventSauce;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\OffsetCursor;
use EventSauce\EventSourcing\PaginationCursor;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use Generator;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\Uuid;

final class LaravelMessageRepository implements MessageRepository
{
    private string $connection;

    private string $table;

    public function __construct(private DatabaseManager $database, private MessageSerializer $serializer, Config $config)
    {
        $this->connection = (string) $config->get('eventsauce.connection');
        $this->table = (string) $config->get('eventsauce.table');
    }

    public function persist(Message ...$messages): void
    {
        $connection = $this->connection();

        collect($messages)->map(fn(Message $message) => $this->serializer->serializeMessage($message))->each(function (array $message) use ($connection) {
            $headers = $message['headers'];

            $connection->table($this->table)->insert([
                'event_id' => $headers[Header::EVENT_ID] ?? Uuid::uuid4()->toString(),
                'event_type' => $headers[Header::EVENT_TYPE],
                'event_stream' => $headers[Header::AGGREGATE_ROOT_ID] ?? null,
                'recorded_at' => $headers[Header::TIME_OF_RECORDING],
                'payload' => json_encode($message),
            ]);
        });
    }

    public function retrieveAll(AggregateRootId $id): Generator
    {
        $payloads = $this->connection()->table($this->table)
            ->where('event_stream', $id->toString())
            ->orderBy('recorded_at')
            ->get('payload');

        $lastVersion = 0;
        $messageCount = 0;

        foreach ($payloads as $payload) {
            $messages = $this->serializer->unserializePayload(json_decode($payload->payload, true));
            $messages = $messages instanceof Message ? [$messages] : $messages;

            foreach ($messages as $message) {
                yield $message;
                $messageCount++;
                $lastVersion = max($lastVersion, (int) $message->header(Header::AGGREGATE_ROOT_VERSION));
            }
        }

        return $lastVersion ?: $messageCount;
    }

    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        $payloads = $this->connection()->table($this->table)
            ->where('event_stream', $id->toString())
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get('payload');

        $lastVersion = 0;

        foreach ($payloads as $payload) {
            $messages = $this->serializer->unserializePayload(json_decode($payload->payload, true));
            $messages = $messages instanceof Message ? [$messages] : $messages;

            foreach ($messages as $message) {
                $version = (int) $message->header(Header::AGGREGATE_ROOT_VERSION);

                if ($version > $aggregateRootVersion) {
                    yield $message;
                    $lastVersion = $version;
                }
            }
        }

        return $lastVersion;
    }

    public function paginate(PaginationCursor $cursor): Generator
    {
        if (!$cursor instanceof OffsetCursor) {
            throw new \InvalidArgumentException('Cursor must be an instance of OffsetCursor.');
        }

        $payloads = $this->connection()->table($this->table)
            ->orderBy('id')
            ->offset($cursor->offset())
            ->limit($cursor->limit())
            ->get('payload');

        foreach ($payloads as $payload) {
            $messages = $this->serializer->unserializePayload(json_decode($payload->payload, true));

            if ($messages instanceof Message) {
                yield $messages;
            } else {
                yield from $messages;
            }
        }

        return $cursor->plusOffset($payloads->count());
    }

    private function connection(): ConnectionInterface
    {
        return $this->database->connection($this->connection);
    }

    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
    }
}
