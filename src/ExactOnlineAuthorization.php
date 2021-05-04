<?php

namespace Yource\ExactOnlineClient;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class ExactOnlineAuthorization
{
    private const BASE_URI = 'https://start.exactonline.nl';

    private const AUTH_URL_PATH = '/api/oauth2/auth';

    private const TOKEN_URL_PATH = '/api/oauth2/token';

    private const CREDENTIALS_KEY = 'exact_online.credentials';

    private ?string $redirectUrl;

    private ?string $clientId;

    private ?string $clientSecret;

    private Client $client;

    public function __construct()
    {
        $this->redirectUrl = config('exact-online-client-laravel.redirect_url');
        $this->clientId = config('exact-online-client-laravel.client_id');
        $this->clientSecret = config('exact-online-client-laravel.client_secret');
        $this->client = new Client(['base_uri' => self::BASE_URI]);
    }

    public function getCredentials(): ?object
    {
        $credentials = Cache::get(self::CREDENTIALS_KEY);

        if (!empty($credentials)) {
            return (object) json_decode($credentials, false);
        }

        return null;
    }

    public function setCredentials($credentials)
    {
        return Cache::put(self::CREDENTIALS_KEY, json_encode($credentials));
    }

    private function getAuthorizationCode(): string
    {
        $authorisationCode = optional($this->getCredentials())->authorisationCode;

        if (empty($authorisationCode)) {
            throw new Exception(sprintf(
                'Authorisation code does not exist. Go to: %s to request one.',
                route('exact-online.connect')
            ));
        }

        return $authorisationCode;
    }

    private function getRefreshToken(): ?string
    {
        return optional($this->getCredentials())->refreshToken;
    }

    public function getAccessToken(): ?string
    {
        return optional($this->getCredentials())->accessToken;
    }

    private function getTokenExpires(): ?string
    {
        return optional($this->getCredentials())->tokenExpires;
    }

    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function setAccessToken($accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function acquireAccessToken(): void
    {
        try {
            $refreshToken = $this->getRefreshToken();

            // If refresh token not yet acquired, do token request
            if (empty($refreshToken)) {
                $body = [
                    'form_params' => [
                        'redirect_uri'  => $this->redirectUrl,
                        'grant_type'    => 'authorization_code',
                        'client_id'     => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'code'          => $this->getAuthorizationCode(),
                    ],
                ];
            } else { // else do refresh token request
                $body = [
                    'form_params' => [
                        'refresh_token' => $this->getRefreshToken(),
                        'grant_type'    => 'refresh_token',
                        'client_id'     => $this->clientId,
                        'client_secret' => $this->clientSecret,
                    ],
                ];
            }

            $response = $this->client->post($this->getTokenUrl(), $body);

            Message::rewindBody($response);
            $body = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $credentials = $this->getCredentials();

                if (!empty($credentials)) {
                    $credentials->accessToken = serialize($body['access_token']);
                    $credentials->refreshToken = $body['refresh_token'];
                    $credentials->tokenExpires = $this->getTimestampFromExpiresIn((int) $body['expires_in']);

                    $this->setCredentials($credentials);
                }
            } else {
                throw new Exception(
                    'Could not acquire tokens, json decode failed. Got response: ' .
                    $response->getBody()->getContents()
                );
            }
        } catch (ClientException $exception) {
            throw new Exception(
                'Could not acquire or refresh tokens [http ' . $exception . ']: ' .
                $exception->getResponse()->getBody()->getContents()
            );
        } catch (Exception $exception) {
            throw new Exception(
                'Could not acquire or refresh tokens [http ' . $exception . ']',
                0,
                $exception
            );
        }
    }

    public function hasValidToken(): bool
    {
        return !empty($this->getAccessToken()) && !$this->tokenHasExpired();
    }

    private function tokenHasExpired(): bool
    {
        $tokenExpires = $this->getTokenExpires();
        if (empty($tokenExpires)) {
            return true;
        }

        return ($tokenExpires - 60) < time();
    }

    /**
     * Translates expires_in to a Unix timestamp.
     *
     * @param int $expiresIn number of seconds until the token expires
     */
    private function getTimestampFromExpiresIn(int $expiresIn): int
    {
        if (!ctype_digit($expiresIn)) {
            throw new InvalidArgumentException('Function requires a numeric expires value');
        }

        return time() + $expiresIn;
    }

    public function getAuthUrl(): string
    {
        $query = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUrl,
            'response_type' => 'code',
        ]);

        return self::BASE_URI . self::AUTH_URL_PATH . '?' . $query;
    }

    private function getTokenUrl(): string
    {
        return self::BASE_URI . self::TOKEN_URL_PATH;
    }
}
