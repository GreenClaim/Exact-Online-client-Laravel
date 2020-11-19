<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ResourceInterface;

class BankEntryLine extends Resource implements ResourceInterface
{
    protected string $endpoint = 'financialtransaction/BankEntryLines';

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
