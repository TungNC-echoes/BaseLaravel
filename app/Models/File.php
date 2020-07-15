<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fileable_type', 'fileable_id', 'title', 'description', 'size', 'path', 'type'
    ];

    public function fileable() {
        return $this->morphTo('fileable');
    }

    /* @array $appends */
    public $appends = ['url', 'uploaded_time', 'size_in_kb'];

    public function getUrlAttribute()
    {
        return Storage::disk('s3')->url($this->path);
    }

    public function getUploadedTimeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'auth_by');
    }

    public function getSizeInKbAttribute()
    {
        return round($this->size / 1024, 2);
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($file) {
            $file->auth_by = Auth::user()->id;
        });
    }
}
