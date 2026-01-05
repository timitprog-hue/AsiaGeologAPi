<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SalesReportPhoto extends Model
{
    protected $fillable = [
        'sales_report_id',
        'file_path',
        'file_url',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'sha256',
    ];

    protected $appends = ['file_url'];

    public function report()
    {
        return $this->belongsTo(SalesReport::class, 'sales_report_id');
    }

    // ✅ file_url selalu public: /storage/...
    public function getFileUrlAttribute()
    {
        $path = $this->file_path;

        if (!$path) return null;

        // pastikan bentuknya /storage/...
        // hasil: http://IP:PORT/storage/reports/...
        return Storage::disk('public')->url($path);
    }
}
