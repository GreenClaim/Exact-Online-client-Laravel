<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ExactOnlineResourceInterface;

class GeneralLedgerAccount extends ExactOnlineResource implements ExactOnlineResourceInterface
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
