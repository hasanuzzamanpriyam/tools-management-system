<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function record(
        Request $request,
        string $action,
        ?Model $entity = null,
        array $metadata = [],
    ): AuditLog {
        $actor = $request->user();

        return AuditLog::create([
            'user_id' => $actor instanceof Authenticatable ? $actor->getAuthIdentifier() : null,
            'action' => $action,
            'entity_type' => $entity?->getTable(),
            'entity_id' => $entity?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => $request->ip(),
        ]);
    }
}
