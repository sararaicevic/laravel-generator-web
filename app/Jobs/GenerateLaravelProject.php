<?php

namespace App\Jobs;

use App\Models\GeneratedProject;
use App\Services\Dsl\DslParseException;
use App\Services\Dsl\DslParser;
use App\Services\Generation\LaravelProjectGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ZipArchive;

class GenerateLaravelProject implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $projectId)
    {
    }

    public function handle(DslParser $parser, LaravelProjectGenerator $generator): void
    {
        /** @var GeneratedProject|null $project */
        $project = GeneratedProject::query()->find($this->projectId);
        if (!$project) {
            return;
        }

        $project->forceFill([
            'status' => 'running',
            'error_message' => null,
        ])->save();

        if (!$project->uuid) {
            $project->forceFill([
                'status' => 'failed',
                'error_message' => 'Missing project uuid.',
            ])->save();
            return;
        }

        $workDir = storage_path('app/generator/'.$project->uuid);
        $inputPath = $workDir.'/input/model.mydsl';
        $outDir = $workDir.'/out';
        $zipPath = $workDir.'/project.zip';

        @mkdir($workDir.'/input', 0775, true);

        if (!is_file($inputPath)) {
            $project->forceFill([
                'status' => 'failed',
                'error_message' => 'Missing input DSL file.',
            ])->save();
            return;
        }

        try {
            $specification = $parser->parse((string) file_get_contents($inputPath));
            $generator->generate($specification, $outDir);
        } catch (DslParseException $e) {
            $project->forceFill([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ])->save();
            return;
        } catch (\Throwable $e) {
            $project->forceFill([
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 10_000),
            ])->save();
            return;
        }

        if (!$this->zipDirectory($outDir, $zipPath)) {
            $project->forceFill([
                'status' => 'failed',
                'error_message' => 'Failed to create zip artifact.',
            ])->save();
            return;
        }

        $project->forceFill([
            'status' => 'succeeded',
            'output_path' => $this->toStorageRelative($outDir),
            'zip_path' => $this->toStorageRelative($zipPath),
        ])->save();
    }

    public function failed(\Throwable $e): void
    {
        GeneratedProject::query()
            ->whereKey($this->projectId)
            ->update([
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 10_000),
            ]);
    }

    private function zipDirectory(string $dir, string $zipPath): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        @unlink($zipPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return false;
        }

        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            $filePath = $file->getRealPath();
            if ($filePath === false) {
                continue;
            }

            $rel = ltrim(str_replace($dir, '', $filePath), DIRECTORY_SEPARATOR);
            if ($file->isDir()) {
                $zip->addEmptyDir($rel);
            } else {
                $zip->addFile($filePath, $rel);
            }
        }

        $zip->close();

        return is_file($zipPath);
    }

    private function toStorageRelative(string $path): string
    {
        $root = rtrim(realpath(storage_path('app')) ?: storage_path('app'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $abs = realpath($path) ?: $path;
        if (str_starts_with($abs, $root)) {
            return str_replace($root, '', $abs);
        }
        return $path;
    }
}
