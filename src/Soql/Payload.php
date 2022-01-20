<?php

declare(strict_types=1);

namespace Codelicia\Soql;

use GuzzleHttp\Exception\ClientException;

use function array_map as map;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class Payload
{
    private bool $success;

    private int $totalSize;

    /** @var mixed[][] */
    private array $values;

    private ?string $errorMessage;

    private ?string $errorCode;

    /** @param mixed[] $values */
    public function __construct(
        bool $success,
        int $totalSize,
        array $values,
        ?string $errorMessage = null,
        ?string $errorCode = null
    ) {
        $this->success      = $success;
        $this->totalSize    = $totalSize;
        $this->values       = $values;
        $this->errorMessage = $errorMessage;
        $this->errorCode    = $errorCode;
    }

    /** @param mixed[] $values */
    public static function withValues(iterable $values): self
    {
        return new self(
            $values['done'],
            $values['totalSize'],
            map([self::class, 'removeRecordMetadata'], $values['records'])
        );
    }

    public static function fromClientException(ClientException $exception): self
    {
        $responseContent = $exception->getResponse()->getBody()->getContents();
        $firstError      = json_decode($responseContent, true, 512, JSON_THROW_ON_ERROR)[0];

        return new self(
            false,
            0,
            [],
            $firstError['message'],
            $firstError['errorCode']
        );
    }

    /** @param mixed[] $values */
    public static function withErrors(iterable $values): self
    {
        return new self(
            false,
            0,
            [],
            $values['message'],
            $values['errorCode']
        );
    }

    /**
     * @param mixed $row
     *
     * @return mixed
     */
    private static function removeRecordMetadata($row)
    {
        if (! is_array($row)) {
            return $row;
        }

        unset($row['attributes']);

        return $row;
    }

    public function totalSize(): int
    {
        return $this->totalSize;
    }

    /** @return mixed[][] */
    public function getResults(): array
    {
        return $this->values;
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
