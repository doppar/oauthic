<?php

namespace Doppar\OAuthic\Contracts;

interface UserInterface
{
    public function getId();
    public function getName();
    public function getEmail();
    public function getAvatar();
    public function getRaw();
    public function getProviderName();
}
