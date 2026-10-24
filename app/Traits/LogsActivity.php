<?php

namespace App\Traits;

use App\Helpers\ActivityLogger;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            $properties = ['attributes' => $model->getAttributes()];
            ActivityLogger::log('CREATE', 'Menambahkan ' . class_basename($model) . ' ID: ' . $model->getKey(), $properties);
        });

        static::updated(function ($model) {
            // Get original attributes before update and only the ones that changed
            $changes = $model->getChanges();
            $original = array_intersect_key($model->getOriginal(), $changes);
            
            $properties = [
                'old' => $original,
                'new' => $changes
            ];
            ActivityLogger::log('UPDATE', 'Mengubah ' . class_basename($model) . ' ID: ' . $model->getKey(), $properties);
        });

        static::deleted(function ($model) {
            $properties = ['attributes' => $model->getAttributes()];
            ActivityLogger::log('DELETE', 'Menghapus ' . class_basename($model) . ' ID: ' . $model->getKey(), $properties);
        });
    }
}
