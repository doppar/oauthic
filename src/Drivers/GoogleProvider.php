<?php

namespace Doppar\OAuthic\Drivers;

use Doppar\Axios\Http\Axios;
use Doppar\OAuthic\User;

/**
 * Class GoogleProvider
 *
 * OAuth provider implementation for Google.
 * Handles the OAuth 2.0 authentication flow: building authorization URL,
 * exchanging code for token, retrieving user info, and mapping it to a User object.
 *
 * @package Doppar\OAuthic\Drivers
 */
class GoogleProvider extends AbstractProvider
{
    /**
     * Separator used when joining multiple scopes.
     *
     * @var string
     */
    protected string $scopeSeparator = ' ';

    /**
     * Get the Google authorization URL to redirect the user to.
     *
     * @return string
     */
    #[\Override]
    public function getAuthUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect'],
            'scope' => $this->getScopes(),
            'response_type' => 'code',
            'access_type' => 'online',
            'prompt' => 'consent select_account'
        ]);
    }

    /**
     * Get the Google token endpoint URL.
     *
     * @return string
     */
    #[\Override]
    public function getTokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    /**
     * Get the fields required to request an access token from Google.
     *
     * @param string $code The authorization code received from Google.
     * @return array
     */
    protected function getTokenFields(#[\SensitiveParameter] string $code): array 
    {
        return [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'code' => $code,
            'redirect_uri' => $this->config['redirect'],
            'grant_type' => 'authorization_code'
        ];
    }

    /**
     * Get the scope string for the authorization URL.
     *
     * @return string
     */
    protected function getScopes(): string
    {
        return implode($this->scopeSeparator, $this->config['scopes'] ?? [
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile'
        ]);
    }

    /**
     * Fetch the authenticated user's data from Google using the access token.
     *
     * @param string $token
     * @return array
     */
    #[\Override]
    public function getUserByToken(#[\SensitiveParameter] string $token): array
    {
        $response = Axios::to('https://www.googleapis.com/oauth2/v1/userinfo')
            ->withoutHttp2()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])
            ->get()
            ->json();

        return (array) $response;
    }

    /**
     * Map the raw Google user array to a standardized User object.
     *
     * @param array $user
     * @return \Doppar\OAuthic\User
     */
    #[\Override]
    public function mapUserToObject(array $user): User
    {
        return new User([
            'id' => $user['id'],
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'avatar' => $user['picture'] ?? null,
        ]);
    }
}
