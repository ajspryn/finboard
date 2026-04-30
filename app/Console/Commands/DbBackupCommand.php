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
          $cmd = $this->shellEscapeArg($script) . ' ' . $this->shellEscapeArg($dest ?? '');

          $this->info('Running backup...');
          [$output, $return] = $this->runCommand($cmd);

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

     private function shellEscapeArg(string $value): string
     {
          if (function_exists('escapeshellarg')) {
               return \escapeshellarg($value);
          }
          return "'" . str_replace("'", "'\\''", $value) . "'";
     }

     private function runCommand(string $cmd): array
     {
          if (function_exists('exec')) {
               $out = [];
               $ret = 0;
               \exec($cmd, $out, $ret);
               return [$out, $ret];
          }
          if (function_exists('shell_exec')) {
               $o = \shell_exec($cmd);
               $lines = $o === null ? [] : explode("\n", trim($o));
               return [$lines, strlen($o) ? 0 : 1];
          }
          $msg = 'Command execution disabled in PHP (disabled_functions). Run this command from the CLI instead: php artisan db:backup';
          return [[$msg], 1];
     }
}
