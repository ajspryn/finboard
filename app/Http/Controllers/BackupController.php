<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

class BackupController extends Controller
{
     public function index()
     {
          $dir = storage_path('backups');
          $files = [];
          if (is_dir($dir)) {
               foreach (glob($dir . '/*') as $path) {
                    $files[] = [
                         'name' => basename($path),
                         'path' => $path,
                         'size' => filesize($path),
                         'mtime' => filemtime($path),
                    ];
               }
          }
          usort($files, function ($a, $b) {
               return $b['mtime'] <=> $a['mtime'];
          });

          // Simple pagination for files array
          $perPage = 10;
          $page = max(1, (int) request()->get('page', 1));
          $total = count($files);
          $offset = ($page - 1) * $perPage;
          $pagedItems = array_slice($files, $offset, $perPage);

          $paginator = new LengthAwarePaginator($pagedItems, $total, $perPage, $page, [
               'path' => request()->url(),
               'query' => request()->query(),
          ]);

          return view('admin.backups', ['files' => $paginator]);
     }

     public function createBackup()
     {
          $script = base_path('scripts/db_backup.sh');
          // Ensure common Homebrew mysql-client paths are available to the executed process
          $homebrewPaths = '/opt/homebrew/bin:/opt/homebrew/opt/mysql-client/bin:/usr/local/opt/mysql-client/bin:/usr/local/bin';
          $cmd = 'PATH="' . $homebrewPaths . ':$PATH" ' . escapeshellcmd($script) . ' 2>&1';
          exec($cmd, $output, $return);
          if ($return !== 0) {
               $msg = implode("\n", $output);
               // Suggest installation if mysqldump not found
               if (stripos($msg, 'mysqldump not found') !== false || stripos($msg, 'mysqldump: command not found') !== false) {
                    $msg .= '\nSuggestion: install MySQL client (Homebrew: `brew install mysql-client`) and ensure the binary is readable by the webserver user.';
               }
               return redirect()->route('admin.backups.index')->with('error', 'Backup failed: ' . Str::limit($msg, 750));
          }
          return redirect()->route('admin.backups.index')->with('status', 'Backup created.');
     }

     public function restore(Request $request)
     {
          $request->validate(['file' => 'required|string']);
          $file = basename($request->input('file'));
          $storagePath = storage_path('backups/' . $file);
          if (!file_exists($storagePath)) {
               return redirect()->route('admin.backups.index')->with('error', 'Backup file not found.');
          }

          $script = base_path('scripts/db_restore.sh');
          $cmd = escapeshellcmd($script) . ' ' . escapeshellarg($storagePath) . ' 2>&1';
          exec($cmd, $output, $return);
          if ($return !== 0) {
               $msg = implode("\n", $output ?? []);
               return redirect()->route('admin.backups.index')->with('error', 'Restore failed: ' . Str::limit($msg, 250));
          }
          return redirect()->route('admin.backups.index')->with('status', 'Restore complete.');
     }

     /**
      * Upload a backup file to storage/backups. Does not auto-restore.
      */
     public function upload(Request $request)
     {
          $request->validate([
               'backup_file' => 'required|file|max:512000', // max ~500MB
          ]);

          $uploaded = $request->file('backup_file');
          $origName = $uploaded->getClientOriginalName();
          $name = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);

          $destDir = storage_path('backups');
          if (!is_dir($destDir)) {
               mkdir($destDir, 0755, true);
          }

          $destPath = $destDir . DIRECTORY_SEPARATOR . $name;
          $uploaded->move($destDir, $name);

          // If requester expects JSON (AJAX), return JSON response
          if ($request->wantsJson() || $request->ajax()) {
               $resp = ['status' => 'uploaded', 'file' => $name];
               // Optionally auto-restore
               if ($request->input('auto_restore')) {
                    $script = base_path('scripts/db_restore.sh');
                    $storagePath = $destPath;
                    $cmd = escapeshellcmd($script) . ' ' . escapeshellarg($storagePath) . ' 2>&1';
                    exec($cmd, $output, $return);
                    if ($return !== 0) {
                         $resp['restore'] = 'failed';
                         $resp['message'] = implode("\n", $output ?? []);
                    } else {
                         $resp['restore'] = 'ok';
                    }
               }
               return response()->json($resp);
          }

          return redirect()->route('admin.backups.index')->with('status', "Uploaded backup: $name");
     }

     public function download($file)
     {
          $file = basename($file);
          $path = storage_path('backups/' . $file);
          if (!file_exists($path)) {
               abort(404);
          }
          return response()->download($path, $file);
     }
}
