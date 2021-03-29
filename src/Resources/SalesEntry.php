<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ResourceInterface;

class SalesEntry extends Resource implements ResourceInterface
{
    protected string $endpoint = 'salesentry/SalesEntries';

    /**
     * The field with the resource's primary key.
     */
    protected string $primaryKeyFieldName = 'EntryID';

    protected array $dates = [
        'Created',
        'Modified',
        'EntryDate',
        'DueDate',
    ];

    protected array $relationships = [
        'SalesEntryLines' => SalesEntryLine::class,
    ];

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
