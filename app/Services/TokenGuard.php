<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class TokenGuard implements Guard
{
    protected $provider;
    protected $request;
    protected $user;

    public function __construct(UserProvider $provider, Request $request = null)
    {
        $this->provider = $provider;
        $this->request = $request ?: app('request');
    }

    public function check()
    {
        return !is_null($this->user());
    }

    public function guest()
    {
        return !$this->check();
    }

    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $token = $this->request->bearerToken();
        
        if (!$token) {
            return null;
        }

        // For this simple implementation, we'll just check if the token exists
        // In a real application, you might want to validate the token against a database
        // or use JWT tokens, etc.
        
        // For now, we'll assume any non-empty token is valid and return the first user
        // This is just for demonstration purposes
        $user = $this->provider->retrieveById(1);
        
        if ($user) {
            $this->user = $user;
        }

        return $this->user;
    }

    public function id()
    {
        if ($user = $this->user()) {
            return $user->getAuthIdentifier();
        }

        return null;
    }

    public function validate(array $credentials = [])
    {
        return false;
    }

    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
        return $this;
    }

    public function hasUser()
    {
        return !is_null($this->user);
    }

    public function forgetUser()
    {
        $this->user = null;
    }
}
