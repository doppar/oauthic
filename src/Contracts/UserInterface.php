<?php

namespace Doppar\OAuthic\Contracts;

interface UserInterface
{
    /**
     * Get the unique identifier of the user from the provider.
     *
     * @return mixed|null
     */
    public function getId(): mixed;

    /**
     * Get the user's display name.
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Get the user's email address.
     *
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * Get the URL to the user's avatar image.
     *
     * @return string|null
     */
    public function getAvatar(): ?string;

    /**
     * Get the raw user array as returned by the provider.
     *
     * @return array
     */
    public function getRaw(): array;

    /**
     * Get the name of the OAuth provider associated with the user.
     *
     * @return string
     */
    public function getProviderName(): string;
}
