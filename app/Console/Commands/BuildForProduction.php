<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BuildForProduction extends Command
{
    protected $signature = 'app:build {--zip}';
    protected $description = 'Genera la carpeta build lista para cPanel';

    public function handle(): int
    {
        $root = base_path();
        $buildRoot = base_path('build');
        File::ensureDirectoryExists($buildRoot);

        $target = $buildRoot.'/infocus_build';
        if (File::exists($target)) {
            File::deleteDirectory($target);
        }
        File::ensureDirectoryExists($target);

        $include = [
            'app',
            'bootstrap',
            'config',
            'database',
            'public',
            'resources/views',
            'routes',
            'storage',
            'vendor',
            '.env.example',
            'composer.json',
            'composer.lock',
            'artisan',
            '.htaccess',
        ];

        foreach ($include as $item) {
            $src = $root.DIRECTORY_SEPARATOR.$item;
            $dst = $target.DIRECTORY_SEPARATOR.$item;
            if (is_dir($src)) {
                File::ensureDirectoryExists(dirname($dst));
                File::copyDirectory($src, $dst);
            } elseif (is_file($src)) {
                File::ensureDirectoryExists(dirname($dst));
                File::copy($src, $dst);
            }
        }

        $exclude = [
            'node_modules',
            'tests',
            '.git',
            'storage/logs/*.log',
            'storage/framework/cache/data/*',
            'storage/framework/sessions/*',
            'storage/framework/testing/*',
            'storage/framework/views/*',
            'storage/app/installed.lock',
            'storage/app/*.sqlite',
            'resources/js',
            'resources/css',
            'bootstrap/cache/*.php',
        ];

        foreach ($exclude as $pattern) {
            $files = glob($target . '/' . $pattern);
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) unlink($file);
                }
            }
            // For simple directories that match exactly
            $path = $target . '/' . $pattern;
            if (File::exists($path) && is_dir($path)) {
                File::deleteDirectory($path);
            }
        }

        if ($this->option('zip')) {
            $zipPath = $buildRoot.'/infocus_build.zip';
            if (File::exists($zipPath)) {
                File::delete($zipPath);
            }
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                $this->error('No se pudo crear el ZIP.');
                return Command::FAILURE;
            }
            $this->addFolderToZip($target, $zip, $target);
            $zip->close();
            $this->info('Build generado en: '.$zipPath);
        } else {
            $this->info('Build generado en: '.$target);
        }

        return Command::SUCCESS;
    }

    private function addFolderToZip(string $folder, ZipArchive $zip, string $basePath): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $path = (string) $item;
            $relative = ltrim(str_replace($basePath, '', $path), DIRECTORY_SEPARATOR);
            if ($item->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($path, $relative);
            }
        }
    }
}
