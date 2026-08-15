<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Status generisanja
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <div class="flex items-start justify-between gap-6">
                        <div>
                            <div class="text-lg font-medium">{{ $project->name }}</div>
                            <div class="text-sm text-gray-600 font-mono">{{ $project->uuid }}</div>
                        </div>
                        <div class="text-sm">
                            <span class="inline-flex items-center rounded-full px-3 py-1 bg-gray-100 text-gray-800">
                                {{ strtoupper($project->status) }}
                            </span>
                        </div>
                    </div>

                    @if($project->status === 'failed')
                        <x-input-error class="mt-2" :messages="$errors->get('rerun')" />
                        <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 whitespace-pre-wrap">{{ $project->error_message }}</div>
                    @endif

                    @if($project->status === 'succeeded')
                        <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                            Generisanje je završeno. ZIP sadrži Laravel modele, kontrolere, migracije, rute i Blade prikaze.
                        </div>
                    @endif

                    @if($project->entities->isNotEmpty())
                        <div class="border-t border-gray-200 pt-4">
                            <div class="font-medium text-gray-900">Parsirana DSL struktura</div>
                            <div class="mt-3 grid gap-4 md:grid-cols-2">
                                @foreach($project->entities as $entity)
                                    <div class="rounded-md border border-gray-200 p-4">
                                        <div class="font-semibold">{{ $entity->name }}</div>
                                        <dl class="mt-3 space-y-2 text-sm">
                                            @foreach($entity->fields as $field)
                                                <div class="flex items-center justify-between gap-4">
                                                    <dt class="font-mono text-gray-800">{{ $field->name }}</dt>
                                                    <dd class="text-gray-600">
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

                    <div class="flex items-center gap-3">
                        @if($project->status === 'succeeded')
                            <a class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white" href="{{ route('generator.download', $project) }}">
                                Preuzmi ZIP
                            </a>
                        @elseif($project->status === 'failed')
                            <form method="POST" action="{{ route('generator.rerun', $project) }}">
                                @csrf
                                <x-primary-button>Rerun</x-primary-button>
                            </form>
                            <a class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" href="{{ route('generator.edit', $project) }}">
                                Edit
                            </a>
                        @else
                            <form method="GET" action="{{ route('generator.show', $project) }}">
                                <x-secondary-button>Osvježi</x-secondary-button>
                            </form>
                        @endif
                        <a class="text-sm text-gray-600 underline" href="{{ route('generator.index') }}">Svi projekti</a>
                        <a class="text-sm text-gray-600 underline" href="{{ route('generator.create') }}">Nova specifikacija</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
