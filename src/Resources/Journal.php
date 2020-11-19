<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ResourceInterface;

class Journal extends Resource implements ResourceInterface
{
    protected string $endpoint = 'financial/Journals';

    protected array $dates = [
        'Created',
        'Modified',
    ];

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
