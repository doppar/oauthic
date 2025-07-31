<?php

namespace Doppar\OAuthic\Drivers;

use Doppar\Axios\Http\Axios;
use Doppar\OAuthic\User;

class GithubProvider extends AbstractProvider
{
    protected string $scopeSeparator = ' ';

    public function getAuthUrl(): string
    {
        return 'https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect'],
            'scope' => $this->getScopes(),
            'response_type' => 'code',
        ]);
    }

    public function getTokenUrl(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    protected function getTokenFields(string $code): array
    {
        return [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'code' => $code,
            'redirect_uri' => $this->config['redirect'],
        ];
    }

    protected function getScopes(): string
    {
        return implode($this->scopeSeparator, $this->config['scopes'] ?? [
            'user:email',
        ]);
    }

    public function getUserByToken(string $token): array
    {
        $userResponse = Axios::to('https://api.github.com/user')
            ->withHeaders([
                'Authorization' => 'token ' . $token,
                'Accept' => 'application/json',
            ])
            ->get()
            ->json();

        $userResponse = (array) $userResponse;

        // GitHub might not return the email in the user response,
        // So we need a separate request
        if (!isset($userResponse['email'])) {
            $emailsResponse = Axios::to('https://api.github.com/user/emails')
                ->withHeaders([
                    'Authorization' => 'token ' . $token,
                    'Accept' => 'application/json',
                ])
                ->get()
                ->json();

            foreach ((array) $emailsResponse as $email) {
                if ($email['primary'] && $email['verified']) {
                    $userResponse['email'] = $email['email'];
                    break;
                }
            }
        }

        return $userResponse;
    }

    public function mapUserToObject(array $user): User
    {
        return (new User([
            'id' => $user['id'],
            'name' => $user['name'] ?? $user['login'] ?? null,
            'email' => $user['email'] ?? null,
            'avatar' => $user['avatar_url'] ?? null,
        ]));
    }
}
