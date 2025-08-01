<?php

namespace Doppar\OAuthic\Drivers;

use Doppar\OAuthic\User;
use Doppar\OAuthic\Exceptions\AuthException;
use Doppar\Axios\Http\Axios;

/**
 * Class LinkedinProvider
 *
 * OAuth provider implementation for Linkedin.
 * Handles the OAuth 2.0 authentication flow: building authorization URL,
 * exchanging code for token, retrieving user info, and mapping it to a User object.
 *
 * @package Doppar\OAuthic\Drivers
 */
class LinkedinProvider extends AbstractProvider
{
    /**
     * Separator used when joining multiple scopes.
     *
     * @var string
     */
    protected string $scopeSeparator = ' ';

    /**
     * Get the Linkedin authorization URL to redirect the user to.
     *
     * @return string
     */
    #[\Override]
    public function getAuthUrl(): string
    {
        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query([
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect'],
            'scope' => $this->getScopes(),
            'response_type' => 'code',
            'state' => bin2hex(random_bytes(16)),
        ]);
    }

    /**
     * Get the Linkedin token endpoint URL.
     *
     * @return string
     */
    #[\Override]
    public function getTokenUrl(): string
    {
        return 'https://www.linkedin.com/oauth/v2/accessToken';
    }

    /**
     * Get the fields required to request an access token from Linkedin.
     *
     * @param string $code The authorization code received from Linkedin.
     * @return array
     */
    protected function getTokenFields(#[\SensitiveParameter] string $code): array
    {
        return [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'code' => $code,
            'redirect_uri' => $this->config['redirect'],
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * Exchange the authorization code for an access token.
     *
     * @param string $code
     * @return string
     * @throws \Doppar\OAuthic\Exceptions\AuthException
     */
    #[\Override]
    protected function getAccessToken(#[\SensitiveParameter] string $code): string
    {
        $response = Axios::to($this->getTokenUrl())
            ->asForm()
            ->withHeaders($this->getTokenHeaders())
            ->post($this->getTokenFields($code));

        $statusCode = $response->status();
        if ($statusCode !== 200) {
            $errorBody = $response->body();
            throw new AuthException(
                "LinkedIn returned status $statusCode. Response: $errorBody"
            );
        }

        try {
            $data = $response->json();
        } catch (\Exception $e) {
            throw new AuthException(
                'Failed to decode JSON response: ' . $e->getMessage()
            );
        }

        if (!isset($data['access_token'])) {
            throw new AuthException(
                'Invalid access token response: ' . json_encode($data)
            );
        }

        return $data['access_token'];
    }

    /**
     * Get the headers required for the token request.
     *
     * @return array
     */
    #[\Override]
    protected function getTokenHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json'
        ];
    }

    protected function getScopes(): string
    {
        return implode($this->scopeSeparator, $this->config['scopes'] ?? [
            "openid",
            "profile",
            "email",
        ]);
    }

    /**
     * Fetch the authenticated user's data from Linkedin using the access token.
     *
     * @param string $token
     * @return array
     */
    #[\Override]
    public function getUserByToken(#[\SensitiveParameter] string $token): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.linkedin.com/v2/userinfo',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token . ''
            ]
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // Handle potential errors
        if ($httpCode !== 200) {
            throw new \Exception("LinkedIn API request failed with status: $httpCode");
        }

        $userInfo = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to decode LinkedIn API response");
        }

        return [
            'id' => $userInfo['sub'] ?? null,
            'first_name' => $userInfo['given_name'] ?? null,
            'last_name' => $userInfo['family_name'] ?? null,
            'name' => $userInfo['name'] ?? null,
            'avatar' => $userInfo['picture'] ?? null,
            'email' => $userInfo['email'] ?? null,
        ];
    }

    /**
     * Map the raw Linkedin user array to a standardized User object.
     *
     * @param array $user
     * @return \Doppar\OAuthic\User
     */
    #[\Override]
    public function mapUserToObject(array $user): User
    {
        return new User([
            'id' => $user['id'],
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'avatar' => $user['avatar'] ?? null,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
        ]);
    }
}
