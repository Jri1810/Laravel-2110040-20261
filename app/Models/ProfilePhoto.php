<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'mime', 'data'])]
#[Hidden(['data'])]
class ProfilePhoto extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Isi biner gambar hasil decode dari kolom data (base64).
     */
    public function binary(): string
    {
        return (string) base64_decode((string) $this->data, true);
    }
}
