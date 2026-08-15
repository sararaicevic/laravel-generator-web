<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Generisani projekti
                </h2>
                <p class="text-sm text-gray-500">
                    Pregled, izmjena i preuzimanje Laravel projekata nastalih iz DSL specifikacija.
                </p>
            </div>

            <a
                href="{{ route('generator.create') }}"
                class="mt-3 inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 sm:mt-0"
            >
                Novi projekat
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if($projects->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Nema generisanih projekata</h3>
                    <p class="mt-2 text-sm text-gray-500">Kreiraj prvu DSL specifikaciju kroz vizuelni builder.</p>
                    <a
                        href="{{ route('generator.create') }}"
                        class="mt-5 inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800"
                    >
                        Kreiraj projekat
                    </a>
                </div>
            @else
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Projekat</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Modeli</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Kreirano</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Akcije</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($projects as $project)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900">{{ $project->name }}</div>
                                            <div class="mt-1 font-mono text-xs text-gray-500">{{ $project->uuid }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span @class([
                                                'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1',
                                                'bg-green-50 text-green-700 ring-green-200' => $project->status === 'succeeded',
                                                'bg-red-50 text-red-700 ring-red-200' => $project->status === 'failed',
                                                'bg-blue-50 text-blue-700 ring-blue-200' => in_array($project->status, ['queued', 'running'], true),
                                                'bg-gray-50 text-gray-700 ring-gray-200' => !in_array($project->status, ['succeeded', 'failed', 'queued', 'running'], true),
                                            ])>
                                                {{ strtoupper($project->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            {{ $project->entities_count }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $project->created_at?->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <a class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" href="{{ route('generator.show', $project) }}">
                                                    Detalji
                                                </a>
                                                <a class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" href="{{ route('generator.edit', $project) }}">
                                                    Edit
                                                </a>
                                                @if($project->status === 'failed')
                                                    <form method="POST" action="{{ route('generator.rerun', $project) }}">
                                                        @csrf
                                                        <button type="submit" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800">
                                                            Rerun
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($project->status === 'succeeded')
                                                    <a class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800" href="{{ route('generator.download', $project) }}">
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

                <div class="mt-5">
                    {{ $projects->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
