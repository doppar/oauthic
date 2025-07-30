<?php

namespace Doppar\OAuthic;

use Doppar\OAuthic\Contracts\UserInterface;

class User implements UserInterface
{
    protected array $user;

    protected string $provider;

    public function __construct(array $user)
    {
        $this->user = $user;
    }

    public function getId()
    {
        return $this->user['id'] ?? null;
    }

    public function getName()
    {
        return $this->user['name'] ?? null;
    }

    public function getEmail()
    {
        return $this->user['email'] ?? null;
    }

    public function getAvatar()
    {
        return $this->user['avatar'] ?? null;
    }

    public function getRaw()
    {
        return $this->user;
    }

    public function getProviderName()
    {
        return $this->provider;
    }

    public function setProviderName(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }
}
