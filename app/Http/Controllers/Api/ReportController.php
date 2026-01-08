<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesReport;
use App\Models\SalesReportPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

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
        $name = Str::uuid()->toString().'.'.$ext;

        // path relatif di disk public: reports/{user}/{report}/{file}
        $relPath = "reports/{$user->id}/{$report->id}/{$name}";
        Storage::disk('public')->putFileAs(
            "reports/{$user->id}/{$report->id}",
            $file,
            $name
        );

        // 3) buat url publik (pakai disk public)
        $url = Storage::disk('public')->url($relPath);
        // hasilnya biasanya: http://IP:PORT/storage/reports/...

        SalesReportPhoto::create([
    'sales_report_id' => $report->id,
    'file_path'       => $relPath,
    'mime_type'       => $file->getMimeType(),
    'size_bytes'      => $file->getSize(),
]);


        return response()->json([
    'message'   => 'Report uploaded',
    'report_id' => $report->id,
    'photo_url' => Storage::disk('public')->url($relPath),
    'address'   => $address,
], 201);

    }


    // index & show biarin dulu, sudah oke

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

    // optional: per_page
    $perPage = (int) ($request->get('per_page', 20));
    $perPage = max(5, min(50, $perPage));

    // ✅ return paginate langsung (format standar Laravel)
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

        $report->load(['user:id,name,email,role','photos']);
        return response()->json($report);
    }

    public function photo(Request $request, SalesReportPhoto $photo)
{
    $user = $request->user();

    // ambil report terkait
    $photo->loadMissing('report');

    // sales hanya boleh lihat foto miliknya
    if ($user->role === 'sales' && $photo->report->user_id !== $user->id) {
        abort(403);
    }

    // boss/marketing boleh
    $path = $photo->file_path; // contoh: reports/1/14/xxx.jpg

    if (!$path || !Storage::disk('public')->exists($path)) {
        abort(404);
    }

    // return file binary
    return response()->file(Storage::disk('public')->path($path));
}
}


