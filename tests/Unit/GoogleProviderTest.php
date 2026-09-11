<?php

namespace Doppar\OAuthic\Tests\Unit;

use Doppar\OAuthic\Drivers\GoogleProvider;
use Doppar\OAuthic\User;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GoogleProviderTest extends TestCase
{
    private GoogleProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new GoogleProvider([
            'client_id' => 'test_client',
            'client_secret' => 'test_secret',
            'redirect' => 'http://localhost/callback',
        ]);
    }

    public function testGetAuthUrl()
    {
        $url = $this->provider->getAuthUrl();
        $this->assertStringContainsString('https://accounts.google.com/o/oauth2/auth', $url);
        $this->assertStringContainsString('client_id=test_client', $url);
        $this->assertStringContainsString('redirect_uri=http%3A%2F%2Flocalhost%2Fcallback', $url);
        $this->assertStringContainsString('scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.email', $url);
        $this->assertStringContainsString('access_type=online', $url);
    }

    public function testGetTokenUrl()
    {
        $this->assertEquals(
            'https://oauth2.googleapis.com/token',
            $this->provider->getTokenUrl()
        );
    }

    public function testGetTokenFields()
    {
        // Use reflection to test protected method
        $method = (new ReflectionClass(GoogleProvider::class))
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

    public function testGetScopes()
    {
        // Use reflection to test protected method
        $method = (new ReflectionClass(GoogleProvider::class))
            ->getMethod('getScopes');

        // Test default scopes
        $defaultScopes = 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile';
        $this->assertEquals($defaultScopes, $method->invoke($this->provider));

        // Test custom scopes
        $providerWithScopes = new GoogleProvider([
            'client_id' => 'test',
            'client_secret' => 'test',
            'redirect' => 'http://test',
            'scopes' => ['email', 'profile']
        ]);
        $this->assertEquals('email profile', $method->invoke($providerWithScopes));
    }

    public function testMapUserToObject()
    {
        $userData = [
            'id' => '12345',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'picture' => 'http://example.com/avatar.jpg',
            'given_name' => 'Test',
            'family_name' => 'User'
        ];

        $user = $this->provider->mapUserToObject($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('12345', $user->getId());
        $this->assertEquals('Test User', $user->getName());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('http://example.com/avatar.jpg', $user->getAvatar());
    }

    public function testMapUserToObjectWithPartialData()
    {
        $userData = [
            'sub' => '12345',
            'email' => 'test@example.com'
        ];

        $user = $this->provider->mapUserToObject($userData);
        $this->assertEquals('12345', $user->getId());
        $this->assertNull($user->getName());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertNull($user->getAvatar());
    }

    public function testMapUserToObjectWithNamesSeparately()
    {
        $userData = [
            'id' => '12345',
            'given_name' => 'Test',
            'family_name' => 'User'
        ];

        $user = $this->provider->mapUserToObject($userData);

        $this->assertEquals('12345', $user->getId());
    }

    public function testMapUserToObjectWithSubIdentifier()
    {
        $userData = [
            'sub' => '12345',
            'given_name' => 'Test',
            'family_name' => 'User'
        ];

        $user = $this->provider->mapUserToObject($userData);

        $this->assertEquals('Test User', $user->getName());
    }
}
