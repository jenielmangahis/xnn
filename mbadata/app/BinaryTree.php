<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BinaryTree extends Model
{
    const PREFERENCE_LESSER_VOLUME_LEG = "LESSER_VOLUME_LEG";
    const PREFERENCE_LEFT_LEG = "LEFT_LEG";
    const PREFERENCE_RIGHT_LEG = "RIGHT_LEG";

    const POSITION_LEFT_LEG = 0;
    const POSITION_RIGHT_LEG = 1;

    protected $table = "cm_genealogy_binary";
    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'parent_id', 'created_at'];

    public function sponsor()
    {
        return $this->belongsTo(BinaryTree::class, 'parent_id', 'user_id');
    }

    public function legs()
    {
        return $this->hasMany(BinaryTree::class, "parent_id", "user_id");
    }

    public function leftLeg()
    {
        return $this->legs()->where("position", 0);
    }

    public function rightLeg()
    {
        return $this->legs()->where("position", 1);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}
