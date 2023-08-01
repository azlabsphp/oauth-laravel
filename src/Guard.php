<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Auth\JwtGuard;

use Drewlabs\Auth\Jwt\Contracts\TokenProvider;
use Drewlabs\Auth\Jwt\TransientToken;
use Drewlabs\Contracts\OAuth\HasApiTokens;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Reflector;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class Guard
{
    /**
     * The authentication factory implementation.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    private $auth;

    /**
     * @var TokenProvider
     */
    private $provider;

    /**
     * @var string
     */
    private $defaultGuard = 'web';

    /**
     * Create guard class instance.
     *
     * @param string $default
     */
    public function __construct(TokenProvider $provider, AuthFactory $auth = null, $default = 'web')
    {
        $this->auth = $auth;
        $this->defaultGuard = $default;
        $this->provider = $provider;
    }

    /**
     * Retrieve the authenticated user for the incoming request.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function __invoke($request)
    {
        if ($tokenable = $this->forDefaultGuards()) {
            return $tokenable;
        }
        if ($bearerToken = BearerToken::fromRequest($request)) {
            return $this->provider->findByBearerToken((string) $bearerToken);
        }
    }

    private function forDefaultGuards()
    {
        if (null !== $this->auth) {
            try {
                foreach (Arr::wrap($this->defaultGuard) as $guard) {
                    /*
                     * @var HasApiTokens
                     */
                    if ($user = $this->auth->guard($guard)->user()) {
                        return $this->supportsTokens($user) ? $user->withAccessToken(new TransientToken()) : $user;
                    }
                }
            } catch (\Throwable $e) {
                return;
            }
        }
    }

    /**
     * Determine if the tokenable model supports API tokens.
     *
     * @param mixed $tokenable
     *
     * @return bool
     */
    private function supportsTokens($tokenable = null)
    {
        if (null === $tokenable) {
            return false;
        }

        return $tokenable instanceof HasApiTokens || Reflector::usesRecursive($tokenable, 'withAccessToken');
    }
}
