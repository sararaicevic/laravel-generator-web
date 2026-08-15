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
    public function index()
    {
        $projects = GeneratedProject::query()
            ->where('user_id', Auth::id())
            ->withCount('entities')
            ->latest()
            ->paginate(12);

        return view('generator.index', ['projects' => $projects]);
    }

    public function create()
    {
        return view('generator.create', [
            'project' => null,
            'initialEntities' => [],
        ]);
    }

    public function store(StoreGeneratedProjectRequest $request, DslParser $parser)
    {
        $result = $this->validatedSpecification($request, $parser);
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        [$dsl, $specification] = $result;

        $project = DB::transaction(function () use ($request, $dsl, $specification): GeneratedProject {
            $project = GeneratedProject::query()->create([
                'user_id' => Auth::id(),
                'uuid' => (string) Str::uuid(),
                'name' => (string) $request->validated('name'),
                'status' => 'queued',
            ]);

            $this->persistSpecification($project, $dsl, $specification);

            return $project;
        });

        GenerateLaravelProject::dispatch($project->id);

        return redirect()->route('generator.show', $project);
    }

    public function edit(GeneratedProject $project)
    {
        $this->authorizeOwner($project);
        $project->load('entities.fields');

        return view('generator.create', [
            'project' => $project,
            'initialEntities' => $this->initialEntities($project),
        ]);
    }

    public function update(StoreGeneratedProjectRequest $request, DslParser $parser, GeneratedProject $project)
    {
        $this->authorizeOwner($project);

        $result = $this->validatedSpecification($request, $parser);
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        [$dsl, $specification] = $result;

        DB::transaction(function () use ($request, $project, $dsl, $specification): void {
            $project->forceFill([
                'name' => (string) $request->validated('name'),
                'status' => 'queued',
                'error_message' => null,
                'output_path' => null,
                'zip_path' => null,
            ])->save();

            $project->entities()->delete();
            $this->persistSpecification($project, $dsl, $specification);
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

    public function rerun(GeneratedProject $project)
    {
        $this->authorizeOwner($project);

        if (!$project->dsl_path || !is_file(storage_path('app/'.$project->dsl_path))) {
            return back()->withErrors([
                'rerun' => 'Nije moguće ponovo pokrenuti generisanje jer DSL fajl nedostaje.',
            ]);
        }

        $project->forceFill([
            'status' => 'queued',
            'error_message' => null,
            'output_path' => null,
            'zip_path' => null,
        ])->save();

        GenerateLaravelProject::dispatch($project->id);

        return redirect()->route('generator.show', $project);
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

    private function validatedSpecification(StoreGeneratedProjectRequest $request, DslParser $parser): array|\Illuminate\Http\RedirectResponse
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

        return [$dsl, $specification];
    }

    private function persistSpecification(GeneratedProject $project, string $dsl, array $specification): void
    {
        $workDir = storage_path('app/generator/'.$project->uuid);
        @mkdir($workDir.'/input', 0775, true);

        $dslPath = $workDir.'/input/model.mydsl';
        file_put_contents($dslPath, $dsl);

        $project->forceFill([
            'dsl_path' => $this->toStorageRelative($dslPath),
        ])->save();

        $this->writeProjectLog($project, $dsl, $specification);

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
    }

    private function writeProjectLog(GeneratedProject $project, string $dsl, array $specification): void
    {
        $appDirectory = Str::slug($project->name) ?: 'unnamed-app';
        $logDir = storage_path('app/projectLogs/'.$appDirectory);
        @mkdir($logDir, 0775, true);

        $timestamp = now()->format('Y-m-d_H-i-s_u');
        $logPath = $logDir.'/'.$timestamp.'_'.$project->uuid.'.log';

        $payload = [
            'logged_at' => now()->toIso8601String(),
            'project' => [
                'id' => $project->id,
                'uuid' => $project->uuid,
                'name' => $project->name,
                'user_id' => $project->user_id,
            ],
            'input' => [
                'name' => $project->name,
                'entities' => $specification['entities'],
            ],
            'dsl' => $dsl,
        ];

        file_put_contents(
            $logPath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    private function initialEntities(GeneratedProject $project): array
    {
        return $project->entities
            ->map(fn (GeneratedEntity $entity): array => [
                'name' => $entity->name,
                'fields' => $entity->fields
                    ->map(fn ($field): array => [
                        'name' => $field->name,
                        'type' => $field->type,
                        'required' => (bool) $field->is_required,
                        'nullable' => !(bool) $field->is_required,
                        'unique' => (bool) $field->is_unique,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
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
