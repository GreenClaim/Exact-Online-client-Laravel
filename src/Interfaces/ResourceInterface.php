<?php

namespace Yource\ExactOnlineClient\Interfaces;

interface ResourceInterface
{
    /**
     * The endpoint of the resource
     *
     * @return string
     */
    public function getEndpoint(): string;
}
