<?php

namespace Doppar\OAuthic\Drivers;

use Doppar\OAuthic\Exceptions\AuthException;
use Doppar\OAuthic\Contracts\UserInterface;
use Doppar\OAuthic\Contracts\ProviderInterface;
use Doppar\Axios\Http\Axios;

abstract class AbstractProvider implements ProviderInterface
{
    protected array $config;

    protected bool $stateless = false;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    abstract public function getAuthUrl(): string;
    abstract public function getTokenUrl(): string;
    abstract public function getUserByToken(string $token): array;
    abstract public function mapUserToObject(array $user): UserInterface;

    public function stateless(): self
    {
        $this->stateless = true;

        return $this;
    }

    public function redirect()
    {
        return $this->getAuthUrl();
    }

    public function user()
    {
        if (isset($_GET['error'])) {
            throw new AuthException($_GET['error']);
        }

        if (!isset($_GET['code'])) {
            throw new AuthException('No authorization code provided');
        }

        $token = $this->getAccessToken($_GET['code']);
        $user = $this->getUserByToken($token);

        return $this->mapUserToObject($user)->setProviderName($this->getName());
    }

    protected function getAccessToken(string $code): string
    {
        $response = Axios::to($this->getTokenUrl())
            ->withoutHttp2()
            ->withHeaders($this->getTokenHeaders())
            ->post($this->getTokenFields($code));

        $data = $response->json();

        if (!isset($data['access_token'])) {
            throw new AuthException('Invalid access token response');
        }

        return $data['access_token'];
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

    protected function getTokenHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }

    protected function getName(): string
    {
        return strtolower((new \ReflectionClass($this))->getShortName());
    }
}
