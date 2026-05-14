<?php
namespace App\Models\Production;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Design extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'image_path',
        'approved',
        'notes',
    ];
}
