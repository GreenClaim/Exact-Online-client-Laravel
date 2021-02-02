<?php

namespace Yource\ExactOnlineClient\Resources;

use Yource\ExactOnlineClient\Interfaces\ResourceInterface;

class WebhookSubscriptions extends Resource implements ResourceInterface
{
    protected string $endpoint = 'webhooks/WebhookSubscriptions';

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
