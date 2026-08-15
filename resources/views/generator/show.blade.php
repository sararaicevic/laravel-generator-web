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
            <div
                class="ui-card p-6 sm:p-8"
                x-data="{
                    status: @js($project->status),
                    errorMessage: @js($project->error_message),
                    downloadUrl: @js($project->status === 'succeeded' && $project->zip_path ? route('generator.download', $project) : null),
                    pollUrl: @js(route('generator.status', $project)),
                    timer: null,
                    get isProcessing() {
                        return ['queued', 'running'].includes(this.status);
                    },
                    get statusLabel() {
                        return String(this.status || '').toUpperCase();
                    },
                    get statusClasses() {
                        return {
                            'status-success': this.status === 'succeeded',
                            'status-danger': this.status === 'failed',
                            'status-info': ['queued', 'running'].includes(this.status),
                            'status-muted': !['succeeded', 'failed', 'queued', 'running'].includes(this.status),
                        };
                    },
                    startPolling() {
                        if (!this.isProcessing) {
                            return;
                        }

                        this.refreshStatus();
                        this.timer = setInterval(() => this.refreshStatus(), 2000);
                    },
                    async refreshStatus() {
                        if (!this.isProcessing) {
                            if (this.timer) clearInterval(this.timer);
                            return;
                        }

                        try {
                            const response = await fetch(this.pollUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            if (!response.ok) return;

                            const data = await response.json();
                            this.status = data.status;
                            this.errorMessage = data.error_message;
                            this.downloadUrl = data.download_url;

                            if (!this.isProcessing && this.timer) {
                                clearInterval(this.timer);
                            }
                        } catch (error) {
                            // Keep the current UI state; the next poll can recover.
                        }
                    },
                }"
                x-init="startPolling()"
            >
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-2xl font-bold text-zinc-50">{{ $project->name }}</div>
                        <div class="mt-1 font-mono text-xs text-zinc-500">{{ $project->uuid }}</div>
                    </div>
                    <span class="ui-pill" :class="statusClasses" x-text="statusLabel">
                    </span>
                </div>

                <div x-show="isProcessing" x-cloak class="mt-6 rounded-lg border border-cyan-300/25 bg-cyan-300/10 p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-cyan-300/30 bg-black/20">
                            <svg class="h-6 w-6 animate-spin text-cyan-100" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-base font-semibold text-cyan-50">Generisanje je u toku</div>
                            <p class="mt-1 text-sm text-cyan-100/75">
                                Stranica sama provjerava status. Dugme za preuzimanje će se pojaviti čim ZIP bude spreman.
                            </p>
                        </div>
                    </div>
                </div>

                <div x-show="status === 'failed'" x-cloak class="mt-6">
                    <x-input-error :messages="$errors->get('rerun')" />
                    <div class="mt-3 whitespace-pre-wrap rounded-md border border-red-400/30 bg-red-500/10 p-4 text-sm text-red-200" x-text="errorMessage || 'Generisanje nije uspjelo.'"></div>
                </div>

                <div x-show="status === 'succeeded'" x-cloak class="mt-6 rounded-md border border-emerald-300/30 bg-emerald-300/10 p-4 text-sm text-emerald-200">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <span>
                        Generisanje je završeno. ZIP sadrži Laravel modele, kontrolere, migracije, rute i Blade prikaze.
                        </span>
                        <a class="ui-button-primary" :href="downloadUrl" x-show="downloadUrl">
                            Preuzmi ZIP
                        </a>
                    </div>
                </div>

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
                                        @foreach($entity->relations as $relation)
                                            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-2 last:border-b-0 last:pb-0">
                                                <dt class="font-mono text-cyan-100">{{ $relation->type }}</dt>
                                                <dd class="text-right text-zinc-400">{{ $relation->target }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('generator.rerun', $project) }}" x-show="status === 'failed'" x-cloak>
                        @csrf
                        <x-primary-button>Rerun</x-primary-button>
                    </form>
                    <a class="ui-button-secondary" href="{{ route('generator.edit', $project) }}" x-show="status === 'failed'" x-cloak>
                        Edit
                    </a>
                    <button type="button" class="ui-button-secondary" x-show="isProcessing" x-cloak @click="refreshStatus()">
                        Provjeri status
                    </button>
                    <a class="ui-link" href="{{ route('generator.index') }}">Svi projekti</a>
                    <a class="ui-link" href="{{ route('generator.create') }}">Nova specifikacija</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
