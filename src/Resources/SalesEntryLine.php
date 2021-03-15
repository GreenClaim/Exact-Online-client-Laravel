<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ResourceInterface;

class SalesEntryLine extends Resource implements ResourceInterface
{
    protected string $endpoint = 'salesentry/SalesEntryLines';

    protected array $dates = [
        'Created',
        'Modified',
        'Date',
    ];

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
