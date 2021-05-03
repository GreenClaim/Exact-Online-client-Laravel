<?php

namespace Yource\ExactOnlineClient;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ExactOnlineAuthorization
{
    private string $baseUri = 'https://start.exactonline.nl';

    private string $authUrlPath = '/api/oauth2/auth';

    private string $tokenUrlPath = '/api/oauth2/token';

    private ?string $credentialFilePath;

    private ?string $credentialFileDisk;

    private ?string $redirectUrl;

    private ?string $clientId;

    private ?string $clientSecret;

    public function __construct()
    {
        $this->credentialFilePath = config('exact-online-client-laravel.credential_file_path');
        $this->credentialFileDisk = config('exact-online-client-laravel.credential_file_disk');
        $this->redirectUrl = config('exact-online-client-laravel.redirect_url');
        $this->clientId = config('exact-online-client-laravel.client_id');
        $this->clientSecret = config('exact-online-client-laravel.client_secret');
        $this->client = new Client(['base_uri' => $this->baseUri]);
    }

    private function getCredentials(): ?object
    {
        if (Storage::disk($this->credentialFileDisk)->exists($this->credentialFilePath)) {
            $credentials = Storage::disk($this->credentialFileDisk)->get(
                $this->credentialFilePath
            );

            return (object) json_decode($credentials, false);
        }

        return null;
    }

    private function getAuthorizationCode(): string
    {
        $credentials = $this->getCredentials();
        if (!empty($credentials) && !empty($credentials->authorisationCode)) {
            return $credentials->authorisationCode;
        }

        throw new Exception(
            'Authorization code does not exist. Go to: ' . route('exact-online.connect') . ' to request one.'
        );
    }

    private function getRefreshToken(): ?string
    {
        $credentials = $this->getCredentials();
        return optional($credentials)->refreshToken;
    }

    public function getAccessToken(): ?string
    {
        $credentials = $this->getCredentials();
        return optional($credentials)->accessToken;
    }

    private function getTokenExpires(): ?string
    {
        $credentials = $this->getCredentials();
        return optional($credentials)->tokenExpires;
    }

    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function setAccessToken($accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getCredentialFilePath(): string
    {
        return $this->credentialFilePath;
    }

    public function getCredentialFileDisk(): string
    {
        return $this->credentialFileDisk;
    }

    private function getTokenUrl(): string
    {
        return $this->baseUri . $this->tokenUrlPath;
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
                if (Storage::disk($this->credentialFileDisk)->exists($this->credentialFilePath)) {
                    $credentials = Storage::disk($this->credentialFileDisk)->get(
                        $this->credentialFilePath
                    );

                    $credentials = (object) json_decode($credentials, false);
                    $credentials->accessToken = serialize($body['access_token']);
                    $credentials->refreshToken = $body['refresh_token'];
                    $credentials->tokenExpires = $this->getTimestampFromExpiresIn((int) $body['expires_in']);

                    Storage::disk($this->credentialFileDisk)->put(
                        $this->credentialFilePath,
                        json_encode($credentials)
                    );
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
     * @param string $expiresIn number of seconds until the token expires
     *
     * @return int
     */
    private function getTimestampFromExpiresIn(int $expiresIn)
    {
        if (! ctype_digit($expiresIn)) {
            throw new InvalidArgumentException('Function requires a numeric expires value');
        }

        return time() + $expiresIn;
    }

    /**
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->baseUri . $this->authUrlPath . '?' . http_build_query([
                'client_id'     => $this->clientId,
                'redirect_uri'  => $this->redirectUrl,
                'response_type' => 'code',
            ]);
    }
}
