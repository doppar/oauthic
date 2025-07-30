<?php

namespace Doppar\OAuthic\Contracts;

interface ProviderInterface
{
    public function redirect();
    public function user();
    public function stateless();
}
