<?php

namespace Doppar\OAuthic\Drivers;

use Doppar\Axios\Http\Axios;
use Doppar\OAuthic\User;

/**
 * Class GithubProvider
 *
 * OAuth provider implementation for GitHub.
 * Handles the OAuth 2.0 authentication flow: building authorization URL,
 * exchanging code for token, retrieving user info, and mapping it to a User object.
 *
 * @package Doppar\OAuthic\Drivers
 */
class GithubProvider extends AbstractProvider
{
    /**
     * Separator used when joining multiple scopes.
     *
     * @var string
     */
    protected string $scopeSeparator = " ";

    /**
     * Get the GitHub authorization URL to redirect the user to.
     *
     * @return string
     */
    #[\Override]
    public function getAuthUrl(): string
    {
        return "https://github.com/login/oauth/authorize?" .
            http_build_query([
                "client_id" => $this->config["client_id"],
                "redirect_uri" => $this->config["redirect"],
                "scope" => $this->getScopes(),
                "response_type" => "code",
            ]);
    }

    /**
     * Get the GitHub token endpoint URL.
     *
     * @return string
     */
    #[\Override]
    public function getTokenUrl(): string
    {
        return "https://github.com/login/oauth/access_token";
    }

    /**
     * Get the fields required to request an access token from GitHub.
     *
     * @param string $code The authorization code received from GitHub.
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
     * Get the scope string for the authorization URL.
     *
     * @return string
     */
    protected function getScopes(): string
    {
        return implode(
            $this->scopeSeparator,
            $this->config["scopes"] ?? ["user:email"],
        );
    }

    /**
     * Fetch the authenticated user's data from GitHub using the access token.
     * If GitHub does not return an email, an additional request is made to fetch verified emails.
     *
     * @param string $token
     * @return array
     */
    #[\Override]
    public function getUserByToken(#[\SensitiveParameter] string $token): array
    {
        $userResponse = Axios::to("https://api.github.com/user")
            ->withHeaders([
                "Authorization" => "token " . $token,
                "Accept" => "application/json",
            ])
            ->get()
            ->json();

        $userResponse = (array) $userResponse;

        // GitHub might not return the email in the user response,
        // So we need a separate request
        if (!isset($userResponse["email"])) {
            $emailsResponse = Axios::to("https://api.github.com/user/emails")
                ->withHeaders([
                    "Authorization" => "token " . $token,
                    "Accept" => "application/json",
                ])
                ->get()
                ->json();

            foreach ((array) $emailsResponse as $email) {
                if ($email["primary"] && $email["verified"]) {
                    $userResponse["email"] = $email["email"];
                    break;
                }
            }
        }

        return $userResponse;
    }

    /**
     * Map the raw GitHub user array to a standardized User object.
     *
     * @param array $user
     * @return \Doppar\OAuthic\User
     */
    #[\Override]
    public function mapUserToObject(array $user): User
    {
        return new User([
            "id" => $user["id"],
            "name" => $user["name"] ?? ($user["login"] ?? null),
            "email" => $user["email"] ?? null,
            "avatar" => $user["avatar_url"] ?? null,
        ]);
    }
}
