<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="ui-pill">Projects</p>
                <h2 class="mt-3 text-2xl font-bold leading-tight text-zinc-50">
                    Generisani projekti
                </h2>
                <p class="mt-1 text-sm text-zinc-400">
                    Pregled, izmjena i preuzimanje Laravel projekata nastalih iz DSL specifikacija.
                </p>
            </div>

            <a href="{{ route('generator.create') }}" class="ui-button-primary">
                Novi projekat
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-container">
            @if($projects->isEmpty())
                <div class="ui-card p-10 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg border border-white/10 bg-white/[0.06] text-2xl font-bold text-emerald-200">
                        +
                    </div>
                    <h3 class="mt-5 text-xl font-bold text-zinc-50">Nema generisanih projekata</h3>
                    <p class="mt-2 text-sm text-zinc-400">Kreiraj prvu DSL specifikaciju kroz vizuelni builder.</p>
                    <a href="{{ route('generator.create') }}" class="mt-6 ui-button-primary">
                        Kreiraj projekat
                    </a>
                </div>
            @else
                <div class="ui-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10">
                            <thead class="bg-white/[0.04]">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-zinc-500">Projekat</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-zinc-500">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-zinc-500">Modeli</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-zinc-500">Kreirano</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase text-zinc-500">Akcije</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                @foreach($projects as $project)
                                    <tr class="transition hover:bg-white/[0.035]">
                                        <td class="px-6 py-5">
                                            <div class="font-semibold text-zinc-50">{{ $project->name }}</div>
                                            <div class="mt-1 font-mono text-xs text-zinc-500">{{ $project->uuid }}</div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <span @class([
                                                'ui-pill',
                                                'status-success' => $project->status === 'succeeded',
                                                'status-danger' => $project->status === 'failed',
                                                'status-info' => in_array($project->status, ['queued', 'running'], true),
                                                'status-muted' => !in_array($project->status, ['succeeded', 'failed', 'queued', 'running'], true),
                                            ])>
                                                {{ strtoupper($project->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-zinc-300">
                                            {{ $project->entities_count }}
                                        </td>
                                        <td class="px-6 py-5 text-sm text-zinc-500">
                                            {{ $project->created_at?->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <a class="ui-button-secondary px-3 py-2" href="{{ route('generator.show', $project) }}">
                                                    Detalji
                                                </a>
                                                <a class="ui-button-secondary px-3 py-2" href="{{ route('generator.edit', $project) }}">
                                                    Edit
                                                </a>
                                                @if($project->status === 'failed')
                                                    <form method="POST" action="{{ route('generator.rerun', $project) }}">
                                                        @csrf
                                                        <button type="submit" class="ui-button-primary px-3 py-2">
                                                            Rerun
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($project->status === 'succeeded')
                                                    <a class="ui-button-primary px-3 py-2" href="{{ route('generator.download', $project) }}">
                                                        ZIP
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-5 text-zinc-300">
                    {{ $projects->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
