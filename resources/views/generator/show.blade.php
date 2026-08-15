<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="ui-pill">Generation status</p>
            <h2 class="mt-3 text-2xl font-bold leading-tight text-zinc-50">
                Status generisanja
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="ui-card p-6 sm:p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-2xl font-bold text-zinc-50">{{ $project->name }}</div>
                        <div class="mt-1 font-mono text-xs text-zinc-500">{{ $project->uuid }}</div>
                    </div>
                    <span @class([
                        'ui-pill',
                        'status-success' => $project->status === 'succeeded',
                        'status-danger' => $project->status === 'failed',
                        'status-info' => in_array($project->status, ['queued', 'running'], true),
                        'status-muted' => !in_array($project->status, ['succeeded', 'failed', 'queued', 'running'], true),
                    ])>
                        {{ strtoupper($project->status) }}
                    </span>
                </div>

                @if($project->status === 'failed')
                    <div class="mt-6">
                        <x-input-error :messages="$errors->get('rerun')" />
                        <div class="mt-3 whitespace-pre-wrap rounded-md border border-red-400/30 bg-red-500/10 p-4 text-sm text-red-200">{{ $project->error_message }}</div>
                    </div>
                @endif

                @if($project->status === 'succeeded')
                    <div class="mt-6 rounded-md border border-emerald-300/30 bg-emerald-300/10 p-4 text-sm text-emerald-200">
                        Generisanje je završeno. ZIP sadrži Laravel modele, kontrolere, migracije, rute i Blade prikaze.
                    </div>
                @endif

                @if($project->entities->isNotEmpty())
                    <div class="mt-8 border-t border-white/10 pt-6">
                        <div class="font-semibold text-zinc-50">Parsirana DSL struktura</div>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            @foreach($project->entities as $entity)
                                <div class="ui-card-soft p-4">
                                    <div class="font-semibold text-zinc-50">{{ $entity->name }}</div>
                                    <dl class="mt-4 space-y-3 text-sm">
                                        @foreach($entity->fields as $field)
                                            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-2 last:border-b-0 last:pb-0">
                                                <dt class="font-mono text-emerald-100">{{ $field->name }}</dt>
                                                <dd class="text-right text-zinc-400">
                                                    {{ $field->type }}
                                                    @if($field->is_required) required @endif
                                                    @if($field->is_unique) unique @endif
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    @if($project->status === 'succeeded')
                        <a class="ui-button-primary" href="{{ route('generator.download', $project) }}">
                            Preuzmi ZIP
                        </a>
                    @elseif($project->status === 'failed')
                        <form method="POST" action="{{ route('generator.rerun', $project) }}">
                            @csrf
                            <x-primary-button>Rerun</x-primary-button>
                        </form>
                        <a class="ui-button-secondary" href="{{ route('generator.edit', $project) }}">
                            Edit
                        </a>
                    @else
                        <form method="GET" action="{{ route('generator.show', $project) }}">
                            <x-secondary-button>Osvježi</x-secondary-button>
                        </form>
                    @endif
                    <a class="ui-link" href="{{ route('generator.index') }}">Svi projekti</a>
                    <a class="ui-link" href="{{ route('generator.create') }}">Nova specifikacija</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
