<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisSurat extends Model
{
    protected $table = 'jenis_surat';

    protected $fillable = [
        'nama',
        'tipe', // 'masuk' atau 'keluar'
        'footer'
    ];

    // Relasi: JenisSurat punya banyak FieldDefinition
    public function fieldDefinitions()
    {
        return $this->hasMany(FieldDefinition::class);
    }

    // Relasi: JenisSurat punya banyak Surat
    public function surat()
    {
        return $this->hasMany(Surat::class);
    }
}
