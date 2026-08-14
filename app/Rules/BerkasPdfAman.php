<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Menolak PDF yang secara berkas sah tetapi tidak dapat diolah aplikasi ini.
 *
 * Pemeriksaan di sini sengaja murah: hanya membaca kepala dan ekor berkas,
 * tanpa menjalankan proses apa pun. Pemeriksaan yang mahal — apakah qpdf
 * sungguh sanggup membacanya — dilakukan sekali saja di BukuRequest.
 */
class BerkasPdfAman implements ValidationRule
{
    /** Sebagian PDF menaruh data pendahulu sebelum tandanya, jadi jangan hanya lihat byte pertama. */
    private const KEPALA = 1024;

    /** Trailer PDF ada di ujung berkas; segini cukup untuk menjangkaunya. */
    private const EKOR = 4096;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Bukan berkas unggahan: biar aturan 'file' dan 'mimes' yang bicara.
        if (! $value instanceof UploadedFile) {
            return;
        }

        if (! $value->isValid()) {
            $fail('Berkas gagal terunggah sepenuhnya. Coba unggah ulang.');

            return;
        }

        $pegangan = @fopen($value->getPathname(), 'rb');

        if ($pegangan === false) {
            $fail('Berkas sementara tidak dapat dibaca oleh server. Coba unggah ulang.');

            return;
        }

        $kepala = (string) fread($pegangan, self::KEPALA);

        $ukuran = $value->getSize() ?: 0;
        $mulaiEkor = max(0, $ukuran - self::EKOR);
        fseek($pegangan, $mulaiEkor);
        $ekor = (string) stream_get_contents($pegangan);

        fclose($pegangan);

        if (! str_contains($kepala, '%PDF-')) {
            $fail('Berkas ini tidak dikenali sebagai PDF yang sah.');

            return;
        }

        // Setiap PDF utuh diakhiri penanda %%EOF. Ketiadaannya hampir selalu
        // berarti unggahan atau penyalinan yang terputus di tengah jalan.
        if (! str_contains($ekor, '%%EOF')) {
            $fail('Berkas PDF ini tampak terpotong atau rusak. Coba buka di komputer Anda, simpan ulang, lalu unggah kembali.');

            return;
        }

        if (preg_match('~/Encrypt[\s/<\[]~', $ekor) === 1) {
            $fail('Berkas PDF ini terkunci sandi, sehingga tidak dapat ditampilkan maupun distempel oleh sistem. Buka berkasnya, simpan sebagai PDF baru tanpa sandi, lalu unggah kembali.');
        }
    }
}