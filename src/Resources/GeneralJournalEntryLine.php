<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ExactOnlineResourceInterface;

class GeneralJournalEntryLine extends ExactOnlineResource implements ExactOnlineResourceInterface
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
