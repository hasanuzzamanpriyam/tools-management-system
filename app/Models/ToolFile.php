<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tool_id', 'file_path', 'file_type', 'version', 'changelog'])]
class ToolFile extends Model
{
    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }
}
