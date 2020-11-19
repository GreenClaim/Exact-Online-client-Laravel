<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class EmptyAttributesException extends RuntimeException
{
    /**
 * The status code to use for the response.
 *
 * @var int
 */
    public $status = 422;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  \Symfony\Component\HttpFoundation\Response|null  $response
     * @param  string  $errorBag
     * @return void
     */
    public function __construct(string $message = 'Attributes can\'t be empty')
    {
        parent::__construct($message);
    }
}
