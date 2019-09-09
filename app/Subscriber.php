<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscriber extends Model
{
    use SoftDeletes;
    public $table = "subscribers";
    protected $fillable = [
        'email',
    ];

    public function __construct(array $attributes = [])
    {
        // $this->setTable('subscribers');
        parent::__construct($attributes);
    }
}
