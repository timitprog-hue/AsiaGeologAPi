<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesReport;
use App\Models\SalesReportPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'photo'       => ['required','image','max:6144'], // 6MB
            'latitude'    => ['required','numeric'],
            'longitude'   => ['required','numeric'],
            'accuracy_m'  => ['nullable','numeric'],
            'captured_at' => ['required','date'],
            'notes'       => ['nullable','string','max:2000'],

            // terima alamat dari app (dua versi)
            'address'      => ['nullable','string','max:3000'],
            'address_text' => ['nullable','string','max:3000'],
        ]);

        $address = $data['address'] ?? $data['address_text'] ?? null;

        // 1) simpan report
        $report = SalesReport::create([
            'user_id'     => $user->id,
            'captured_at' => $data['captured_at'],
            'latitude'    => $data['latitude'],
            'longitude'   => $data['longitude'],
            'accuracy_m'  => $data['accuracy_m'] ?? null,
            'address'     => $address,
            'notes'       => $data['notes'] ?? null,
        ]);

        // 2) simpan file ke disk public
        $file = $request->file('photo');
        $ext  = $file->getClientOriginalExtension() ?: 'jpg';
        $name = Str::uuid()->toString() . '.' . $ext;

        $dir = "reports/{$user->id}/{$report->id}";
        $stored = Storage::disk('public')->putFileAs($dir, $file, $name);

        // ✅ kalau gagal simpan file: hapus report biar DB gak “yatim”
        if (!$stored) {
            $report->delete();
            return response()->json([
                'message' => 'Gagal menyimpan foto ke storage (disk public).'
            ], 500);
        }

        // $stored = "reports/{user}/{report}/{file}.jpg"
        $relPath = $stored;

        SalesReportPhoto::create([
            'sales_report_id' => $report->id,
            'file_path'       => $relPath,
            'mime_type'       => $file->getMimeType(),
            'size_bytes'      => $file->getSize(),
        ]);

        // ✅ URL publik (tanpa Storage::url biar editor ga merah)
        $photoUrl = asset('storage/' . ltrim($relPath, '/'));

        return response()->json([
            'message'   => 'Report uploaded',
            'report_id' => $report->id,
            'photo_url' => $photoUrl,
            'address'   => $address,
        ], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $q = SalesReport::query()
            ->with([
                'photos',
                'user:id,name,email,role,is_active'
            ])
            ->latest('captured_at');

        // sales hanya lihat miliknya sendiri
        if ($user->role === 'sales') {
            $q->where('user_id', $user->id);
        }

        // admin bisa filter per sales
        if ($user->role === 'admin' && $request->filled('user_id')) {
            $q->where('user_id', $request->user_id);
        }

        // filter tanggal (opsional)
        if ($request->filled('date_from')) {
            $q->whereDate('captured_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('captured_at', '<=', $request->date_to);
        }

        // search (alamat / catatan)
        if ($request->filled('q')) {
            $kw = trim($request->q);
            $q->where(function ($qq) use ($kw) {
                $qq->where('address', 'like', "%{$kw}%")
                   ->orWhere('notes', 'like', "%{$kw}%");
            });
        }

        $perPage = (int) ($request->get('per_page', 20));
        $perPage = max(5, min(50, $perPage));

        return response()->json(
            $q->paginate($perPage)
        );
    }

    public function show(Request $request, SalesReport $report)
    {
        $user = $request->user();

        if ($user->role === 'sales' && $report->user_id !== $user->id) {
            abort(403);
        }

        $report->load(['user:id,name,email,role,is_active','photos']);
        return response()->json($report);
    }

    public function photo(Request $request, SalesReportPhoto $photo)
    {
        $user = $request->user();

        $photo->loadMissing('report');

        if ($user->role === 'sales' && $photo->report->user_id !== $user->id) {
            abort(403);
        }

        $path = $photo->file_path;

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }
}
