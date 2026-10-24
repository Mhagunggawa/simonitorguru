<?php

namespace Tests\Feature\Pengaturan;

use App\Http\Requests\BackupDataRequest;
use App\Http\Requests\MassDeleteRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DataManagementTest extends TestCase
{
    public function test_backup_requires_a_valid_period(): void
    {
        $request = $this->request(BackupDataRequest::class, []);

        try {
            $request->validateResolved();
            $this->fail('Validasi seharusnya gagal tanpa periode.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('start_date', $exception->errors());
            $this->assertArrayHasKey('end_date', $exception->errors());
        }
    }

    public function test_delete_only_requires_the_confirmation_word(): void
    {
        $request = $this->request(MassDeleteRequest::class, [
            'confirmation' => ' HAPUS ',
        ]);

        $request->validateResolved();

        $this->assertSame('hapus', $request->validated('confirmation'));
    }

    public function test_delete_rejects_an_invalid_confirmation_word(): void
    {
        $request = $this->request(MassDeleteRequest::class, [
            'confirmation' => 'salah',
        ]);

        $this->expectException(ValidationException::class);
        $request->validateResolved();
    }

    /**
     * @template TRequest of FormRequest
     *
     * @param  class-string<TRequest>  $requestClass
     * @param  array<string, mixed>  $data
     * @return TRequest
     */
    private function request(string $requestClass, array $data): FormRequest
    {
        $request = $requestClass::create('/', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        return $request;
    }
}
