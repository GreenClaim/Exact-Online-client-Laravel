<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ResourceInterface;

class GeneralLedgerAccount extends Resource implements ResourceInterface
{
    protected string $endpoint = 'financial/GLAccounts';

    protected array $dates = [
        'Created',
        'Modified',
    ];

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
