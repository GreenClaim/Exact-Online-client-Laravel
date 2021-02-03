<?php

namespace Yource\ExactOnlineClient\Http\Middlewares;

use Closure;
use Yource\ExactOnlineClient\Concerns\Authenticatable;

class ExactOnlineWebhookAuthentication
{
    use Authenticatable;

    public function handle($request, Closure $next)
    {
        // If the body is empty we assume it's a webhook validation request, we wouldn't do anything just return 200
        if (empty($request->getContent())) {
            return response('');
        }

        if (
            $this->authenticate($request->getContent(), config('exact-online-client-laravel.webhook_secret'))
        ) {
            return $next($request);
        }

        return abort(403, 'Verification failed.');
    }
}
