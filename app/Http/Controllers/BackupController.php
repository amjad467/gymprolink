<?php

namespace App\Http\Controllers;

use App\Models\AccessEvent;
use App\Models\AuditLog;
use App\Models\Gym;
use App\Models\GymShop;
use App\Models\InventoryBatch;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\Membership;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Scopes\GymScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    public function index()
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);

        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'filename' => $file->getFilename(),
                'size' => round($file->getSize() / 1024, 2), // in KB
                'size_formatted' => $file->getSize() >= 1048576 
                    ? round($file->getSize() / 1048576, 2) . ' MB' 
                    : round($file->getSize() / 1024, 1) . ' KB',
                'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                'type' => str_ends_with($file->getFilename(), '.sqlite') ? 'داتابەیسی تەواو (SQLite)' : 'ئەرشیفی داتا (JSON)',
                'extension' => $file->getExtension(),
            ];
        }

        // Sort latest backups first
        usort($backups, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return view('backups.index', compact('activeGym', 'backups'));
    }

    /**
     * دروستکردنی باک ئەپی دەستبەجێ (Database Copy & JSON Archive)
     */
    public function create(Request $request)
    {
        $type = $request->get('type', 'db'); // 'db' or 'json'
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $timeStr = date('Y-m-d_His');

        if ($type === 'db') {
            $dbPath = database_path('database.sqlite');
            if (File::exists($dbPath)) {
                $backupName = "GymPro_Full_DB_{$timeStr}.sqlite";
                File::copy($dbPath, $this->backupDir . '/' . $backupName);
            } else {
                return back()->with('error', 'فایلی سەرەکی داتابەیس نەدۆزرایەوە.');
            }
        } else {
            // دروستکردنی باک ئەپی داتا بە شێوەی JSON
            $backupData = [
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'platform' => 'GymPro Enterprise Cloud v2.0',
                    'gym_id' => $activeGymId,
                    'created_by' => auth()->user()?->name ?? 'سیستەم',
                ],
                'gyms' => Gym::all(),
                'users' => User::all(['id', 'name', 'email', 'phone', 'is_active', 'created_at']),
                'members' => Member::all(),
                'memberships' => Membership::all(),
                'invoices' => Invoice::all(),
                'pos_sales' => PosSale::with('items')->get(),
                'products' => Product::with('variants.batches')->get(),
                'attendances' => MemberAttendance::latest()->take(500)->get(),
                'access_events' => AccessEvent::latest()->take(500)->get(),
            ];

            $backupName = "GymPro_Data_Archive_{$timeStr}.json";
            File::put($this->backupDir . '/' . $backupName, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // تۆمارکردنی لۆگی سکیوریتی بۆ دروستکردنی باک ئەپ
        AuditLog::create([
            'gym_id' => $activeGymId,
            'user_id' => auth()->id() ?? 1,
            'action' => 'FULL_SYSTEM_BACKUP_CREATED',
            'auditable_type' => Gym::class,
            'auditable_id' => $activeGymId,
            'new_values' => [
                'type' => $type,
                'file_name' => $backupName,
                'created_at' => now()->toIso8601String(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "باک ئەپی نوێ [{$backupName}] بە سەرکەوتوویی دروستکرا و ئامادەیە بۆ داگرتن.");
    }

    /**
     * داگرتنی فایلی باک ئەپ
     */
    public function download(string $filename): BinaryFileResponse
    {
        $filePath = $this->backupDir . '/' . basename($filename);

        if (!File::exists($filePath)) {
            abort(404, 'فایلی داواکراوی باک ئەپ بوونی نییە.');
        }

        return response()->download($filePath);
    }

    /**
     * سڕینەوەی فایلی باک ئەپ
     */
    public function destroy(string $filename)
    {
        $filePath = $this->backupDir . '/' . basename($filename);

        if (File::exists($filePath)) {
            File::delete($filePath);
            return back()->with('success', "فایلی باک ئەپ بە سەرکەوتوویی سڕایەوە.");
        }

        return back()->with('error', 'فایل نەدۆزرایەوە.');
    }

    /**
     * گەڕاندنەوەی داتابەیس لە فایلی باک ئەپ (Database Restore)
     */
    public function restore(Request $request, string $filename)
    {
        $filename = basename($filename);
        $filePath = $this->backupDir . '/' . $filename;

        if (!File::exists($filePath)) {
            return back()->with('error', 'فایلی باک ئەپ نەدۆزرایەوە.');
        }

        if (!str_ends_with($filename, '.sqlite')) {
            return back()->with('error', 'تەنها فایلەکانی جۆری SQLite دەتوانرێت ڕاستەوخۆ بگێڕدرێنەوە.');
        }

        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()?->id ?? 1;
        $dbPath = database_path('database.sqlite');

        // ١. دروستکردنی باک ئەپی سەلامەتی پێش گەڕاندنەوە
        $preRestoreSnapshot = null;
        if (File::exists($dbPath)) {
            $preRestoreSnapshot = "Safety_PreRestore_" . date('Y-m-d_His') . ".sqlite";
            File::copy($dbPath, $this->backupDir . '/' . $preRestoreSnapshot);
        }

        // ٢. جێبەجێکردنی گەڕاندنەوەی داتابەیس
        File::copy($filePath, $dbPath);

        // ٣. تۆمارکردنی لۆگی سکیوریتی لە ئۆدیت لۆگ
        AuditLog::create([
            'gym_id' => $activeGymId,
            'user_id' => auth()->id() ?? 1,
            'action' => 'DATABASE_RESTORED_FROM_BACKUP',
            'auditable_type' => Gym::class,
            'auditable_id' => $activeGymId,
            'new_values' => [
                'restored_file' => $filename,
                'restored_at' => now()->toIso8601String(),
                'safety_snapshot' => $preRestoreSnapshot,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "داتابەیس بە سەرکەوتوویی لە باک ئەپی [{$filename}] گەڕێندرایەوە. وێنەیەکی سەلامەتیش لە [{$preRestoreSnapshot}] پاشەکەوت کرا.");
    }
}
