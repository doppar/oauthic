<?php

namespace Doppar\OAuthic;

use Doppar\OAuthic\Contracts\UserInterface;

/**
 * Class User
 *
 * Represents an authenticated user retrieved from an OAuth provider.
 * Acts as a data container for basic user information such as ID, name, email, and avatar.
 * Also retains the raw user array and the originating provider's name.
 *
 * @package Doppar\OAuthic
 */
class User implements UserInterface
{
    /**
     * The raw user data returned from the OAuth provider.
     *
     * @var array
     */
    protected array $user;

    /**
     * The name of the OAuth provider (e.g., google, github).
     *
     * @var string
     */
    protected string $provider;

    /**
     * User constructor.
     *
     * @param array $user The raw user information from the provider.
     */
    public function __construct(array $user)
    {
        $this->user = $user;
    }

    /**
     * Get the unique identifier of the user from the provider.
     *
     * @return mixed|null
     */
    public function getId(): mixed
    {
        return $this->user["id"] ?? null;
    }

    /**
     * Get the user's display name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->user["name"] ?? null;
    }

    /**
     * Get the user's email address.
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->user["email"] ?? null;
    }

    /**
     * Get the URL to the user's avatar image.
     *
     * @return string|null
     */
    public function getAvatar(): ?string
    {
        return $this->user["avatar"] ?? null;
    }

    /**
     * Get the raw user array as returned by the provider.
     *
     * @return array
     */
    public function getRaw(): array
    {
        return $this->user;
    }

    /**
     * Get the name of the OAuth provider associated with the user.
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->provider;
    }

    /**
     * Set the name of the OAuth provider.
     *
     * @param string $provider
     * @return $this
     */
    public function setProviderName(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }
}
