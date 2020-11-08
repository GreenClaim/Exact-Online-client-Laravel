<?php

namespace Yource\ExactOnlineClient\Interfaces;

interface ExactOnlineResourceInterface
{
    /**
     * The endpoint of the resource
     *
     * @return string
     */
    public function getEndpoint(): string;
}
