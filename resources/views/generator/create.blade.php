<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <p class="ui-pill">Builder</p>
            <h2 class="text-2xl font-bold leading-tight text-[#1E293B]">
                {{ $project ? 'Edit Project' : 'Create New Application' }}
            </h2>
            <p class="text-sm text-[#64748B]">
                {{ $project ? 'Update your application models and regenerate the project.' : 'Build your Laravel application by defining models, fields, and relationships.' }}
            </p>
        </div>
    </x-slot>

    <div class="py-8" x-data="generatorBuilder({ projectName: @js(old('name', $project?->name ?? '')), entities: @js($initialEntities) })">
        <form method="POST" action="{{ $project ? route('generator.update', $project) : route('generator.store') }}">
            @csrf
            @if($project)
                @method('PUT')
            @endif
            <input type="hidden" name="dsl" :value="dslSource">

            <div class="app-container space-y-5">
                <div class="ui-card p-5">
                    <div class="grid gap-4 lg:grid-cols-[minmax(260px,1fr)_auto] lg:items-end">
                        <div>
                            <label for="name" class="ui-label">Application Name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                class="ui-input mt-2 block w-full text-base"
                                placeholder="e.g. Inventory System"
                                x-model="projectName"
                                required
                            >
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            <x-input-error class="mt-2" :messages="$errors->get('dsl')" />
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="ui-button-secondary" @click="addEntity()">
                                + Add Model
                            </button>

                            <button type="submit" class="ui-button-primary" :disabled="!canSubmit">
                                {{ $project ? 'Save and Regenerate' : 'Generate' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 xl:grid-cols-[280px_minmax(0,1fr)]">
                    <aside class="ui-card overflow-hidden">
                        <div class="border-b border-[#E2E8F0] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-[#1E293B]">Models</h3>
                                    <p class="text-xs text-[#64748B]">Application entities</p>
                                </div>
                                <button type="button" class="ui-button-primary px-3 py-2" @click="addEntity()">
                                    New
                                </button>
                            </div>
                        </div>

                        <div class="p-3">
                            <template x-if="entities.length === 0">
                                <button
                                    type="button"
                                    class="flex min-h-44 w-full flex-col items-center justify-center rounded-md border border-dashed border-[#CBD5E1] px-4 py-8 text-center transition hover:border-[#A5B4FC] hover:bg-[#EEF2FF]"
                                    @click="addEntity()"
                                >
                                    <span class="text-sm font-semibold text-[#1E293B]">Add the first model</span>
                                    <span class="mt-1 text-sm text-[#64748B]">For example: Product, Category, Order</span>
                                </button>
                            </template>

                            <div class="space-y-2" x-show="entities.length > 0">
                                <template x-for="(entity, index) in entities" :key="index">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-3 rounded-md border px-3 py-3 text-left text-sm transition"
                                        :class="selectedEntityIndex === index ? 'border-[#E0E7FF] bg-[#EEF2FF] text-[#6366F1]' : 'border-transparent text-[#64748B] hover:border-[#E2E8F0] hover:bg-[#F8FAFC] hover:text-[#1E293B]'"
                                        @click="selectEntity(index)"
                                    >
                                        <span class="truncate font-semibold" x-text="entityLabel(entity, index)"></span>
                                        <span class="shrink-0 rounded-full border border-[#E2E8F0] bg-white px-2 py-0.5 text-xs text-[#64748B]" x-text="entity.fields.length"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </aside>

                    <section class="ui-card overflow-hidden">
                        <template x-if="!selectedEntity">
                            <div class="flex min-h-[520px] flex-col items-center justify-center p-8 text-center">
                                <div class="max-w-sm">
                                    <h3 class="text-xl font-bold text-[#1E293B]">No model selected</h3>
                                    <p class="mt-2 text-sm text-[#64748B]">Add a model to define its fields and rules.</p>
                                    <button type="button" class="mt-5 ui-button-primary" @click="addEntity()">
                                        Add Model
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-if="selectedEntity">
                            <div class="divide-y divide-[#E2E8F0]">
                                <div class="p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <label class="ui-label">Model Name</label>
                                            <input
                                                type="text"
                                                class="ui-input mt-2 block w-full text-base"
                                                placeholder="e.g. Product"
                                                x-model="selectedEntity.name"
                                                required
                                            >
                                        </div>

                                        <button type="button" class="ui-button-danger" @click="removeEntity(selectedEntityIndex)">
                                            Remove Model
                                        </button>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h3 class="font-semibold text-[#1E293B]">Fields</h3>
                                            <p class="text-xs text-[#64748B]">Field name, type, and validation rules</p>
                                        </div>
                                        <button type="button" class="ui-button-secondary px-3 py-2" @click="addField(selectedEntity)">
                                            + Add Field
                                        </button>
                                    </div>

                                    <template x-if="selectedEntity.fields.length === 0">
                                        <button
                                            type="button"
                                            class="flex min-h-36 w-full flex-col items-center justify-center rounded-md border border-dashed border-[#CBD5E1] px-4 py-8 text-center transition hover:border-[#A5B4FC] hover:bg-[#EEF2FF]"
                                            @click="addField(selectedEntity)"
                                        >
                                            <span class="text-sm font-semibold text-[#1E293B]">Add the first field</span>
                                            <span class="mt-1 text-sm text-[#64748B]">For example: name, title, price, email</span>
                                        </button>
                                    </template>

                                    <div class="overflow-hidden rounded-md border border-[#E2E8F0]" x-show="selectedEntity.fields.length > 0">
                                        <div class="hidden grid-cols-[minmax(160px,1fr)_150px_220px_44px] gap-3 border-b border-[#E2E8F0] bg-[#F8FAFC] px-4 py-3 text-xs font-semibold uppercase text-[#64748B] md:grid">
                                            <div>Field Name</div>
                                            <div>Type</div>
                                            <div>Rules</div>
                                            <div></div>
                                        </div>

                                        <template x-for="(field, fieldIndex) in selectedEntity.fields" :key="fieldIndex">
                                            <div class="grid gap-3 border-b border-[#E2E8F0] p-4 last:border-b-0 md:grid-cols-[minmax(160px,1fr)_150px_220px_44px] md:items-center">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-[#64748B] md:hidden">Field Name</label>
                                                    <input
                                                        type="text"
                                                        class="ui-input block w-full text-sm"
                                                        placeholder="name"
                                                        x-model="field.name"
                                                        required
                                                    >
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-[#64748B] md:hidden">Type</label>
                                                    <select class="ui-select block w-full text-sm" x-model="field.type">
                                                        <template x-for="type in fieldTypes" :key="type">
                                                            <option :value="type" x-text="type"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div class="flex flex-wrap gap-3">
                                                    <label class="inline-flex items-center gap-2 text-sm text-[#1E293B]">
                                                        <input type="checkbox" class="rounded border-[#CBD5E1] text-[#6366F1] shadow-sm focus:ring-[#6366F1]" x-model="field.required" @change="if (field.required) field.nullable = false">
                                                        Required
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 text-sm text-[#1E293B]">
                                                        <input type="checkbox" class="rounded border-[#CBD5E1] text-[#6366F1] shadow-sm focus:ring-[#6366F1]" x-model="field.nullable" @change="if (field.nullable) field.required = false">
                                                        Nullable
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 text-sm text-[#1E293B]">
                                                        <input type="checkbox" class="rounded border-[#CBD5E1] text-[#6366F1] shadow-sm focus:ring-[#6366F1]" x-model="field.unique">
                                                        Unique
                                                    </label>
                                                </div>

                                                <button
                                                    type="button"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-red-200 bg-white text-sm font-semibold text-red-500 transition hover:bg-red-50"
                                                    title="Remove field"
                                                    @click="removeField(selectedEntity, fieldIndex)"
                                                >
                                                    X
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h3 class="font-semibold text-[#1E293B]">Relationships</h3>
                                            <p class="text-xs text-[#64748B]">Connect models with belongsTo, hasOne, hasMany, and belongsToMany relations.</p>
                                        </div>
                                        <button
                                            type="button"
                                            class="ui-button-secondary px-3 py-2"
                                            :disabled="availableRelationTargets.length === 0"
                                            @click="addRelation(selectedEntity)"
                                        >
                                            + Add Relationship
                                        </button>
                                    </div>

                                    <template x-if="availableRelationTargets.length === 0">
                                        <div class="rounded-md border border-dashed border-[#CBD5E1] px-4 py-5 text-sm text-[#64748B]">
                                            Relationships require at least two named models.
                                        </div>
                                    </template>

                                    <div class="space-y-3" x-show="selectedEntity.relations.length > 0">
                                        <template x-for="(relation, relationIndex) in selectedEntity.relations" :key="relationIndex">
                                            <div class="grid gap-3 rounded-md border border-[#E2E8F0] p-4 md:grid-cols-[180px_minmax(180px,1fr)_44px] md:items-center">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-[#64748B]">Relationship Type</label>
                                                    <select class="ui-select block w-full text-sm" x-model="relation.type">
                                                        <template x-for="type in relationTypes" :key="type">
                                                            <option :value="type" x-text="type"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-[#64748B]">Related Model</label>
                                                    <select class="ui-select block w-full text-sm" x-model="relation.target">
                                                        <option value="">Choose model</option>
                                                        <template x-for="target in availableRelationTargets" :key="target.name">
                                                            <option :value="target.name" x-text="target.name"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <button
                                                    type="button"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-red-200 bg-white text-sm font-semibold text-red-500 transition hover:bg-red-50 md:mt-5"
                                                    title="Remove relationship"
                                                    @click="removeRelation(selectedEntity, relationIndex)"
                                                >
                                                    X
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </section>

                    <div class="ui-card p-4 text-sm xl:col-span-2">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <span class="font-semibold text-[#1E293B]">Specification Status</span>
                                <p class="mt-1 text-[#64748B]">When the application name, model, and fields are complete, the project is ready to generate.</p>
                            </div>
                            <span
                                class="ui-pill"
                                :class="canSubmit ? 'status-success' : 'border-amber-200 bg-amber-50 text-amber-700'"
                                x-text="canSubmit ? 'Ready' : 'Incomplete'"
                            ></span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
