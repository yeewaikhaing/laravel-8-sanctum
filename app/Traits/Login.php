<?php
namespace App\Traits;

use Laravel\Sanctum\PersonalAccessToken;

trait Login {
    public function findUserByToken($bearerToken) {
        $token = PersonalAccessToken::findToken($bearerToken);

    $user = $token->tokenable;

    return $user;
    }
}