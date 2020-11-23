<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ResourceInterface;

class Account extends Resource implements ResourceInterface
{
    protected array $dates = [
        'ControlledDate',
        'Created',
        'CustomerSince',
        'EndDate',
        'EstablishedDate',
        'Modified',
        'StatusSince',
        'StartDate',
    ];

    protected string $endpoint = 'crm/Accounts';

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
