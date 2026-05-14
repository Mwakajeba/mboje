<?php
namespace App\Models\Production;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'type', // fabric, thread, ink, etc.
        'quantity_in_stock',
        'unit',
    ];
}
