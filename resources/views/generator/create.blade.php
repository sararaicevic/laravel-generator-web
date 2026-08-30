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

    @php
        $oldBuilderState = old('builder_state');
        $decodedBuilderState = is_string($oldBuilderState) ? json_decode($oldBuilderState, true) : null;
        $builderProjectName = data_get($decodedBuilderState, 'projectName', old('name', $project?->name ?? ''));
        $builderEntities = data_get($decodedBuilderState, 'entities', $initialEntities);
    @endphp

    <div class="py-8" x-data="generatorBuilder({ projectName: @js($builderProjectName), entities: @js($builderEntities) })" x-init="syncAllRelationships()">
        <form method="POST" action="{{ $project ? route('generator.update', $project) : route('generator.store') }}" @submit="syncAllRelationships()">
            @csrf
            @if($project)
                @method('PUT')
            @endif
            <input type="hidden" name="dsl" :value="dslSource">
            <input type="hidden" name="builder_state" :value="builderStateJson">

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
                                <template x-for="(entity, index) in entities" :key="entity._id || index">
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

                        <template x-for="activeEntity in selectedEntity ? [selectedEntity] : []" :key="activeEntity._id">
                            <div class="divide-y divide-[#E2E8F0]">
                                <div class="p-5">
                                    <div class="grid gap-4 lg:grid-cols-[minmax(220px,1fr)_minmax(220px,1fr)_auto] lg:items-end">
                                        <div class="min-w-0">
                                            <label class="ui-label">Model Name</label>
                                            <input
                                                type="text"
                                                class="ui-input mt-2 block w-full text-base"
                                                placeholder="e.g. Product"
                                                x-model="selectedEntity.name"
                                                @input="syncAllRelationships()"
                                                required
                                            >
                                        </div>

                                        <div class="min-w-0">
                                            <label class="ui-label">Display Field</label>
                                            <select class="ui-select mt-2 block w-full text-base" x-model="selectedEntity.display_field">
                                                <option value="">Auto: name, title, email, then ID</option>
                                                <template x-for="field in selectedEntity.fields" :key="field._id">
                                                    <option :value="cleanFieldName(field.name, '')" x-text="fieldLabel(field, 0)"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <button type="button" class="ui-button-danger" @click="removeEntity(selectedEntityIndex)">
                                            Remove Model
                                        </button>
                                    </div>

                                    <div class="mt-5 rounded-md border border-[#E2E8F0] bg-[#F8FAFC] p-4">
                                        <div class="mb-3">
                                            <h3 class="font-semibold text-[#1E293B]">Generated Screens</h3>
                                            <p class="text-xs text-[#64748B]">Choose which admin pages and actions this model should expose.</p>
                                        </div>

                                        <div class="flex flex-wrap gap-3">
                                            <template x-for="feature in featureOptions" :key="feature.key">
                                                <label class="inline-flex items-center gap-2 rounded-md border border-[#E2E8F0] bg-white px-3 py-2 text-sm font-semibold text-[#1E293B]">
                                                    <input type="checkbox" class="rounded border-[#CBD5E1] text-[#6366F1] shadow-sm focus:ring-[#6366F1]" x-model="selectedEntity.features[feature.key]">
                                                    <span x-text="feature.label"></span>
                                                </label>
                                            </template>
                                        </div>
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

                                        <template x-for="(field, fieldIndex) in selectedEntity.fields" :key="field._id || fieldIndex">
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
                                                    <select class="ui-select block w-full text-sm" x-model="field.type" @change="normalizeFieldRules(field)">
                                                        <template x-for="type in fieldTypes" :key="type">
                                                            <option :value="type" x-text="type"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div class="flex flex-wrap gap-3">
                                                    <label class="inline-flex items-center gap-2 text-sm text-[#1E293B]">
                                                        <input type="checkbox" class="rounded border-[#CBD5E1] text-[#6366F1] shadow-sm focus:ring-[#6366F1]" x-model="field.required" @change="normalizeFieldRules(field)">
                                                        Required
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 text-sm text-[#1E293B]">
                                                        <input type="checkbox" class="rounded border-[#CBD5E1] text-[#6366F1] shadow-sm focus:ring-[#6366F1]" x-model="field.nullable" @change="normalizeFieldRules(field)">
                                                        Nullable
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 text-sm text-[#1E293B]" :class="!canFieldBeUnique(field) ? 'opacity-50' : ''" :title="uniqueUnavailableReason(field)">
                                                        <input type="checkbox" class="rounded border-[#CBD5E1] text-[#6366F1] shadow-sm focus:ring-[#6366F1] disabled:cursor-not-allowed" x-model="field.unique" :disabled="!canFieldBeUnique(field)">
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

                                                <div class="grid gap-3 rounded-md border border-[#E2E8F0] bg-[#F8FAFC] p-3 md:col-span-4 md:grid-cols-4">
                                                    <template x-if="fieldSupportsOptions(field)">
                                                        <div class="md:col-span-4">
                                                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                                                <div>
                                                                    <label class="block text-xs font-medium text-[#64748B]">Enum Values</label>
                                                                    <p class="mt-0.5 text-xs text-[#64748B]">Each value becomes one option in the generated select field.</p>
                                                                </div>
                                                                <button type="button" class="ui-button-secondary px-3 py-2 text-xs" @click="addFieldOption(field)">
                                                                    + Add Value
                                                                </button>
                                                            </div>

                                                            <div class="space-y-2">
                                                                <template x-for="(option, optionIndex) in fieldOptions(field)" :key="optionIndex">
                                                                    <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_44px]">
                                                                        <input
                                                                            type="text"
                                                                            class="ui-input block w-full text-sm"
                                                                            placeholder="draft"
                                                                            :value="option"
                                                                            @input="updateFieldOption(field, optionIndex, $event.target.value)"
                                                                            @keydown.enter.prevent="addFieldOption(field)"
                                                                        >
                                                                        <button
                                                                            type="button"
                                                                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-red-200 bg-white text-sm font-semibold text-red-500 transition hover:bg-red-50"
                                                                            title="Remove enum value"
                                                                            @click="removeFieldOption(field, optionIndex)"
                                                                        >
                                                                            X
                                                                        </button>
                                                                    </div>
                                                                </template>
                                                            </div>

                                                            <p class="mt-2 text-xs font-semibold text-red-600" x-show="enumOptionValues(field).length === 0">
                                                                Add at least one enum value.
                                                            </p>
                                                        </div>
                                                    </template>

                                                    <template x-if="fieldSupportsLengthRules(field)">
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-[#64748B]">Min Length</label>
                                                            <input type="number" min="0" class="ui-input block w-full text-sm" :value="metadataValue(field, 'minLength')" @input="setMetadataValue(field, 'minLength', $event.target.value)">
                                                        </div>
                                                    </template>

                                                    <template x-if="fieldSupportsLengthRules(field)">
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-[#64748B]">Max Length</label>
                                                            <input type="number" min="1" class="ui-input block w-full text-sm" :value="metadataValue(field, 'maxLength')" @input="setMetadataValue(field, 'maxLength', $event.target.value)">
                                                        </div>
                                                    </template>

                                                    <template x-if="fieldSupportsRangeRules(field) || fieldSupportsFileRules(field)">
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-[#64748B]" x-text="fieldSupportsFileRules(field) ? 'Min KB' : 'Min'"></label>
                                                            <input :type="['date', 'datetime', 'timestamp', 'time'].includes(field.type) ? (field.type === 'time' ? 'time' : field.type === 'date' ? 'date' : 'datetime-local') : 'number'" class="ui-input block w-full text-sm" :value="metadataValue(field, 'min')" @input="setMetadataValue(field, 'min', $event.target.value)">
                                                        </div>
                                                    </template>

                                                    <template x-if="fieldSupportsRangeRules(field) || fieldSupportsFileRules(field)">
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-[#64748B]" x-text="fieldSupportsFileRules(field) ? 'Max KB' : 'Max'"></label>
                                                            <input :type="['date', 'datetime', 'timestamp', 'time'].includes(field.type) ? (field.type === 'time' ? 'time' : field.type === 'date' ? 'date' : 'datetime-local') : 'number'" class="ui-input block w-full text-sm" :value="metadataValue(field, 'max')" @input="setMetadataValue(field, 'max', $event.target.value)">
                                                        </div>
                                                    </template>

                                                    <template x-if="fieldSupportsStep(field)">
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-[#64748B]">Step</label>
                                                            <input type="text" class="ui-input block w-full text-sm" placeholder="1, 0.01, any" :value="metadataValue(field, 'step')" @input="setMetadataValue(field, 'step', $event.target.value)">
                                                        </div>
                                                    </template>

                                                    <template x-if="fieldSupportsFileRules(field)">
                                                        <div class="md:col-span-4">
                                                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                                                <div>
                                                                    <label class="block text-xs font-medium text-[#64748B]">Allowed Types</label>
                                                                    <p class="mt-0.5 text-xs text-[#64748B]">Each value becomes one accepted file type.</p>
                                                                </div>
                                                                <button type="button" class="ui-button-secondary px-3 py-2 text-xs" @click="addAcceptType(field)">
                                                                    + Add Type
                                                                </button>
                                                            </div>

                                                            <div class="space-y-2">
                                                                <template x-for="(accept, acceptIndex) in fieldAcceptTypes(field)" :key="acceptIndex">
                                                                    <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_44px]">
                                                                        <input
                                                                            type="text"
                                                                            class="ui-input block w-full text-sm"
                                                                            :placeholder="field.type === 'image' ? 'image/*' : '.pdf'"
                                                                            :value="accept"
                                                                            @input="updateAcceptType(field, acceptIndex, $event.target.value)"
                                                                            @keydown.enter.prevent="addAcceptType(field)"
                                                                        >
                                                                        <button
                                                                            type="button"
                                                                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-red-200 bg-white text-sm font-semibold text-red-500 transition hover:bg-red-50"
                                                                            title="Remove allowed type"
                                                                            @click="removeAcceptType(field, acceptIndex)"
                                                                        >
                                                                            X
                                                                        </button>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-[#64748B]">Placeholder</label>
                                                        <input type="text" class="ui-input block w-full text-sm" :value="metadataValue(field, 'placeholder')" @input="setMetadataValue(field, 'placeholder', $event.target.value)">
                                                    </div>

                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-[#64748B]">Default</label>
                                                        <template x-if="fieldSupportsOptions(field)">
                                                            <select class="ui-select block w-full text-sm" :value="metadataValue(field, 'default')" @change="setMetadataValue(field, 'default', $event.target.value)">
                                                                <option value="">No default</option>
                                                                <template x-for="option in enumOptionValues(field)" :key="option">
                                                                    <option :value="option" x-text="option"></option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="!fieldSupportsOptions(field)">
                                                            <input type="text" class="ui-input block w-full text-sm" :value="metadataValue(field, 'default')" @input="setMetadataValue(field, 'default', $event.target.value)">
                                                        </template>
                                                    </div>

                                                    <div class="md:col-span-2">
                                                        <label class="mb-1 block text-xs font-medium text-[#64748B]">Help Text</label>
                                                        <input type="text" class="ui-input block w-full text-sm" :value="metadataValue(field, 'help')" @input="setMetadataValue(field, 'help', $event.target.value)">
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h3 class="font-semibold text-[#1E293B]">Relationships</h3>
                                            <p class="text-xs text-[#64748B]">Direct relations automatically add and remove their inverse relation on the related model.</p>
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

                                    <div class="space-y-3" x-show="relationshipRows(selectedEntity, selectedEntityIndex).length > 0">
                                        <template x-for="(relation, relationIndex) in relationshipRows(selectedEntity, selectedEntityIndex)" :key="relation._id || relationIndex">
                                            <div class="grid gap-3 rounded-md border border-[#E2E8F0] p-4 md:grid-cols-[180px_minmax(180px,1fr)_minmax(180px,1fr)_44px] md:items-center">
                                                <template x-if="relation._managedInverse">
                                                    <div class="md:col-span-4">
                                                        <div class="grid gap-3 rounded-md bg-[#F8FAFC] px-4 py-3 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-center">
                                                            <div>
                                                                <div class="text-sm font-semibold text-[#1E293B]">
                                                                    <span x-text="relation.type"></span>
                                                                    <span class="text-[#64748B]">-></span>
                                                                    <span x-text="relation.target"></span>
                                                                </div>
                                                                <p class="mt-1 text-xs font-semibold text-[#64748B]" x-show="relation.type === 'belongsToMany'">
                                                                    Pivot: <span x-text="relation.pivot_table"></span>
                                                                </p>
                                                                <p class="mt-1 text-xs text-[#64748B]">Auto-added inverse relationship</p>
                                                            </div>
                                                            <span class="rounded-full bg-[#EEF2FF] px-2 py-1 text-xs font-semibold text-[#6366F1]">Auto</span>
                                                            <button
                                                                type="button"
                                                                class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-red-200 bg-white text-sm font-semibold text-red-500 transition hover:bg-red-50"
                                                                title="Remove relationship pair"
                                                                @click="removeRelation(selectedEntity, relationIndex, relation)"
                                                            >
                                                                X
                                                            </button>
                                                        </div>
                                                    </div>
                                                </template>

                                                <template x-if="!relation._managedInverse">
                                                    <div>
                                                    <div class="mb-1 flex items-center justify-between gap-2">
                                                        <label class="block text-xs font-medium text-[#64748B]">Relationship Type</label>
                                                        <span
                                                            class="rounded-full bg-[#EEF2FF] px-2 py-0.5 text-[11px] font-semibold text-[#6366F1]"
                                                            x-show="relation._managedInverse"
                                                            x-text="'Auto'"
                                                        ></span>
                                                    </div>
                                                    <select
                                                        class="ui-select block w-full text-sm"
                                                        x-model="relation.type"
                                                        @change="updateRelationshipType(selectedEntity, selectedEntityIndex, relation, $event.target.value)"
                                                    >
                                                        <template x-for="type in relationTypes" :key="type">
                                                            <option :value="type" x-text="type"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                                </template>

                                                <template x-if="!relation._managedInverse">
                                                    <div>
                                                    <label class="mb-1 block text-xs font-medium text-[#64748B]">Related Model</label>
                                                    <select
                                                        class="ui-select block w-full text-sm"
                                                        :value="relation.target || ''"
                                                        x-effect="$el.value = relation.target || ''"
                                                        @change="updateRelationshipTarget(selectedEntity, selectedEntityIndex, relation, $event.target.value)"
                                                    >
                                                        <option value="" :selected="!relation.target">Choose model</option>
                                                        <template x-for="target in relationTargetOptions(relation)" :key="target.name">
                                                            <option :value="target.name" :selected="target.name === relation.target" x-text="target.name"></option>
                                                        </template>
                                                    </select>
                                                    <p class="mt-1 text-xs text-[#64748B]" x-text="relationshipDescription(relation)"></p>
                                                </div>
                                                </template>

                                                <template x-if="!relation._managedInverse && relation.type === 'belongsToMany'">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-[#64748B]">Pivot Table</label>
                                                        <input
                                                            type="text"
                                                            class="ui-input block w-full text-sm"
                                                            x-model="relation.pivot_table"
                                                            placeholder="product_tag"
                                                            @input="updatePivotTable(relation, $event.target.value)"
                                                        >
                                                        <p class="mt-1 text-xs text-[#64748B]">Laravel default can be changed.</p>
                                                    </div>
                                                </template>

                                                <template x-if="!relation._managedInverse">
                                                    <button
                                                        type="button"
                                                        class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-red-200 bg-white text-sm font-semibold text-red-500 transition hover:bg-red-50 md:mt-5"
                                                        title="Remove relationship"
                                                        @click="removeRelation(selectedEntity, relationIndex, relation)"
                                                    >
                                                        X
                                                    </button>
                                                </template>
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
