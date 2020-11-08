<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ExactOnlineResourceInterface;

class BankEntryLine extends ExactOnlineResource implements ExactOnlineResourceInterface
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
