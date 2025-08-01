<?php

namespace Doppar\OAuthic\Tests\Unit;

use Doppar\OAuthic\Drivers\GithubProvider;
use Doppar\OAuthic\User;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GithubProviderTest extends TestCase
{
    private GithubProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new GithubProvider([
            'client_id' => 'test_client',
            'client_secret' => 'test_secret',
            'redirect' => 'http://localhost/callback',
        ]);
    }

    public function testGetAuthUrl()
    {
        $url = $this->provider->getAuthUrl();
        $this->assertStringContainsString('https://github.com/login/oauth/authorize', $url);
        $this->assertStringContainsString('client_id=test_client', $url);
        $this->assertStringContainsString('redirect_uri=http%3A%2F%2Flocalhost%2Fcallback', $url);
        $this->assertStringContainsString('scope=user%3Aemail', $url);
    }

    public function testGetTokenUrl()
    {
        $this->assertEquals(
            'https://github.com/login/oauth/access_token',
            $this->provider->getTokenUrl()
        );
    }

    public function testGetTokenFields()
    {
        // Use reflection to test protected method
        $method = (new ReflectionClass(GithubProvider::class))
            ->getMethod('getTokenFields');
        $method->setAccessible(true);

        $fields = $method->invokeArgs($this->provider, ['test_code']);
        $this->assertEquals([
            'client_id' => 'test_client',
            'client_secret' => 'test_secret',
            'code' => 'test_code',
            'redirect_uri' => 'http://localhost/callback',
        ], $fields);
    }

    public function testGetScopes()
    {
        // Use reflection to test protected method
        $method = (new ReflectionClass(GithubProvider::class))
            ->getMethod('getScopes');
        $method->setAccessible(true);

        // Test default scopes
        $this->assertEquals('user:email', $method->invoke($this->provider));

        // Test custom scopes
        $providerWithScopes = new GithubProvider([
            'client_id' => 'test',
            'client_secret' => 'test',
            'redirect' => 'http://test',
            'scopes' => ['user', 'repo']
        ]);
        $this->assertEquals('user repo', $method->invoke($providerWithScopes));
    }

    public function testMapUserToObject()
    {
        $userData = [
            'id' => 123,
            'name' => 'Test User',
            'login' => 'testuser',
            'email' => 'test@example.com',
            'avatar_url' => 'http://example.com/avatar'
        ];

        $user = $this->provider->mapUserToObject($userData);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(123, $user->getId());
        $this->assertEquals('Test User', $user->getName());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('http://example.com/avatar', $user->getAvatar());
    }

    public function testMapUserToObjectWithLoginFallback()
    {
        $userData = [
            'id' => 123,
            'login' => 'testuser',
            'email' => 'test@example.com'
        ];

        $user = $this->provider->mapUserToObject($userData);
        $this->assertEquals('testuser', $user->getName());
    }

    public function testMapUserToObjectWithMissingFields()
    {
        $userData = [
            'id' => 123,
            'login' => 'testuser'
        ];

        $user = $this->provider->mapUserToObject($userData);
        $this->assertNull($user->getEmail());
        $this->assertNull($user->getAvatar());
    }
}