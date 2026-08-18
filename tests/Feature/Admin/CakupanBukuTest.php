<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CakupanBukuTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_tidak_melihat_buku_umum_dosen_lain(): void
    {
        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();

        $dosenA = User::factory()->admin($prodiA)->create();
        $dosenB = User::factory()->admin($prodiB)->create();

        Book::factory()->create([
            'title' => 'Zzz Buku Umum Milik Dosen Lain',
            'prodi_id' => null,
            'uploaded_by' => $dosenB->id,
        ]);

        $this->actingAs($dosenA)
            ->get(route('admin.buku.index'))
            ->assertOk()
            ->assertDontSee('Zzz Buku Umum Milik Dosen Lain');
    }

    public function test_dosen_tetap_melihat_buku_umum_miliknya(): void
    {
        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        Book::factory()->create([
            'title' => 'Zzz Buku Umum Milik Saya',
            'prodi_id' => null,
            'uploaded_by' => $dosen->id,
        ]);

        $this->actingAs($dosen)
            ->get(route('admin.buku.index'))
            ->assertOk()
            ->assertSee('Zzz Buku Umum Milik Saya');
    }
}