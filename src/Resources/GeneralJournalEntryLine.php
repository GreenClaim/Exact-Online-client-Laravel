<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ResourceInterface;

class GeneralJournalEntryLine extends Resource implements ResourceInterface
{
    protected string $endpoint = 'generaljournalentry/GeneralJournalEntryLines';

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
