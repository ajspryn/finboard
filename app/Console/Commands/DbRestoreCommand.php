<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DbRestoreCommand extends Command
{
     protected $signature = 'db:restore {file}';
     protected $description = 'Restore database from a backup file using scripts/db_restore.sh';

     public function handle()
     {
          $file = $this->argument('file');
          $script = base_path('scripts/db_restore.sh');
          $path = $file;
          // If user passed relative path inside storage/backups, accept it
          if (!file_exists($path) && file_exists(storage_path('app/' . $file))) {
               $path = storage_path('app/' . $file);
          }

          if (!file_exists($path)) {
               $this->error("Backup file not found: $file");
               return 1;
          }

          $cmd = escapeshellcmd($script) . ' ' . escapeshellarg($path);
          $this->warn('This command will DROP and recreate the target database.');
          if (!$this->confirm('Proceed with restore?')) {
               $this->info('Restore aborted.');
               return 0;
          }

          $output = [];
          $return = 0;
          exec($cmd, $output, $return);
          foreach ($output as $line) {
               $this->line($line);
          }

          if ($return !== 0) {
               $this->error('Restore failed.');
               return 1;
          }

          $this->info('Restore complete.');
          return 0;
     }
}
