<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ringkasan aktivitas unduhan untuk dosen pengelola.
 */
class StatistikController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Buku yang menjadi tanggung jawab dosen ini.
        $bukuIds = Book::query()
            ->where('prodi_id', $user->prodi_id)
            ->orWhere(fn (Builder $q) => $q->whereNull('prodi_id')->where('uploaded_by', $user->id))
            ->pluck('id');

        $catatan = DownloadLog::whereIn('book_id', $bukuIds);

        return view('admin.statistik', [
            'totalUnduhan' => (clone $catatan)->count(),
            'unduhanSebulan' => (clone $catatan)->where('created_at', '>=', now()->subDays(30))->count(),
            'pengunduhUnik' => (clone $catatan)->distinct('user_id')->count('user_id'),
            'jumlahMahasiswa' => User::where('role', User::ROLE_MAHASISWA)->where('prodi_id', $user->prodi_id)->count(),
            'grafikHarian' => $this->grafikHarian($bukuIds),
            'bukuTerpopuler' => $this->bukuTerpopuler($bukuIds),
            'catatanTerbaru' => DownloadLog::with(['book', 'user'])
                ->whereIn('book_id', $bukuIds)
                ->latest()
                ->paginate(20),
        ]);
    }

    /**
     * Jumlah unduhan per hari selama 14 hari terakhir.
     *
     * @return array<int, array{label: string, jumlah: int}>
     */
    private function grafikHarian($bukuIds): array
    {
        $perTanggal = DownloadLog::whereIn('book_id', $bukuIds)
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->get()
            ->groupBy(fn (DownloadLog $log) => $log->created_at->toDateString())
            ->map->count();

        $hasil = [];

        for ($mundur = 13; $mundur >= 0; $mundur--) {
            $tanggal = now()->subDays($mundur);

            $hasil[] = [
                'label' => $tanggal->translatedFormat('d M'),
                'jumlah' => $perTanggal[$tanggal->toDateString()] ?? 0,
            ];
        }

        return $hasil;
    }

    /**
     * Sepuluh buku dengan unduhan terbanyak.
     * Memakai whereHas alih-alih having agar berjalan di MySQL maupun SQLite.
     */
    private function bukuTerpopuler($bukuIds)
    {
        return Book::withCount('downloadLogs')
            ->whereIn('id', $bukuIds)
            ->whereHas('downloadLogs')
            ->orderByDesc('download_logs_count')
            ->take(10)
            ->get();
    }
}
