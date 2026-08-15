<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <p class="ui-pill">Builder</p>
            <h2 class="text-2xl font-bold leading-tight text-zinc-50">
                Laravel Generator
            </h2>
            <p class="text-sm text-zinc-400">
                {{ $project ? 'Izmijeni postojeću specifikaciju i ponovo generiši projekat.' : 'Vizuelno definiši aplikaciju kroz modele, polja i pravila.' }}
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
                            <label for="name" class="ui-label">Naziv aplikacije</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                class="ui-input mt-2 block w-full text-base"
                                placeholder="npr. Inventory System"
                                x-model="projectName"
                                required
                            >
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            <x-input-error class="mt-2" :messages="$errors->get('dsl')" />
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="ui-button-secondary" @click="addEntity()">
                                Dodaj model
                            </button>

                            <button type="submit" class="ui-button-primary" :disabled="!canSubmit">
                                {{ $project ? 'Sačuvaj i generiši' : 'Generiši projekat' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 xl:grid-cols-[280px_minmax(0,1fr)]">
                    <aside class="ui-card overflow-hidden">
                        <div class="border-b border-white/10 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-zinc-50">Modeli</h3>
                                    <p class="text-xs text-zinc-500">Entiteti aplikacije</p>
                                </div>
                                <button type="button" class="ui-button-primary px-3 py-2" @click="addEntity()">
                                    Novi
                                </button>
                            </div>
                        </div>

                        <div class="p-3">
                            <template x-if="entities.length === 0">
                                <button
                                    type="button"
                                    class="flex min-h-44 w-full flex-col items-center justify-center rounded-md border border-dashed border-white/15 px-4 py-8 text-center transition hover:border-emerald-300/40 hover:bg-emerald-300/5"
                                    @click="addEntity()"
                                >
                                    <span class="text-sm font-semibold text-zinc-50">Dodaj prvi model</span>
                                    <span class="mt-1 text-sm text-zinc-500">Npr. Product, Category, Order</span>
                                </button>
                            </template>

                            <div class="space-y-2" x-show="entities.length > 0">
                                <template x-for="(entity, index) in entities" :key="index">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-3 rounded-md border px-3 py-3 text-left text-sm transition"
                                        :class="selectedEntityIndex === index ? 'border-emerald-300/30 bg-emerald-300/10 text-emerald-100' : 'border-transparent text-zinc-400 hover:border-white/10 hover:bg-white/[0.05] hover:text-zinc-100'"
                                        @click="selectEntity(index)"
                                    >
                                        <span class="truncate font-semibold" x-text="entityLabel(entity, index)"></span>
                                        <span class="shrink-0 rounded-full border border-white/10 bg-black/30 px-2 py-0.5 text-xs text-zinc-400" x-text="entity.fields.length"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </aside>

                    <section class="ui-card overflow-hidden">
                        <template x-if="!selectedEntity">
                            <div class="flex min-h-[520px] flex-col items-center justify-center p-8 text-center">
                                <div class="max-w-sm">
                                    <h3 class="text-xl font-bold text-zinc-50">Nema izabranog modela</h3>
                                    <p class="mt-2 text-sm text-zinc-400">Dodaj model da bi definisala njegova polja i pravila.</p>
                                    <button type="button" class="mt-5 ui-button-primary" @click="addEntity()">
                                        Dodaj model
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-if="selectedEntity">
                            <div class="divide-y divide-white/10">
                                <div class="p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <label class="ui-label">Naziv modela</label>
                                            <input
                                                type="text"
                                                class="ui-input mt-2 block w-full text-base"
                                                placeholder="npr. Product"
                                                x-model="selectedEntity.name"
                                                required
                                            >
                                        </div>

                                        <button type="button" class="ui-button-danger" @click="removeEntity(selectedEntityIndex)">
                                            Ukloni model
                                        </button>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h3 class="font-semibold text-zinc-50">Polja modela</h3>
                                            <p class="text-xs text-zinc-500">Naziv, tip i validaciona pravila</p>
                                        </div>
                                        <button type="button" class="ui-button-secondary px-3 py-2" @click="addField(selectedEntity)">
                                            Dodaj polje
                                        </button>
                                    </div>

                                    <template x-if="selectedEntity.fields.length === 0">
                                        <button
                                            type="button"
                                            class="flex min-h-36 w-full flex-col items-center justify-center rounded-md border border-dashed border-white/15 px-4 py-8 text-center transition hover:border-emerald-300/40 hover:bg-emerald-300/5"
                                            @click="addField(selectedEntity)"
                                        >
                                            <span class="text-sm font-semibold text-zinc-50">Dodaj prvo polje</span>
                                            <span class="mt-1 text-sm text-zinc-500">Npr. name, title, price, email</span>
                                        </button>
                                    </template>

                                    <div class="overflow-hidden rounded-md border border-white/10" x-show="selectedEntity.fields.length > 0">
                                        <div class="hidden grid-cols-[minmax(160px,1fr)_150px_220px_44px] gap-3 border-b border-white/10 bg-white/[0.04] px-4 py-3 text-xs font-semibold uppercase text-zinc-500 md:grid">
                                            <div>Naziv</div>
                                            <div>Tip</div>
                                            <div>Pravila</div>
                                            <div></div>
                                        </div>

                                        <template x-for="(field, fieldIndex) in selectedEntity.fields" :key="fieldIndex">
                                            <div class="grid gap-3 border-b border-white/10 p-4 last:border-b-0 md:grid-cols-[minmax(160px,1fr)_150px_220px_44px] md:items-center">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-zinc-500 md:hidden">Naziv</label>
                                                    <input
                                                        type="text"
                                                        class="ui-input block w-full text-sm"
                                                        placeholder="name"
                                                        x-model="field.name"
                                                        required
                                                    >
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-zinc-500 md:hidden">Tip</label>
                                                    <select class="ui-select block w-full text-sm" x-model="field.type">
                                                        <template x-for="type in fieldTypes" :key="type">
                                                            <option :value="type" x-text="type"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div class="flex flex-wrap gap-3">
                                                    <label class="inline-flex items-center gap-2 text-sm text-zinc-300">
                                                        <input type="checkbox" class="rounded border-white/10 bg-zinc-950/70 text-emerald-300 shadow-sm focus:ring-emerald-300" x-model="field.required" @change="if (field.required) field.nullable = false">
                                                        Required
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 text-sm text-zinc-300">
                                                        <input type="checkbox" class="rounded border-white/10 bg-zinc-950/70 text-emerald-300 shadow-sm focus:ring-emerald-300" x-model="field.nullable" @change="if (field.nullable) field.required = false">
                                                        Nullable
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 text-sm text-zinc-300">
                                                        <input type="checkbox" class="rounded border-white/10 bg-zinc-950/70 text-emerald-300 shadow-sm focus:ring-emerald-300" x-model="field.unique">
                                                        Unique
                                                    </label>
                                                </div>

                                                <button
                                                    type="button"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-red-400/30 bg-red-500/10 text-sm font-semibold text-red-200 transition hover:bg-red-500/20"
                                                    title="Ukloni polje"
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
                                            <h3 class="font-semibold text-zinc-50">Relacije</h3>
                                            <p class="text-xs text-zinc-500">Poveži modele kroz belongsTo i hasMany odnose.</p>
                                        </div>
                                        <button
                                            type="button"
                                            class="ui-button-secondary px-3 py-2"
                                            :disabled="availableRelationTargets.length === 0"
                                            @click="addRelation(selectedEntity)"
                                        >
                                            Dodaj relaciju
                                        </button>
                                    </div>

                                    <template x-if="availableRelationTargets.length === 0">
                                        <div class="rounded-md border border-dashed border-white/15 px-4 py-5 text-sm text-zinc-500">
                                            Za relaciju su potrebna najmanje dva imenovana modela.
                                        </div>
                                    </template>

                                    <div class="space-y-3" x-show="selectedEntity.relations.length > 0">
                                        <template x-for="(relation, relationIndex) in selectedEntity.relations" :key="relationIndex">
                                            <div class="grid gap-3 rounded-md border border-white/10 p-4 md:grid-cols-[180px_minmax(180px,1fr)_44px] md:items-center">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-zinc-500">Tip relacije</label>
                                                    <select class="ui-select block w-full text-sm" x-model="relation.type">
                                                        <template x-for="type in relationTypes" :key="type">
                                                            <option :value="type" x-text="type"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-zinc-500">Ciljni model</label>
                                                    <select class="ui-select block w-full text-sm" x-model="relation.target">
                                                        <option value="">Izaberi model</option>
                                                        <template x-for="target in availableRelationTargets" :key="target.name">
                                                            <option :value="target.name" x-text="target.name"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <button
                                                    type="button"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-red-400/30 bg-red-500/10 text-sm font-semibold text-red-200 transition hover:bg-red-500/20 md:mt-5"
                                                    title="Ukloni relaciju"
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
                                <span class="font-semibold text-zinc-50">Status specifikacije</span>
                                <p class="mt-1 text-zinc-400">Kada su naziv aplikacije, model i polja popunjeni, projekat je spreman za generisanje.</p>
                            </div>
                            <span
                                class="ui-pill"
                                :class="canSubmit ? 'status-success' : 'border-amber-300/30 bg-amber-300/10 text-amber-100'"
                                x-text="canSubmit ? 'Spremno' : 'Nedovršeno'"
                            ></span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
