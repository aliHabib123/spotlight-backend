<?php

namespace App\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class UsernameEmailAuthProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) || 
            (count($credentials) === 1 && 
             array_key_exists('password', $credentials))) {
            return null;
        }
        
        // First, we will create a query builder to fetch a user based on either email or username
        $query = $this->createModel()->newQuery();
        
        // If both fields are provided, prioritize email
        if (isset($credentials['email']) && isset($credentials['username'])) {
            // We'll try email first in the next step and username as fallback if no match
            $query->where('email', $credentials['email']);
        }
        // If only email is provided
        elseif (isset($credentials['email'])) {
            $query->where('email', $credentials['email']);
        }
        // If only username is provided
        elseif (isset($credentials['username'])) {
            $query->where('username', $credentials['username']);
        }
        
        // Filter out the password from credentials to avoid a potential SQL injection
        foreach ($credentials as $key => $value) {
            if ($key !== 'password') {
                $query->where($key, $value);
            }
        }
        
        return $query->first();
    }
    
    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'];
        
        return $this->hasher->check($plain, $user->getAuthPassword());
    }
}
