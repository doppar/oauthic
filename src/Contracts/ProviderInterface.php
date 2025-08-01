<?php

namespace Doppar\OAuthic\Contracts;

/**
 * Interface ProviderInterface
 *
 * Defines the contract for OAuth providers within the OAuthic package.
 * Each implementation should handle the redirection, user retrieval, and state handling
 * required for completing an OAuth authentication flow.
 *
 * @package Doppar\OAuthic\Contracts
 */
interface ProviderInterface
{
    /**
     * Redirect the user to the OAuth provider's authorization page.
     *
     * @return \Phaseolies\Http\Response\RedirectResponse|mixed
     */
    public function redirect();

    /**
     * Retrieve the authenticated user from the OAuth provider.
     *
     * @return \Doppar\OAuthic\Contracts\UserInterface
     */
    public function user();

    /**
     * Disable state validation for stateless OAuth authentication.
     *
     * Useful for APIs or environments where session state is not available.
     *
     * @return $this
     */
    public function stateless();
}
