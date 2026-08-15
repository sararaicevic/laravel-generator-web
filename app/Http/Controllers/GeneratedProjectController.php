<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGeneratedProjectRequest;
use App\Jobs\GenerateLaravelProject;
use App\Models\GeneratedEntity;
use App\Models\GeneratedProject;
use App\Services\Dsl\DslParseException;
use App\Services\Dsl\DslParser;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeneratedProjectController extends Controller
{
    public function create()
    {
        return view('generator.create');
    }

    public function store(StoreGeneratedProjectRequest $request, DslParser $parser)
    {
        $dsl = (string) $request->validated('dsl');
        $maxBytes = (int) config('generator.max_dsl_bytes');
        if ($maxBytes > 0 && strlen($dsl) > $maxBytes) {
            return back()
                ->withInput()
                ->withErrors(['dsl' => 'DSL is too large (max '.$maxBytes.' bytes).']);
        }

        try {
            $specification = $parser->parse($dsl);
        } catch (DslParseException $e) {
            return back()
                ->withInput()
                ->withErrors(['dsl' => $e->getMessage()]);
        }

        $project = DB::transaction(function () use ($request, $dsl, $specification) {
            $project = GeneratedProject::query()->create([
                'user_id' => Auth::id(),
                'uuid' => (string) Str::uuid(),
                'name' => (string) $request->validated('name'),
                'status' => 'queued',
            ]);

            $workDir = storage_path('app/generator/'.$project->uuid);
            @mkdir($workDir.'/input', 0775, true);

            $dslPath = $workDir.'/input/model.mydsl';
            file_put_contents($dslPath, $dsl);

            $project->forceFill([
                'dsl_path' => $this->toStorageRelative($dslPath),
            ])->save();

            foreach ($specification['entities'] as $entitySpec) {
                /** @var GeneratedEntity $entity */
                $entity = $project->entities()->create([
                    'name' => $entitySpec['name'],
                ]);

                foreach ($entitySpec['fields'] as $fieldSpec) {
                    $entity->fields()->create([
                        'name' => $fieldSpec['name'],
                        'type' => $fieldSpec['type'],
                        'is_required' => $fieldSpec['required'],
                        'is_unique' => $fieldSpec['unique'],
                    ]);
                }
            }

            return $project;
        });

        GenerateLaravelProject::dispatch($project->id);

        return redirect()->route('generator.show', $project);
    }

    public function show(GeneratedProject $project)
    {
        $this->authorizeOwner($project);
        $project->load('entities.fields');

        return view('generator.show', ['project' => $project]);
    }

    public function download(GeneratedProject $project)
    {
        $this->authorizeOwner($project);

        if ($project->status !== 'succeeded' || !$project->zip_path) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $zipAbs = storage_path('app/'.$project->zip_path);
        if (!is_file($zipAbs)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $fileName = Str::slug($project->name).'.zip';

        return response()->download($zipAbs, $fileName);
    }

    private function authorizeOwner(GeneratedProject $project): void
    {
        $uid = Auth::id();
        if (!$uid || (int) $project->user_id !== (int) $uid) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }

    private function toStorageRelative(string $path): string
    {
        $root = rtrim(storage_path('app'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $abs = realpath($path) ?: $path;
        if (str_starts_with($abs, $root)) {
            return str_replace($root, '', $abs);
        }
        return $path;
    }
}
