<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';
    const DELETED_AT = 'delete_at';

    protected $fillable = [
        'key',
        'value',
        'create_by',
        'update_by',
        'delete_by',
    ];
}
