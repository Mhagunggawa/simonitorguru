<?php

namespace App\Data;

readonly class BackupArchive
{
    public function __construct(
        public string $path,
        public string $filename,
    ) {}
}
