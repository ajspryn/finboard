<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DbRestoreCommand extends Command
{
     protected $signature = 'db:restore {file} {--force : Skip confirmation and run non-interactively}';
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

          $cmd = 'NONINTERACTIVE=1 ' . $this->shellEscapeArg($script) . ' ' . $this->shellEscapeArg($path) . ($force ? ' --force' : '');
          $this->warn('This command will DROP and recreate the target database.');
          $force = $this->option('force') || !$this->input->isInteractive();
          if (!$force) {
               if (!$this->confirm('Proceed with restore?')) {
                    $this->info('Restore aborted.');
                    return 0;
               }
          }
          [$output, $return] = $this->runShellCommand($cmd);
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

     protected function shellEscapeArg(string $value): string
     {
          if (function_exists('escapeshellarg')) {
               return \escapeshellarg($value);
          }
          return "'" . str_replace("'", "'\\''", $value) . "'";
     }

     protected function runShellCommand(string $cmd): array
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
          $msg = 'Command execution disabled in PHP (disabled_functions). Run this command from the CLI instead: php artisan db:restore <file>';
          return [[$msg], 1];
     }
}
