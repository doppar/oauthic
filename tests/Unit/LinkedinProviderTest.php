<?php

namespace Doppar\OAuthic\Tests\Unit;

use Doppar\OAuthic\Drivers\LinkedinProvider;
use Doppar\OAuthic\User;
use Doppar\OAuthic\Exceptions\AuthException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class LinkedinProviderTest extends TestCase
{
    private LinkedinProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new LinkedinProvider([
            'client_id' => 'test_client',
            'client_secret' => 'test_secret',
            'redirect' => 'http://localhost/callback',
        ]);
    }

    public function testGetAuthUrl()
    {
        $url = $this->provider->getAuthUrl();
        $this->assertStringContainsString('https://www.linkedin.com/oauth/v2/authorization', $url);
        $this->assertStringContainsString('client_id=test_client', $url);
        $this->assertStringContainsString('redirect_uri=http%3A%2F%2Flocalhost%2Fcallback', $url);
        $this->assertStringContainsString('response_type=code', $url);
    }

    public function testGetTokenUrl()
    {
        $this->assertEquals(
            'https://www.linkedin.com/oauth/v2/accessToken',
            $this->provider->getTokenUrl()
        );
    }

    public function testGetTokenFields()
    {
        $method = (new ReflectionClass(LinkedinProvider::class))
            ->getMethod('getTokenFields');

        $fields = $method->invokeArgs($this->provider, ['test_code']);
        $this->assertEquals([
            'client_id' => 'test_client',
            'client_secret' => 'test_secret',
            'code' => 'test_code',
            'redirect_uri' => 'http://localhost/callback',
            'grant_type' => 'authorization_code'
        ], $fields);
    }

    public function testGetTokenHeaders()
    {
        $method = (new ReflectionClass(LinkedinProvider::class))
            ->getMethod('getTokenHeaders');

        $headers = $method->invoke($this->provider);
        $this->assertEquals([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json'
        ], $headers);
    }

    public function testGetScopes()
    {
        $method = (new ReflectionClass(LinkedinProvider::class))
            ->getMethod('getScopes');

        $this->assertEquals('openid profile email', $method->invoke($this->provider));

        $providerWithScopes = new LinkedinProvider([
            'client_id' => 'test',
            'client_secret' => 'test',
            'redirect' => 'http://test',
            'scopes' => ['openid', 'w_member_social']
        ]);
        $this->assertEquals('openid w_member_social', $method->invoke($providerWithScopes));
    }

    public function testMapUserToObject()
    {
        $userData = [
            'id' => '12345',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'avatar' => 'http://example.com/avatar.jpg',
            'first_name' => 'Test',
            'last_name' => 'User'
        ];

        $user = $this->provider->mapUserToObject($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('12345', $user->getId());
        $this->assertEquals('Test User', $user->getName());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('http://example.com/avatar.jpg', $user->getAvatar());
        $this->assertEquals([
            'id' => '12345',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'avatar' => 'http://example.com/avatar.jpg',
            'first_name' => 'Test',
            'last_name' => 'User'
        ], $user->getRaw());
    }

    public function testMapUserToObjectWithPartialData()
    {
        $userData = [
            'id' => '12345',
            'email' => 'test@example.com'
        ];

        $user = $this->provider->mapUserToObject($userData);
        $this->assertEquals('12345', $user->getId());
        $this->assertNull($user->getName());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertNull($user->getAvatar());
    }
}
