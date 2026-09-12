<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue;

use JsonException;

final class JobSerializer
{
    /**
     * @throws JsonException
     */
    public static function encode(ShouldQueue $job): string
    {
        if (!$job instanceof SerializableJob) {
            throw new \InvalidArgumentException('Persistent jobs must implement SerializableJob.');
        }
        return json_encode(
            ['version' => 1, 'class' => $job::class, 'payload' => $job->toPayload()],
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @param list<class-string<SerializableJob>> $allowedClasses
     * @throws JsonException
     */
    public static function decode(string $json, array $allowedClasses): SerializableJob
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (
            !is_array($data) || ($data['version'] ?? null) !== 1
            || !is_string($data['class'] ?? null) || !in_array($data['class'], $allowedClasses, true)
            || !is_subclass_of($data['class'], SerializableJob::class) || !is_array($data['payload'] ?? null)
        ) {
            throw new \UnexpectedValueException('Invalid or unregistered queue job payload.');
        }
        return $data['class']::fromPayload($data['payload']);
    }
}
