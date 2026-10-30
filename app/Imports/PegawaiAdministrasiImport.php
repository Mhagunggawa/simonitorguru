<?php

namespace App\Imports;

use App\Models\PegawaiAdministrasi;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PegawaiAdministrasiImport implements ToCollection
{
    public int $importedCount = 0;
    public int $skippedCount = 0;

    public function collection(Collection $rows): void
    {
        $headerWords = ['no', 'nomor', 'nama', 'nama pegawai', 'nama lengkap', 'nama_pegawai', 'name', 'full name'];

        foreach ($rows as $index => $row) {
            $values = array_values(is_array($row) ? $row : $row->toArray());

            $values = array_map(function($v) {
                return is_string($v) ? trim($v) : $v;
            }, $values);

            $nonEmpty = array_values(array_filter($values, fn($v) => !is_null($v) && $v !== ''));

            if (empty($nonEmpty)) {
                continue;
            }

            $candidateName = null;

            if (count($nonEmpty) === 1) {
                $candidateName = (string)$nonEmpty[0];
            } else {
                if (is_numeric($nonEmpty[0]) && isset($nonEmpty[1])) {
                    $candidateName = (string)$nonEmpty[1];
                } else {
                    $candidateName = (string)$nonEmpty[0];
                }
            }

            $candidateNameTrimmed = trim((string)$candidateName);

            if (in_array(strtolower($candidateNameTrimmed), $headerWords)) {
                continue;
            }

            if (!empty($candidateNameTrimmed)) {
                $exists = PegawaiAdministrasi::where('nama', $candidateNameTrimmed)->first();
                if (!$exists) {
                    PegawaiAdministrasi::create([
                        'nama' => $candidateNameTrimmed,
                    ]);
                    $this->importedCount++;
                } else {
                    $this->skippedCount++;
                }
            }
        }
    }
}
