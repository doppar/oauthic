<?php

namespace Doppar\OAuthic\Drivers;

use Doppar\Axios\Http\Axios;
use Doppar\OAuthic\User;

class GoogleProvider extends AbstractProvider
{
    protected string $scopeSeparator = ' ';

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

    public function getTokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function getTokenFields(string $code): array
    {
        return [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'code' => $code,
            'redirect_uri' => $this->config['redirect'],
            'grant_type' => 'authorization_code'
        ];
    }

    protected function getScopes(): string
    {
        return implode($this->scopeSeparator, $this->config['scopes'] ?? [
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile'
        ]);
    }

    public function getUserByToken(string $token): array
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

    public function mapUserToObject(array $user): User
    {
        return (new User([
            'id' => $user['id'],
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'avatar' => $user['picture'] ?? null,
        ]));
    }
}
