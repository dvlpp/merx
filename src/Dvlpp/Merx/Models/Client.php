<?php

namespace Dvlpp\Merx\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = "merx_clients";

    protected $fillable = [
        "ref"
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, "client_id");
    }
}