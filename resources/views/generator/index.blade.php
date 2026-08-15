<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="ui-pill">Projects</p>
                <h2 class="mt-3 text-2xl font-bold leading-tight text-[#1E293B]">
                    My Projects
                </h2>
                <p class="mt-1 text-sm text-[#64748B]">
                    View, edit, regenerate, and download Laravel applications created from DSL specifications.
                </p>
            </div>

            <a href="{{ route('generator.create') }}" class="ui-button-primary">
                + New Project
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-container">
            @if($projects->isEmpty())
                <div class="ui-card p-10 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg border border-[#E0E7FF] bg-[#EEF2FF] text-2xl font-bold text-[#6366F1]">
                        +
                    </div>
                    <h3 class="mt-5 text-xl font-bold text-[#1E293B]">No projects yet</h3>
                    <p class="mt-2 text-sm text-[#64748B]">Create your first DSL specification with the visual builder.</p>
                    <a href="{{ route('generator.create') }}" class="mt-6 ui-button-primary">
                        Create Project
                    </a>
                </div>
            @else
                <div class="ui-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#E2E8F0]">
                            <thead class="bg-[#F8FAFC]">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-[#64748B]">Project Name</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-[#64748B]">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-[#64748B]">Models</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-[#64748B]">Created</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase text-[#64748B]">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E2E8F0] bg-white">
                                @foreach($projects as $project)
                                    <tr class="transition hover:bg-[#F8FAFC]">
                                        <td class="px-6 py-5">
                                            <div class="font-semibold text-[#1E293B]">{{ $project->name }}</div>
                                            <div class="mt-1 font-mono text-xs text-[#64748B]">{{ $project->uuid }}</div>
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
                                        <td class="px-6 py-5 text-sm text-[#1E293B]">
                                            {{ $project->entities_count }} {{ Str::plural('model', $project->entities_count) }}
                                        </td>
                                        <td class="px-6 py-5 text-sm text-[#64748B]">
                                            {{ $project->created_at?->format('M d, Y H:i') }}
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <a class="ui-button-secondary px-3 py-2" href="{{ route('generator.show', $project) }}">
                                                    Details
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
                                                        Download
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

                <div class="mt-5 text-[#64748B]">
                    {{ $projects->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
