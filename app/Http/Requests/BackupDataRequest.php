<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class BackupDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'mode' => ['nullable', 'string', 'in:all,range'],
            'start_date' => ['required_if:mode,range', 'nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required_if:mode,range', 'nullable', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'mode' => 'mode pencadangan',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal akhir',
        ];
    }

    public function isAllMedia(): bool
    {
        return $this->input('mode', 'all') !== 'range';
    }

    public function startDate(): ?Carbon
    {
        if ($this->isAllMedia() || ! $this->filled('start_date')) {
            return null;
        }

        return Carbon::parse((string) $this->input('start_date'))->startOfDay();
    }

    public function endDate(): ?Carbon
    {
        if ($this->isAllMedia() || ! $this->filled('end_date')) {
            return null;
        }

        return Carbon::parse((string) $this->input('end_date'))->endOfDay();
    }
}
