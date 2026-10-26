<?php

namespace App\Credentials;

use App\Models\Credential;

class CredentialResolver
{
    public function find(int $id, int $userId): ?Credential
    {
        return Credential::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }
}
