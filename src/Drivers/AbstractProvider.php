<?php

namespace Doppar\OAuthic\Drivers;

use Doppar\OAuthic\Exceptions\AuthException;
use Doppar\OAuthic\Contracts\UserInterface;
use Doppar\OAuthic\Contracts\ProviderInterface;
use Doppar\Axios\Http\Axios;

/**
 * Class AbstractProvider
 *
 * An abstract base class for OAuth providers implementing the ProviderInterface.
 * Handles the common OAuth 2.0 flow: redirecting, handling the callback, exchanging
 * authorization codes for access tokens, and retrieving user data.
 *
 * Concrete providers must implement methods to:
 *  - Build the auth URL
 *  - Define the token endpoint
 *  - Fetch user data using the token
 *  - Map user data into a standard User object
 *
 * @package Doppar\OAuthic\Drivers
 */
abstract class AbstractProvider implements ProviderInterface
{
    /**
     * The OAuth provider configuration (client ID, secret, redirect URL, etc.).
     *
     * @var array
     */
    protected array $config;

    /**
     * Indicates whether the provider should skip state validation (stateless mode).
     *
     * @var bool
     */
    protected bool $stateless = false;

    /**
     * AbstractProvider constructor.
     *
     * @param array $config Provider-specific configuration.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get the authorization URL to redirect the user to.
     *
     * @return string
     */
    abstract public function getAuthUrl(): string;

    /**
     * Get the access token endpoint URL.
     *
     * @return string
     */
    abstract public function getTokenUrl(): string;

    /**
     * Retrieve the user's raw data from the provider using the access token.
     *
     * @param string $token
     * @return array
     */
    abstract public function getUserByToken(string $token): array;

    /**
     * Map the raw user data to a UserInterface-compliant object.
     *
     * @param array $user
     * @return \Doppar\OAuthic\Contracts\UserInterface
     */
    abstract public function mapUserToObject(array $user): UserInterface;

    /**
     * Enable stateless mode for the OAuth flow (no session/state validation).
     *
     * @return $this
     */
    public function stateless(): self
    {
        $this->stateless = true;

        return $this;
    }

    /**
     * Get the authorization URL for redirection.
     * This may be overridden by specific providers if needed.
     *
     * @return string
     */
    public function redirect()
    {
        return $this->getAuthUrl();
    }

    /**
     * Retrieve the authenticated user from the provider after the callback.
     *
     * @return \Doppar\OAuthic\Contracts\UserInterface
     * @throws \Doppar\OAuthic\Exceptions\AuthException
     */
    public function user()
    {
        if (isset($_GET["error"])) {
            throw new AuthException($_GET["error"]);
        }

        if (!isset($_GET["code"])) {
            throw new AuthException("No authorization code provided");
        }

        $token = $this->getAccessToken($_GET["code"]);
        $user = $this->getUserByToken($token);

        return $this->mapUserToObject($user)->setProviderName($this->getName());
    }

    /**
     * Exchange the authorization code for an access token.
     *
     * @param string $code
     * @return string
     * @throws \Doppar\OAuthic\Exceptions\AuthException
     */
    protected function getAccessToken(#[\SensitiveParameter] string $code): string
    {
        $response = Axios::to($this->getTokenUrl())
            ->withoutHttp2()
            ->withHeaders($this->getTokenHeaders())
            ->post($this->getTokenFields($code));

        $data = $response->json();

        if (!isset($data["access_token"])) {
            throw new AuthException("Invalid access token response");
        }

        return $data["access_token"];
    }

    /**
     * Get the POST fields required for the token request.
     *
     * @param string $code
     * @return array
     */
    protected function getTokenFields(#[\SensitiveParameter] string $code): array
    {
        return [
            "client_id" => $this->config["client_id"],
            "client_secret" => $this->config["client_secret"],
            "code" => $code,
            "redirect_uri" => $this->config["redirect"],
        ];
    }

    /**
     * Get the headers required for the token request.
     *
     * @return array
     */
    protected function getTokenHeaders(): array
    {
        return [
            "Accept" => "application/json",
        ];
    }

    /**
     * Get the name of the provider based on the class name.
     * This is used to set the provider name in the User object.
     *
     * @return string
     */
    protected function getName(): string
    {
        $providerName = (new \ReflectionClass($this))->getShortName();

        return strtolower($providerName);
    }
}
