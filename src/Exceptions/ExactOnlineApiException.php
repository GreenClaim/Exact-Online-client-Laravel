<?php

namespace Yource\ExactOnlineClient\Exceptions;

use Exception;

class ExactOnlineApiException extends Exception
{
    /**
     * The status code to use for the response.
     *
     * @var int
     */
    public $status = 502;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  \Symfony\Component\HttpFoundation\Response|null  $response
     * @param  string  $errorBag
     * @return void
     */
    public function __construct(string $message = 'Something when wrong')
    {
        parent::__construct($message);
    }
}
