<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ExactOnlineResourceInterface;

class GeneralJournalEntry extends ExactOnlineResource implements ExactOnlineResourceInterface
{
    protected string $endpoint = 'generaljournalentry/GeneralJournalEntries';

    /**
     * The field with the resource's primary key.
     */
    protected string $primaryKeyFieldName = 'EntryID';

    protected array $dates = [
        'Created',
        'Modified',
    ];

    protected array $relationships = [
        'GeneralJournalEntryLines' => GeneralJournalEntryLine::class,
    ];

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
