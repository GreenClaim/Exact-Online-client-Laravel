<?php

namespace Yource\ExactOnlineClient\Concerns;

trait Authenticatable
{
    public function authenticate($requestContent, $webhookSecret)
    {
        $matches = [];
        $matched = preg_match('/^{"Content":(.*),"HashCode":"(.*)"}$/', $requestContent, $matches);

        if ($matched === 1 && isset($matches[1]) && isset($matches[2])) {
            return $matches[2] === strtoupper(hash_hmac('sha256', $matches[1], $webhookSecret));
        }

        return false;
    }
}
