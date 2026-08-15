<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold leading-tight text-gray-900">
                Laravel DSL Generator
            </h2>
            <p class="text-sm text-gray-500">
                {{ $project ? 'Izmijeni postojeću specifikaciju i ponovo generiši projekat.' : 'Vizuelno definiši aplikaciju; DSL se formira automatski ispod haube.' }}
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

            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="grid gap-4 p-5 lg:grid-cols-[minmax(260px,1fr)_auto] lg:items-end">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Naziv aplikacije</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="npr. Inventory System"
                                x-model="projectName"
                                required
                            >
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            <x-input-error class="mt-2" :messages="$errors->get('dsl')" />
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button
                                type="button"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                @click="addEntity()"
                            >
                                Dodaj model
                            </button>

                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-400"
                                :disabled="!canSubmit"
                            >
                                {{ $project ? 'Sačuvaj i generiši' : 'Generiši projekat' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 xl:grid-cols-[280px_minmax(0,1fr)_420px]">
                    <aside class="rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="font-semibold text-gray-900">Modeli</h3>
                                <button
                                    type="button"
                                    class="rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800"
                                    @click="addEntity()"
                                >
                                    Novi
                                </button>
                            </div>
                        </div>

                        <div class="p-3">
                            <template x-if="entities.length === 0">
                                <button
                                    type="button"
                                    class="flex min-h-44 w-full flex-col items-center justify-center rounded-md border border-dashed border-gray-300 px-4 py-8 text-center hover:border-gray-400 hover:bg-gray-50"
                                    @click="addEntity()"
                                >
                                    <span class="text-sm font-semibold text-gray-900">Dodaj prvi model</span>
                                    <span class="mt-1 text-sm text-gray-500">Npr. Product, Category, Order</span>
                                </button>
                            </template>

                            <div class="space-y-2" x-show="entities.length > 0">
                                <template x-for="(entity, index) in entities" :key="index">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-3 text-left text-sm hover:bg-gray-50"
                                        :class="selectedEntityIndex === index ? 'bg-indigo-50 text-indigo-900 ring-1 ring-indigo-200' : 'text-gray-700'"
                                        @click="selectEntity(index)"
                                    >
                                        <span class="truncate font-medium" x-text="entityLabel(entity, index)"></span>
                                        <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-xs text-gray-500 ring-1 ring-gray-200" x-text="entity.fields.length"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </aside>

                    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                        <template x-if="!selectedEntity">
                            <div class="flex min-h-[520px] flex-col items-center justify-center p-8 text-center">
                                <div class="max-w-sm">
                                    <h3 class="text-lg font-semibold text-gray-900">Nema izabranog modela</h3>
                                    <p class="mt-2 text-sm text-gray-500">Dodaj model da bi definisala njegova polja i pravila.</p>
                                    <button
                                        type="button"
                                        class="mt-5 inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800"
                                        @click="addEntity()"
                                    >
                                        Dodaj model
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-if="selectedEntity">
                            <div class="divide-y divide-gray-200">
                                <div class="p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <label class="block text-sm font-medium text-gray-700">Naziv modela</label>
                                            <input
                                                type="text"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                placeholder="npr. Product"
                                                x-model="selectedEntity.name"
                                                required
                                            >
                                        </div>

                                        <button
                                            type="button"
                                            class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"
                                            @click="removeEntity(selectedEntityIndex)"
                                        >
                                            Ukloni model
                                        </button>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                        <h3 class="font-semibold text-gray-900">Polja modela</h3>
                                        <button
                                            type="button"
                                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                            @click="addField(selectedEntity)"
                                        >
                                            Dodaj polje
                                        </button>
                                    </div>

                                    <template x-if="selectedEntity.fields.length === 0">
                                        <button
                                            type="button"
                                            class="flex min-h-36 w-full flex-col items-center justify-center rounded-md border border-dashed border-gray-300 px-4 py-8 text-center hover:border-gray-400 hover:bg-gray-50"
                                            @click="addField(selectedEntity)"
                                        >
                                            <span class="text-sm font-semibold text-gray-900">Dodaj prvo polje</span>
                                            <span class="mt-1 text-sm text-gray-500">Npr. name, title, price, email</span>
                                        </button>
                                    </template>

                                    <div class="overflow-hidden rounded-md border border-gray-200" x-show="selectedEntity.fields.length > 0">
                                        <div class="hidden grid-cols-[minmax(160px,1fr)_150px_220px_44px] gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 md:grid">
                                            <div>Naziv</div>
                                            <div>Tip</div>
                                            <div>Pravila</div>
                                            <div></div>
                                        </div>

                                        <template x-for="(field, fieldIndex) in selectedEntity.fields" :key="fieldIndex">
                                            <div class="grid gap-3 border-b border-gray-100 p-4 last:border-b-0 md:grid-cols-[minmax(160px,1fr)_150px_220px_44px] md:items-center">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-gray-500 md:hidden">Naziv</label>
                                                    <input
                                                        type="text"
                                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        placeholder="name"
                                                        x-model="field.name"
                                                        required
                                                    >
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-gray-500 md:hidden">Tip</label>
                                                    <select
                                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        x-model="field.type"
                                                    >
                                                        <template x-for="type in fieldTypes" :key="type">
                                                            <option :value="type" x-text="type"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div class="flex flex-wrap gap-3">
                                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" x-model="field.required" @change="if (field.required) field.nullable = false">
                                                        Required
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" x-model="field.nullable" @change="if (field.nullable) field.required = false">
                                                        Nullable
                                                    </label>
                                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" x-model="field.unique">
                                                        Unique
                                                    </label>
                                                </div>

                                                <button
                                                    type="button"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-red-200 text-sm font-semibold text-red-700 hover:bg-red-50"
                                                    title="Ukloni polje"
                                                    @click="removeField(selectedEntity, fieldIndex)"
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

                    <aside class="space-y-5">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h3 class="font-semibold text-gray-900">DSL preview</h3>
                            </div>
                            <div class="p-4">
                                <pre class="max-h-[560px] overflow-auto rounded-md bg-gray-950 p-4 text-xs leading-5 text-gray-100" x-text="dslSource"></pre>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white p-4 text-sm shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-medium text-gray-900">Status</span>
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="canSubmit ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'"
                                    x-text="canSubmit ? 'Spremno' : 'Nedovršeno'"
                                ></span>
                            </div>
                            <p class="mt-3 text-gray-600">Relacije dodajemo u sljedećem koraku.</p>
                        </div>
                    </aside>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
