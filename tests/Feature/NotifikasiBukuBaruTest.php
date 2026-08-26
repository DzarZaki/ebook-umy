<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Prodi;
use App\Models\User;
use App\Notifications\BukuBaru;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pemberitahuan surel buku baru — opt-in, terjaga cakupannya, dan halaman
 * profil tidak pernah menjadi jalan menaikkan wewenang akun.
 */
class NotifikasiBukuBaruTest extends TestCase
{
    use RefreshDatabase;

    public function test_penerbitan_mengirim_hanya_ke_langganan_seprodi(): void
    {
        Notification::fake();

        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();
        $langganan = User::factory()->mahasiswa($prodi)->create(['notifikasi_buku_baru' => true]);
        $memilihDiam = User::factory()->mahasiswa($prodi)->create(['notifikasi_buku_baru' => false]);

        $buku = Book::factory()->create([
            'prodi_id' => $prodi->id,
            'uploaded_by' => $dosen->id,
            'is_published' => false,
        ]);

        $this->actingAs($dosen)
            ->patch(route('admin.buku.terbit', $buku), ['is_published' => '1'])
            ->assertRedirect();

        Notification::assertSentTo($langganan, BukuBaru::class);
        Notification::assertNotSentTo($memilihDiam, BukuBaru::class);
    }

    public function test_buku_umum_menjangkau_langganan_seluruh_prodi(): void
    {
        Notification::fake();

        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodiA)->create();

        $pelangganA = User::factory()->mahasiswa($prodiA)->create(['notifikasi_buku_baru' => true]);
        $pelangganB = User::factory()->mahasiswa($prodiB)->create(['notifikasi_buku_baru' => true]);

        // Draf ditaruh sebagai buku umum oleh superadmin-ish path: cukup
        // lepas prodi-nya, penerbitan tetap lewat tombol milik dosen ini.
        $buku = Book::factory()->create([
            'prodi_id' => null,
            'uploaded_by' => $dosen->id,
            'is_published' => false,
        ]);

        $this->actingAs($dosen)
            ->patch(route('admin.buku.terbit', $buku), ['is_published' => '1']);

        Notification::assertSentTo([$pelangganA, $pelangganB], BukuBaru::class);
    }

    public function test_akun_nonaktif_atau_belum_terverifikasi_tidak_dikirimi(): void
    {
        Notification::fake();

        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();

        User::factory()->mahasiswa($prodi)->create([
            'notifikasi_buku_baru' => true,
            'is_active' => false,
        ]);

        User::factory()->mahasiswa($prodi)->create([
            'notifikasi_buku_baru' => true,
            'email_verified_at' => null,
        ]);

        $buku = Book::factory()->create([
            'prodi_id' => $prodi->id,
            'uploaded_by' => $dosen->id,
            'is_published' => false,
        ]);

        $this->actingAs($dosen)
            ->patch(route('admin.buku.terbit', $buku), ['is_published' => '1']);

        Notification::assertNothingSent();
    }

    public function test_tarik_dan_terbit_ulang_tidak_mengirim_dua_kabar(): void
    {
        Notification::fake();

        $prodi = Prodi::factory()->create();
        $dosen = User::factory()->admin($prodi)->create();
        $langganan = User::factory()->mahasiswa($prodi)->create(['notifikasi_buku_baru' => true]);

        $buku = Book::factory()->create([
            'prodi_id' => $prodi->id,
            'uploaded_by' => $dosen->id,
            'is_published' => true,
        ]);

        // Menarik lalu menerbitkan ulang tanpa melewati keadaan draf yang
        // tersimpan? Transisi nyatanya lewat dua permintaan berbeda:
        $this->actingAs($dosen)->patch(route('admin.buku.terbit', $buku), ['is_published' => '0']);
        $this->actingAs($dosen)->patch(route('admin.buku.terbit', $buku), ['is_published' => '1']);

        Notification::assertSentToTimes($langganan, BukuBaru::class, 1);
    }

    public function test_preferensi_profil_mengikuti_kotak_centang(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $payloadDasar = [
            'name' => $mahasiswa->name,
            'email' => $mahasiswa->email,
        ];

        // Dicentang → langganan menyala.
        $this->actingAs($mahasiswa)
            ->patch('/profile', $payloadDasar + ['notifikasi_buku_baru' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($mahasiswa->refresh()->notifikasi_buku_baru);

        // Kotak tak dikirim sama sekali (dicentangnya dilepas) → mati.
        $this->actingAs($mahasiswa)
            ->patch('/profile', $payloadDasar)
            ->assertSessionHasNoErrors();

        $this->assertFalse($mahasiswa->refresh()->notifikasi_buku_baru);
    }

    /**
     * Halaman profil tidak boleh menjadi pintu eskalasi: kolom wewenang
     * yang diselundupkan ke dalam kiriman harus diabaikan begitu saja.
     */
    public function test_profil_mengabaikan_kolom_wewenang_yang_diselundupkan(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $prodiLama = $mahasiswa->prodi_id;

        $this->actingAs($mahasiswa)
            ->patch('/profile', [
                'name' => $mahasiswa->name,
                'email' => $mahasiswa->email,
                'role' => User::ROLE_SUPERADMIN,
                'is_active' => 1,
                'prodi_id' => null,
                'password' => 'password-palsu',
            ])
            ->assertSessionHasNoErrors();

        $mahasiswa->refresh();

        $this->assertSame(User::ROLE_MAHASISWA, $mahasiswa->role);
        $this->assertTrue($mahasiswa->is_active);
        $this->assertSame($prodiLama, $mahasiswa->prodi_id);
    }
}
