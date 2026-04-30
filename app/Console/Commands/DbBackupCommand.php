<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DbBackupCommand extends Command
{
     protected $signature = 'db:backup {--dest=}';
     protected $description = 'Create database backup using scripts/db_backup.sh';

     public function handle()
     {
          $dest = $this->option('dest');
          $script = base_path('scripts/db_backup.sh');
          $cmd = escapeshellcmd($script) . ' ' . escapeshellarg($dest ?? '');

          $this->info('Running backup...');
          $output = [];
          $return = 0;
          exec($cmd, $output, $return);

          foreach ($output as $line) {
               $this->line($line);
          }

          if ($return !== 0) {
               $this->error('Backup command failed.');
               return 1;
          }

          $this->info('Backup finished.');
          return 0;
     }
}
