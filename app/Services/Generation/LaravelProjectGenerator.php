<?php

namespace App\Services\Generation;

use Illuminate\Support\Str;

class LaravelProjectGenerator
{
    private const ROOT_FILES = [
        '.editorconfig',
        '.gitattributes',
        '.gitignore',
        '.npmrc',
        'artisan',
        'postcss.config.js',
        'tailwind.config.js',
        'vite.config.js',
    ];

    private const CONFIG_FILES = [
        'app.php',
        'auth.php',
        'cache.php',
        'database.php',
        'filesystems.php',
        'logging.php',
        'mail.php',
        'queue.php',
        'services.php',
        'session.php',
    ];

    private const ENTITY_FEATURES = [
        'index',
        'create',
        'edit',
        'show',
        'delete',
    ];

    public function generate(array $specification, string $outputDir): void
    {
        $this->ensureCleanDirectory($outputDir);

        $appName = $specification['app'];
        $entities = collect($specification['entities'])
            ->map(fn (array $entity) => $this->withDefaultFeatures($entity))
            ->all();

        $this->writeBaseProject($outputDir, $appName);
        $this->write($outputDir.'/README.md', $this->readme($appName, $entities));
        $this->write($outputDir.'/routes/web.php', $this->routes($entities));
        $this->write($outputDir.'/routes/auth.php', $this->authRoutes());
        $this->write($outputDir.'/resources/views/layouts/app.blade.php', $this->layout($appName, $entities));
        $this->write($outputDir.'/resources/views/layouts/auth.blade.php', $this->authLayout($appName));
        $this->writeUiComponents($outputDir);
        $this->write($outputDir.'/resources/views/dashboard.blade.php', $this->dashboardView($appName, $entities));
        $this->write($outputDir.'/resources/views/auth/login.blade.php', $this->loginView());
        $this->write($outputDir.'/resources/views/auth/register.blade.php', $this->registerView());
        $this->write($outputDir.'/app/Http/Controllers/Auth/AuthenticatedSessionController.php', $this->authenticatedSessionController());
        $this->write($outputDir.'/app/Http/Controllers/Auth/RegisteredUserController.php', $this->registeredUserController());
        $this->write($outputDir.'/app/Http/Requests/Auth/LoginRequest.php', $this->loginRequest());
        $this->write($outputDir.'/tests/TestCase.php', $this->testCase());
        $this->write($outputDir.'/tests/Feature/ExampleTest.php', $this->featureExampleTest());
        $this->write($outputDir.'/tests/Unit/ExampleTest.php', $this->unitExampleTest());

        foreach ($entities as $entity) {
            $this->write($outputDir.'/app/Models/'.$entity['name'].'.php', $this->model($entity));
            $this->write($outputDir.'/app/Http/Controllers/'.$entity['name'].'Controller.php', $this->controller($entity));
            $this->writeViews($outputDir, $entity);
        }

        foreach ($entities as $index => $entity) {
            $this->write(
                $outputDir.'/database/migrations/'.$this->migrationFileName($index, $entity).'.php',
                $this->migration($entity),
            );
        }

        $foreignKeyEntities = collect($entities)
            ->filter(fn (array $entity): bool => $this->belongsToRelations($entity) !== [])
            ->values()
            ->all();

        foreach ($foreignKeyEntities as $index => $entity) {
            $this->write(
                $outputDir.'/database/migrations/'.$this->foreignKeysMigrationFileName(count($entities) + $index, $entity).'.php',
                $this->foreignKeysMigration($entity),
            );
        }

        $pivotOffset = count($entities) + count($foreignKeyEntities);

        foreach ($this->belongsToManyPivotRelations($entities) as $index => $relation) {
            $this->write(
                $outputDir.'/database/migrations/'.$this->pivotMigrationFileName($pivotOffset + $index, $relation).'.php',
                $this->pivotMigration($relation),
            );
        }
    }

    private function writeBaseProject(string $outputDir, string $appName): void
    {
        foreach (self::ROOT_FILES as $file) {
            $this->copyBaseFile($file, $outputDir.'/'.$file);
        }

        $databaseName = $this->databaseName($appName);

        $this->write($outputDir.'/composer.json', $this->composerJson($appName));
        $this->write($outputDir.'/package.json', $this->packageJson());
        $this->write($outputDir.'/.env.example', $this->envExample($appName));
        $this->write($outputDir.'/phpunit.xml', $this->phpunitXml($databaseName));

        $this->copyBaseFile('bootstrap/app.php', $outputDir.'/bootstrap/app.php');
        $this->copyBaseFile('bootstrap/providers.php', $outputDir.'/bootstrap/providers.php');
        $this->write($outputDir.'/bootstrap/cache/.gitignore', "*\n!.gitignore\n");

        foreach (self::CONFIG_FILES as $file) {
            $this->copyBaseFile('config/'.$file, $outputDir.'/config/'.$file);
        }

        foreach ([
            'app/Http/Controllers/Controller.php',
            'app/Models/User.php',
            'app/Providers/AppServiceProvider.php',
            'public/.htaccess',
            'public/favicon.ico',
            'public/index.php',
            'public/logo.svg',
            'public/robots.txt',
            'resources/js/app.js',
            'routes/console.php',
        ] as $file) {
            $this->copyBaseFile($file, $outputDir.'/'.$file);
        }

        $this->write($outputDir.'/database/seeders/DatabaseSeeder.php', $this->databaseSeeder());
        $this->write($outputDir.'/database/seeders/UserSeeder.php', $this->userSeeder());
        $this->write($outputDir.'/resources/css/app.css', $this->generatedAppCss());

        foreach (glob(base_path('database/migrations/0001_*.php')) ?: [] as $migration) {
            $this->copyBaseFile(
                'database/migrations/'.basename($migration),
                $outputDir.'/database/migrations/'.basename($migration),
            );
        }

        foreach ([
            'database/.gitignore',
            'storage/app/.gitignore',
            'storage/app/private/.gitignore',
            'storage/app/public/.gitignore',
            'storage/framework/.gitignore',
            'storage/framework/cache/.gitignore',
            'storage/framework/cache/data/.gitignore',
            'storage/framework/sessions/.gitignore',
            'storage/framework/testing/.gitignore',
            'storage/framework/views/.gitignore',
            'storage/logs/.gitignore',
        ] as $file) {
            $this->copyBaseFile($file, $outputDir.'/'.$file);
        }
    }

    private function copyBaseFile(string $source, string $target): void
    {
        $sourcePath = base_path($source);
        if (!is_file($sourcePath)) {
            return;
        }

        $dir = dirname($target);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        copy($sourcePath, $target);
    }

    private function generatedAppCss(): string
    {
        return <<<'CSS'
@import '@fontsource/plus-jakarta-sans/400.css';
@import '@fontsource/plus-jakarta-sans/500.css';
@import '@fontsource/plus-jakarta-sans/600.css';
@import '@fontsource/plus-jakarta-sans/700.css';

@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
    html {
        color-scheme: light;
    }

    body {
        @apply bg-[#F8FAFC] text-[#1E293B];
        letter-spacing: 0;
    }

    ::selection {
        @apply bg-[#E0E7FF] text-[#1E293B];
    }

    a {
        @apply text-inherit;
    }

    button,
    input,
    textarea,
    select {
        font: inherit;
    }
}

@layer components {
    .app-shell {
        @apply min-h-screen bg-[#F8FAFC] text-[#1E293B];
    }

    [x-cloak] {
        display: none !important;
    }

    .app-container {
        @apply mx-auto max-w-7xl px-4 sm:px-6 lg:px-8;
    }

    .mobile-topbar {
        @apply sticky top-0 z-40 flex h-16 items-center justify-between border-b border-[#E2E8F0] bg-white px-4 lg:hidden;
    }

    .sidebar-backdrop {
        @apply fixed inset-0 z-40 bg-[#1E293B]/30 lg:hidden;
    }

    .sidebar {
        @apply fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-[#E2E8F0] bg-white transition-transform duration-200 lg:translate-x-0;
    }

    .sidebar.is-closed {
        @apply -translate-x-full lg:translate-x-0;
    }

    .sidebar-header {
        @apply flex h-20 items-center justify-between border-b border-[#E2E8F0] px-5;
    }

    .brand-link {
        @apply inline-flex items-center gap-3 text-[#1E293B] no-underline;
    }

    .brand-logo {
        @apply h-10 w-10 shrink-0;
    }

    .brand-link span {
        @apply text-sm font-bold;
    }

    .sidebar-nav {
        @apply flex-1 space-y-1 overflow-y-auto px-3 py-5;
    }

    .nav,
    .actions-row,
    .row {
        @apply flex flex-wrap items-center gap-3;
    }

    .sidebar-footer {
        @apply border-t border-[#E2E8F0] p-4;
    }

    .nav-link {
        @apply flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-[#64748B] no-underline transition duration-200 hover:bg-[#EEF2FF] hover:text-[#4F46E5] focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:ring-offset-2;
    }

    .nav-link.active {
        @apply bg-[#E0E7FF] text-[#4F46E5];
    }

    .nav-icon {
        @apply flex h-8 w-8 items-center justify-center rounded-lg bg-[#F8FAFC] text-[#6366F1];
    }

    .user-chip {
        @apply flex items-center gap-3 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] px-3 py-3 text-sm font-medium text-[#1E293B];
    }

    .user-avatar {
        @apply flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#E0E7FF] text-sm font-bold text-[#4F46E5];
    }

    .content-shell {
        @apply min-h-screen lg:pl-72;
    }

    .page-main {
        @apply px-4 py-6 sm:px-6 lg:px-8;
    }

    .page-header {
        @apply mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between;
    }

    .detail-header {
        @apply mb-6 border-b border-[#E2E8F0] pb-5;
    }

    .detail-header-top {
        @apply flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between;
    }

    .detail-breadcrumbs {
        @apply flex flex-wrap items-center gap-2 text-sm text-[#64748B];
    }

    .back-button {
        @apply inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#CBD5E1] bg-white text-[#1E293B] transition hover:border-[#A5B4FC] hover:bg-[#F8FAFC] hover:text-[#4F46E5];
    }

    .top-actions {
        @apply flex flex-wrap items-center gap-2;
    }

    .page-title {
        @apply space-y-1;
    }

    .eyebrow {
        @apply text-xs font-semibold uppercase tracking-wide text-[#6366F1];
    }

    h1 {
        @apply m-0 text-2xl font-bold leading-tight text-[#1E293B] sm:text-3xl;
    }

    h2 {
        @apply text-lg font-semibold text-[#1E293B];
    }

    .muted {
        @apply text-sm text-[#64748B];
    }

    .card {
        @apply rounded-2xl border border-[#E2E8F0] bg-white shadow-[0_10px_30px_rgba(30,41,59,0.05)];
    }

    .table-card {
        @apply overflow-hidden;
    }

    .table-toolbar {
        @apply flex flex-col gap-3 border-b border-[#E2E8F0] px-5 py-4 sm:flex-row sm:items-center sm:justify-between;
    }

    .table-title {
        @apply text-sm font-semibold text-[#1E293B];
    }

    .table-meta {
        @apply text-sm text-[#64748B];
    }

    .table-scroll {
        @apply overflow-x-auto;
    }

    table {
        @apply w-full border-collapse;
    }

    th {
        @apply border-b border-[#E2E8F0] bg-[#F8FAFC] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#64748B];
    }

    td {
        @apply border-b border-[#E2E8F0] px-5 py-4 text-sm text-[#1E293B];
    }

    tr:last-child td {
        @apply border-b-0;
    }

    tbody tr {
        @apply transition duration-150 hover:bg-[#F8FAFC];
    }

    .numeric-cell {
        @apply text-right tabular-nums;
    }

    .actions-heading {
        @apply w-36 text-right;
    }

    .actions {
        @apply w-36 text-right align-middle;
    }

    .action-list {
        @apply inline-flex items-center justify-end gap-2;
    }

    .actions-row {
        @apply mt-5;
    }

    .button {
        @apply inline-flex items-center justify-center gap-2 rounded-[10px] border px-4 py-2.5 text-sm font-semibold no-underline transition duration-200 focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50;
    }

    .button.primary,
    button.button {
        @apply border-[#6366F1] bg-[#6366F1] text-white shadow-[0_8px_18px_rgba(99,102,241,0.18)] hover:border-[#4F46E5] hover:bg-[#4F46E5];
    }

    .button.secondary,
    .button:not(.primary):not(.danger) {
        @apply border-[#CBD5E1] bg-white text-[#1E293B] hover:border-[#A5B4FC] hover:bg-[#F8FAFC] hover:text-[#6366F1];
    }

    .button.danger {
        @apply border-[#FEE2E2] bg-white text-[#EF4444] hover:bg-[#FEE2E2];
    }

    .icon-button {
        @apply inline-flex h-9 w-9 items-center justify-center rounded-[10px] border border-[#E2E8F0] bg-white text-[#64748B] transition duration-200 hover:border-[#CBD5E1] hover:bg-[#F8FAFC] hover:text-[#1E293B] focus:outline-none focus:ring-2 focus:ring-[#6366F1] focus:ring-offset-2;
    }

    .icon-button.primary {
        @apply border-[#E0E7FF] bg-[#E0E7FF] text-[#4F46E5] hover:bg-[#C7D2FE];
    }

    .icon-button.danger {
        @apply border-[#FEE2E2] bg-white text-[#EF4444] hover:bg-[#FEE2E2];
    }

    .button svg,
    .icon-button svg,
    .back-button svg,
    .nav-link svg {
        @apply h-4 w-4 shrink-0;
    }

    form.inline {
        @apply m-0;
    }

    .form-card {
        @apply grid w-full gap-6 p-6;
    }

    .form-section {
        @apply grid gap-5 md:grid-cols-2;
    }

    .form-section-title {
        @apply border-b border-[#E2E8F0] pb-3 text-base font-semibold text-[#1E293B] md:col-span-2;
    }

    .form-section > div,
    .form-section > fieldset {
        @apply grid gap-2;
    }

    .form-actions {
        @apply flex flex-col-reverse gap-3 border-t border-[#E2E8F0] pt-5 sm:flex-row sm:justify-end;
    }

    label {
        @apply text-sm font-medium text-[#1E293B];
    }

    fieldset {
        @apply m-0 grid gap-2 border-0 p-0 text-sm text-[#1E293B];
    }

    legend {
        @apply mb-1 text-sm font-medium text-[#1E293B];
    }

    .field-full {
        @apply md:col-span-2;
    }

    .required-mark {
        @apply text-[#EF4444];
    }

    input,
    textarea,
    select {
        @apply w-full rounded-[10px] border-[#CBD5E1] bg-white text-sm text-[#1E293B] shadow-sm placeholder:text-[#94A3B8] transition duration-200 focus:border-[#6366F1] focus:ring-[#6366F1];
    }

    input:disabled,
    textarea:disabled,
    select:disabled,
    input[readonly],
    textarea[readonly] {
        @apply cursor-not-allowed border-[#E2E8F0] bg-[#F1F5F9] text-[#94A3B8] shadow-none;
    }

    input[type='checkbox'] {
        @apply h-4 w-4 rounded border-[#CBD5E1] text-[#6366F1] focus:ring-[#6366F1];
    }

    input[type='file'] {
        @apply cursor-pointer border-dashed bg-[#F8FAFC] file:mr-4 file:rounded-md file:border-0 file:bg-[#EEF2FF] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-[#4F46E5] hover:bg-white;
    }

    textarea {
        @apply min-h-32;
    }

    select[multiple] {
        @apply min-h-32;
    }

    .checkbox-list {
        @apply grid gap-2 sm:grid-cols-2;
    }

    .checkbox-card {
        @apply flex items-center gap-3 rounded-[10px] border border-[#E2E8F0] bg-white px-3 py-2.5 text-sm font-medium text-[#1E293B] transition hover:border-[#A5B4FC] hover:bg-[#F8FAFC];
    }

    .relation-empty {
        @apply rounded-[10px] border border-dashed border-[#CBD5E1] bg-[#F8FAFC] px-4 py-3;
    }

    .code-input {
        @apply min-h-48 font-mono text-xs leading-5;
    }

    .error {
        @apply text-sm font-medium text-red-600;
    }

    .status {
        @apply rounded-md border border-[#BBF7D0] bg-[#DCFCE7] p-3 text-sm font-medium text-[#047857];
    }

    .status.danger {
        @apply border-[#FEE2E2] bg-[#FEE2E2] text-[#B91C1C];
    }

    .status.warning {
        @apply border-[#FEF3C7] bg-[#FFFBEB] text-[#92400E];
    }

    .status.info {
        @apply border-[#DBEAFE] bg-[#EFF6FF] text-[#1D4ED8];
    }

    .flash-region {
        @apply mb-5 grid gap-3;
    }

    .flash-alert {
        @apply flex items-start justify-between gap-4;
    }

    .flash-dismiss {
        @apply -m-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-current opacity-70 transition hover:bg-white/50 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-current;
    }

    .ui-link {
        @apply font-medium text-[#6366F1] underline decoration-[#E0E7FF] underline-offset-4 transition hover:text-[#4F46E5];
    }

    .detail-list {
        @apply mb-5 grid overflow-hidden rounded-2xl border border-[#E2E8F0] bg-white shadow-[0_10px_30px_rgba(30,41,59,0.05)] md:grid-cols-[240px_1fr];
    }

    .detail-list dt,
    .detail-list dd {
        @apply m-0 border-b border-[#E2E8F0] px-5 py-4;
    }

    .detail-list dt {
        @apply bg-[#F8FAFC] text-sm font-medium text-[#64748B];
    }

    .detail-list dd {
        @apply text-sm text-[#1E293B];
    }

    .related-card {
        @apply mb-5 overflow-hidden;
    }

    .related-card ul {
        @apply m-0 list-none space-y-2 p-0 text-sm text-[#1E293B];
    }

    .record-card,
    .summary-card {
        @apply mb-5 p-5;
    }

    .record-card h2,
    .summary-card h2 {
        @apply mb-4 text-base font-semibold text-[#1E293B];
    }

    .detail-grid {
        @apply grid gap-3 md:grid-cols-2;
    }

    .detail-item {
        @apply rounded-[10px] border border-[#E2E8F0] bg-[#F8FAFC] px-4 py-3;
    }

    .detail-item span {
        @apply block text-xs font-semibold uppercase tracking-wide text-[#64748B];
    }

    .detail-item strong {
        @apply mt-1 block text-sm font-semibold text-[#1E293B];
    }

    .summary-grid {
        @apply mt-5 grid gap-5 md:grid-cols-2;
    }

    .summary-grid h3 {
        @apply mb-2 text-sm font-semibold text-[#1E293B];
    }

    .summary-grid ul {
        @apply m-0 list-disc space-y-1 pl-5 text-sm text-[#475569];
    }

    .resource-links {
        @apply flex flex-wrap gap-3;
    }

    .confirm-dialog-backdrop {
        @apply fixed inset-0 z-50 flex items-center justify-center p-4;
        background: rgba(15, 23, 42, 0.55);
    }

    .confirm-dialog-panel {
        @apply w-full max-w-md rounded-[12px] border border-[#E2E8F0] bg-white p-5 shadow-2xl;
    }

    .confirm-dialog-panel h2 {
        @apply text-lg font-semibold text-[#1E293B];
    }

    .confirm-dialog-panel p {
        @apply mt-2 text-sm text-[#64748B];
    }

    .confirm-dialog-actions {
        @apply mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end;
    }

    .pagination {
        @apply mt-5 flex flex-col gap-3 rounded-[12px] border border-[#E2E8F0] bg-white px-4 py-3 text-sm text-[#64748B] sm:flex-row sm:items-center sm:justify-between;
    }

    .pagination-pages {
        @apply flex flex-wrap items-center gap-2;
    }

    .pagination-link,
    .pagination-current,
    .pagination-disabled {
        @apply inline-flex h-9 min-w-9 items-center justify-center rounded-[10px] border px-3 text-sm font-semibold no-underline;
    }

    .pagination-link {
        @apply border-[#E2E8F0] bg-white text-[#1E293B] transition hover:border-[#A5B4FC] hover:bg-[#EEF2FF] hover:text-[#4F46E5];
    }

    .pagination-current {
        @apply border-[#6366F1] bg-[#6366F1] text-white;
    }

    .pagination-disabled {
        @apply border-[#E2E8F0] bg-[#F8FAFC] text-[#94A3B8];
    }

    .badge {
        @apply inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium;
    }

    .badge.success {
        @apply border-[#D1FAE5] bg-[#D1FAE5] text-[#047857];
    }

    .badge.muted {
        @apply border-[#E2E8F0] bg-[#F8FAFC] text-[#64748B];
    }

    .image-thumb {
        @apply h-14 w-14 rounded-[10px] border border-[#E2E8F0] object-cover;
    }

    .empty-state {
        @apply flex min-h-52 flex-col items-center justify-center gap-3 px-6 py-10 text-center;
    }

    .empty-state.compact {
        @apply min-h-36 rounded-[10px] border border-dashed border-[#CBD5E1] bg-[#F8FAFC] px-4 py-6;
    }

    .empty-icon {
        @apply flex h-12 w-12 items-center justify-center rounded-2xl bg-[#E0E7FF] text-[#4F46E5];
    }

    .auth-body {
        @apply grid min-h-screen place-items-center bg-[#F8FAFC] p-6 text-[#1E293B];
    }

    .auth-card {
        @apply w-full max-w-md rounded-2xl border border-[#E2E8F0] bg-white p-7 shadow-[0_10px_30px_rgba(30,41,59,0.05)];
    }

    .auth-brand {
        @apply mb-6;
    }

    .auth-card h1 {
        @apply text-3xl;
    }

    .auth-card p {
        @apply mt-2 text-sm leading-6 text-[#64748B];
    }

    .auth-card form {
        @apply mt-6 grid gap-4;
    }

    .auth-card .row {
        @apply justify-between;
    }
}
CSS;
    }

    private function composerJson(string $appName): string
    {
        $packageName = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $appName));
        $packageName = trim($packageName, '-') ?: 'generated-laravel-app';

        $composer = [
            '$schema' => 'https://getcomposer.org/schema.json',
            'name' => 'generated/'.$packageName,
            'type' => 'project',
            'description' => 'Generated Laravel application.',
            'license' => 'MIT',
            'require' => [
                'php' => '^8.4',
                'laravel/framework' => '^13.8',
                'laravel/tinker' => '^3.0',
            ],
            'require-dev' => [
                'laravel/breeze' => '^2.4',
                'laravel/pail' => '^1.2.5',
                'laravel/pint' => '^1.27',
                'mockery/mockery' => '^1.6',
                'nunomaduro/collision' => '^8.6',
                'phpunit/phpunit' => '^12.5.12',
            ],
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                    'Database\\Seeders\\' => 'database/seeders/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    'Tests\\' => 'tests/',
                ],
            ],
            'scripts' => [
                'setup' => [
                    'composer install',
                    '@php -r "file_exists(\'.env\') || copy(\'.env.example\', \'.env\');"',
                    '@php artisan key:generate',
                    '@php artisan migrate --force',
                    '@php artisan db:seed --force',
                    '@php artisan storage:link',
                    'npm install',
                    'npm run build',
                ],
                'dev' => [
                    'Composer\\Config::disableProcessTimeout',
                    'npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" "php artisan serve" "npm run dev" --names=server,vite --kill-others',
                ],
                'test' => [
                    '@php artisan config:clear --ansi',
                    '@php artisan test',
                ],
                'post-autoload-dump' => [
                    'Illuminate\\Foundation\\ComposerScripts::postAutoloadDump',
                    '@php artisan package:discover --ansi',
                ],
                'post-update-cmd' => [
                    '@php artisan vendor:publish --tag=laravel-assets --ansi --force',
                ],
                'post-root-package-install' => [
                    '@php -r "file_exists(\'.env\') || copy(\'.env.example\', \'.env\');"',
                ],
                'post-create-project-cmd' => [
                    '@php artisan key:generate --ansi',
                    '@php artisan migrate --graceful --ansi',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'dont-discover' => [],
                ],
            ],
            'config' => [
                'optimize-autoloader' => true,
                'preferred-install' => 'dist',
                'sort-packages' => true,
            ],
            'minimum-stability' => 'stable',
            'prefer-stable' => true,
        ];

        return json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    }

    private function packageJson(): string
    {
        return <<<'JSON'
{
    "$schema": "https://www.schemastore.org/package.json",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "@fontsource/plus-jakarta-sans": "^5.2.8",
        "@tailwindcss/forms": "^0.5.2",
        "@tailwindcss/vite": "^4.0.0",
        "alpinejs": "^3.4.2",
        "autoprefixer": "^10.4.2",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^3.1",
        "postcss": "^8.4.31",
        "tailwindcss": "^3.1.0",
        "vite": "^8.0.0"
    }
}
JSON;
    }

    private function envExample(string $appName): string
    {
        $quotedAppName = str_contains($appName, ' ')
            ? '"'.addcslashes($appName, "\"\\").'"'
            : $appName;
        $databaseName = $this->databaseName($appName);

        return <<<ENV
APP_NAME={$quotedAppName}
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE={$databaseName}
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="\${APP_NAME}"

ENV;
    }

    private function phpunitXml(string $databaseName): string
    {
        $testDatabaseName = $databaseName.'_test';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <server name="APP_ENV" value="testing"/>
        <server name="APP_KEY" value="base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA="/>
        <server name="BCRYPT_ROUNDS" value="4"/>
        <server name="CACHE_STORE" value="array"/>
        <server name="DB_CONNECTION" value="mysql"/>
        <server name="DB_HOST" value="127.0.0.1"/>
        <server name="DB_PORT" value="3306"/>
        <server name="DB_DATABASE" value="{$testDatabaseName}"/>
        <server name="DB_USERNAME" value="root"/>
        <server name="DB_PASSWORD" value=""/>
        <server name="MAIL_MAILER" value="array"/>
        <server name="QUEUE_CONNECTION" value="sync"/>
        <server name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>

XML;
    }

    private function databaseName(string $appName): string
    {
        $name = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $appName));
        $name = trim($name, '_');

        return $name !== '' ? $name : 'generated_laravel_app';
    }

    private function databaseSeeder(): string
    {
        return <<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}

PHP;
    }

    private function userSeeder(): string
    {
        return <<<'PHP'
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => null,
        ]);
    }
}

PHP;
    }

    private function indent(string $content, int $spaces): string
    {
        $prefix = str_repeat(' ', $spaces);

        return collect(explode("\n", $content))
            ->map(fn (string $line) => $line === '' ? $line : $prefix.$line)
            ->implode("\n");
    }

    private function ensureCleanDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($it as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    private function write(string $path, string $content): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, $content);
    }

    private function readme(string $appName, array $entities): string
    {
        $databaseName = $this->databaseName($appName);
        $list = collect($entities)
            ->map(function (array $entity) {
                $relations = collect($entity['relations'] ?? [])
                    ->map(fn (array $relation) => $relation['type'].' '.$relation['target'])
                    ->implode(', ');

                return '- '.$entity['name'].' (`'.$entity['table'].'`)'.($relations ? ' - relations: '.$relations : '');
            })
            ->implode("\n");

        return <<<MD
# {$appName}

This is a complete Laravel application generated from a DSL specification. It includes a Laravel application skeleton, basic authentication, database migrations, Eloquent models, resource controllers, web routes, and Blade CRUD views.

## Generated Entities

{$list}

## Requirements

- PHP 8.4 or newer
- Composer
- Node.js and npm
- MySQL or MariaDB

## Setup

1. Install PHP dependencies:

```bash
composer install
```

2. Install JavaScript dependencies:

```bash
npm install
```

3. Create the environment file and application key:

```bash
cp .env.example .env
php artisan key:generate
```

4. Create the MySQL database:

```bash
mysql -u root -p -e "CREATE DATABASE {$databaseName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

If your MySQL username, password, host, or database name is different, update the `DB_*` values in `.env` before running migrations.

5. Run database migrations:

```bash
php artisan migrate
```

6. Seed the default test user:

```bash
php artisan db:seed
```

The generated app creates a test user with `test@example.com` and password `password`.

7. Link public storage for generated file and image uploads:

```bash
php artisan storage:link
```

8. Start the Laravel server:

```bash
php artisan serve
```

9. In a second terminal, start Vite:

```bash
npm run dev
```

Open `http://127.0.0.1:8000`, register a user, then use the generated CRUD screens from the dashboard.

## Production Build

```bash
npm run build
php artisan config:cache
php artisan route:cache
```

MD;
    }

    private function routes(array $entities): string
    {
        $firstRoute = collect($entities)
            ->map(fn (array $entity) => $this->entityPrimaryRouteName($entity))
            ->filter()
            ->first() ?? 'dashboard';

        $resourceRoutes = collect($entities)
            ->map(function (array $entity): ?string {
                $actions = $this->resourceActions($entity);

                if ($actions === []) {
                    return null;
                }

                $quotedActions = collect($actions)
                    ->map(fn (string $action) => "'{$action}'")
                    ->implode(', ');

                return "Route::resource('{$entity['route']}', {$entity['name']}Controller::class)->only([{$quotedActions}]);";
            })
            ->filter()
            ->implode("\n");

        $controllers = collect($entities)
            ->map(fn (array $entity) => "use App\\Http\\Controllers\\{$entity['name']}Controller;")
            ->implode("\n");

        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;
{$controllers}

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
{$this->indent($resourceRoutes, 4)}
    Route::get('/generated', function () {
        return redirect()->route('{$firstRoute}');
    })->name('generated.index');
});

require __DIR__.'/auth.php';

PHP;
    }

    private function withDefaultFeatures(array $entity): array
    {
        $entity['features'] = array_merge(
            array_fill_keys(self::ENTITY_FEATURES, true),
            $entity['features'] ?? [],
        );

        return $entity;
    }

    private function entityFeature(array $entity, string $feature): bool
    {
        return (bool) ($entity['features'][$feature] ?? true);
    }

    private function resourceActions(array $entity): array
    {
        $actions = [];

        if ($this->entityFeature($entity, 'index')) {
            $actions[] = 'index';
        }

        if ($this->entityFeature($entity, 'create')) {
            $actions[] = 'create';
            $actions[] = 'store';
        }

        if ($this->entityFeature($entity, 'show')) {
            $actions[] = 'show';
        }

        if ($this->entityFeature($entity, 'edit')) {
            $actions[] = 'edit';
            $actions[] = 'update';
        }

        if ($this->entityFeature($entity, 'delete')) {
            $actions[] = 'destroy';
        }

        return $actions;
    }

    private function entityPrimaryRouteName(array $entity): ?string
    {
        if ($this->entityFeature($entity, 'index')) {
            return $entity['route'].'.index';
        }

        if ($this->entityFeature($entity, 'create')) {
            return $entity['route'].'.create';
        }

        return null;
    }

    private function entityBackRoute(array $entity): string
    {
        return $this->entityFeature($entity, 'index')
            ? "route('{$entity['route']}.index')"
            : "route('dashboard')";
    }

    private function entityRedirect(array $entity, string $variable): string
    {
        if ($this->entityFeature($entity, 'index')) {
            return "redirect()->route('{$entity['route']}.index')";
        }

        if ($this->entityFeature($entity, 'show')) {
            return "redirect()->route('{$entity['route']}.show', \${$variable})";
        }

        return "redirect()->route('dashboard')";
    }

    private function authRoutes(): string
    {
        return <<<'PHP'
<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

PHP;
    }

    private function model(array $entity): string
    {
        $fillable = collect($this->fillableAttributes($entity))
            ->map(fn (string $attribute) => "        '{$attribute}',")
            ->implode("\n");
        $casts = collect($this->fieldCasts($entity))
            ->map(fn (string $cast, string $attribute) => "        '{$attribute}' => '{$cast}',")
            ->implode("\n");
        $hidden = collect($this->hiddenAttributes($entity))
            ->map(fn (string $attribute) => "        '{$attribute}',")
            ->implode("\n");

        $relations = collect($entity['relations'] ?? [])
            ->map(fn (array $relation) => $this->modelRelationMethod($relation))
            ->filter()
            ->implode("\n");

        $displayFields = collect($this->displayNameFields($entity))
            ->map(fn (string $field) => "            '{$field}',")
            ->implode("\n");
        $displayNameMethod = $displayFields
            ? <<<PHP
    public function displayName(): string
    {
        foreach ([
{$displayFields}
        ] as \$displayField) {
            if (filled(\$this->{\$displayField})) {
                return (string) \$this->{\$displayField};
            }
        }

        return (string) \$this->id;
    }
PHP
            : <<<PHP
    public function displayName(): string
    {
        return (string) \$this->id;
    }
PHP;

        $castsBlock = $casts ? "\n    protected \$casts = [\n{$casts}\n    ];\n" : '';
        $hiddenBlock = $hidden ? "\n    protected \$hidden = [\n{$hidden}\n    ];\n" : '';
        $relationsBlock = $relations ? "\n{$relations}\n" : '';

        return <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class {$entity['name']} extends Model
{
    protected \$fillable = [
{$fillable}
    ];
{$castsBlock}{$hiddenBlock}
{$relationsBlock}
{$displayNameMethod}
}

PHP;
    }

    private function fillableAttributes(array $entity): array
    {
        $foreignKeys = collect($this->belongsToRelations($entity))
            ->map(fn (array $relation) => $relation['foreign_key'])
            ->all();

        return array_merge(
            collect($entity['fields'])->pluck('name')->all(),
            $foreignKeys,
        );
    }

    private function fieldCasts(array $entity): array
    {
        return collect($entity['fields'])
            ->mapWithKeys(function (array $field): array {
                $cast = $this->fieldComponent($field)['cast'];

                return $cast ? [$field['name'] => $cast] : [];
            })
            ->all();
    }

    private function fieldComponent(array $field): array
    {
        return match ($field['type']) {
            'bigInteger' => ['control' => 'input', 'input' => 'number', 'migration' => 'bigInteger', 'validation' => 'integer', 'cast' => 'integer', 'step' => 1, 'full' => false],
            'boolean' => ['control' => 'switch', 'input' => 'checkbox', 'migration' => 'boolean', 'validation' => 'boolean', 'cast' => 'boolean', 'step' => null, 'full' => false],
            'date' => ['control' => 'input', 'input' => 'date', 'migration' => 'date', 'validation' => 'date', 'cast' => 'date', 'step' => null, 'full' => false],
            'datetime' => ['control' => 'input', 'input' => 'datetime-local', 'migration' => 'dateTime', 'validation' => 'date', 'cast' => 'datetime', 'step' => null, 'full' => false],
            'decimal' => ['control' => 'input', 'input' => 'number', 'migration' => 'decimal', 'validation' => 'numeric', 'cast' => 'decimal:2', 'step' => '0.01', 'full' => false],
            'email' => ['control' => 'input', 'input' => 'email', 'migration' => 'string', 'validation' => 'email', 'cast' => null, 'step' => null, 'full' => false],
            'enum' => ['control' => 'select', 'input' => null, 'migration' => 'string', 'validation' => 'string', 'cast' => null, 'step' => null, 'full' => false],
            'file' => ['control' => 'file', 'input' => 'file', 'migration' => 'string', 'validation' => 'file', 'cast' => null, 'step' => null, 'full' => true],
            'float' => ['control' => 'input', 'input' => 'number', 'migration' => 'float', 'validation' => 'numeric', 'cast' => 'float', 'step' => 'any', 'full' => false],
            'foreignId' => ['control' => 'input', 'input' => 'number', 'migration' => 'foreignId', 'validation' => 'integer', 'cast' => 'integer', 'step' => 1, 'full' => false],
            'image' => ['control' => 'file', 'input' => 'file', 'migration' => 'string', 'validation' => 'image', 'cast' => null, 'step' => null, 'full' => true],
            'integer' => ['control' => 'input', 'input' => 'number', 'migration' => 'integer', 'validation' => 'integer', 'cast' => 'integer', 'step' => 1, 'full' => false],
            'json' => ['control' => 'textarea', 'input' => null, 'migration' => 'json', 'validation' => 'json', 'cast' => 'array', 'step' => null, 'full' => true],
            'password' => ['control' => 'input', 'input' => 'password', 'migration' => 'string', 'validation' => 'string|min:8', 'cast' => 'hashed', 'step' => null, 'full' => false],
            'phone' => ['control' => 'input', 'input' => 'tel', 'migration' => 'string', 'validation' => 'string', 'cast' => null, 'step' => null, 'full' => false],
            'text' => ['control' => 'textarea', 'input' => null, 'migration' => 'text', 'validation' => 'string', 'cast' => null, 'step' => null, 'full' => true],
            'time' => ['control' => 'input', 'input' => 'time', 'migration' => 'time', 'validation' => 'date_format:H:i', 'cast' => null, 'step' => null, 'full' => false],
            'timestamp' => ['control' => 'input', 'input' => 'datetime-local', 'migration' => 'timestamp', 'validation' => 'date', 'cast' => 'datetime', 'step' => null, 'full' => false],
            'url' => ['control' => 'input', 'input' => 'url', 'migration' => 'string', 'validation' => 'url', 'cast' => null, 'step' => null, 'full' => false],
            default => ['control' => 'input', 'input' => 'text', 'migration' => 'string', 'validation' => 'string', 'cast' => null, 'step' => null, 'full' => false],
        };
    }

    private function hiddenAttributes(array $entity): array
    {
        return collect($entity['fields'])
            ->where('type', 'password')
            ->pluck('name')
            ->all();
    }

    private function displayNameFields(array $entity): array
    {
        $availableFields = collect($entity['fields'])
            ->pluck('name')
            ->all();

        return collect([$entity['display_field'] ?? null, 'name', 'title', 'email'])
            ->filter(fn (?string $field): bool => $field !== null && in_array($field, $availableFields, true))
            ->unique()
            ->values()
            ->all();
    }

    private function modelRelationMethod(array $relation): string
    {
        $target = $relation['target'];
        $method = $relation['method'];

        if ($relation['type'] === 'belongsTo') {
            return <<<PHP

    public function {$method}()
    {
        return \$this->belongsTo({$target}::class);
    }
PHP;
        }

        if ($relation['type'] === 'hasMany') {
            return <<<PHP

    public function {$method}()
    {
        return \$this->hasMany({$target}::class);
    }
PHP;
        }

        if ($relation['type'] === 'hasOne') {
            return <<<PHP

    public function {$method}()
    {
        return \$this->hasOne({$target}::class);
    }
PHP;
        }

        if ($relation['type'] === 'belongsToMany') {
            $defaultPivotTable = collect([$relation['source'], $relation['target']])
                ->map(fn (string $model) => Str::snake($model))
                ->sort()
                ->implode('_');
            $pivotArgument = ($relation['pivot_table'] ?? null) && $relation['pivot_table'] !== $defaultPivotTable
                ? ", '{$relation['pivot_table']}'"
                : '';

            return <<<PHP

    public function {$method}()
    {
        return \$this->belongsToMany({$target}::class{$pivotArgument})->withTimestamps();
    }
PHP;
        }

        return '';
    }

    private function controller(array $entity): string
    {
        $rules = $this->controllerValidationRules($entity);
        $route = $entity['route'];
        $variable = $entity['variable'];
        $collection = $entity['collection'];
        $withRelations = collect($this->eagerLoadRelations($entity))->pluck('method')->all();
        $with = $withRelations === []
            ? ''
            : "->with(['".implode("', '", $withRelations)."'])";
        $relationImports = collect($this->formRelations($entity))
            ->pluck('target')
            ->unique()
            ->reject(fn (string $target) => $target === $entity['name'])
            ->map(fn (string $target) => "use App\\Models\\{$target};")
            ->implode("\n");
        $relationImports = $relationImports ? $relationImports."\n" : '';
        $createRelationData = $this->controllerRelationData($entity);
        $createReturn = $this->controllerCreateReturn($entity);
        $editReturn = $this->controllerEditReturn($entity);
        $relationKeys = collect($this->belongsToManyRelations($entity))
            ->pluck('method')
            ->map(fn (string $method) => "'{$method}'")
            ->implode(', ');
        $relationKeys = $relationKeys === '' ? '' : $relationKeys;
        $showRelations = collect($this->showRelations($entity))->pluck('method')->all();
        $showLoad = $showRelations === []
            ? ''
            : "        \${$variable}->loadMissing(['".implode("', '", $showRelations)."']);\n\n";
        $syncRelationships = $this->syncRelationshipsMethod($entity);
        $passwordFields = collect($entity['fields'])
            ->where('type', 'password')
            ->pluck('name')
            ->map(fn (string $field) => "'{$field}'")
            ->implode(', ');
        $passwordFields = $passwordFields === '' ? '' : $passwordFields;
        $passwordUpdateRules = collect($entity['fields'])
            ->whereIn('type', ['password', 'file', 'image'])
            ->pluck('name')
            ->map(fn (string $field) => "                \$rules['{$field}'] = str_replace('required|', 'nullable|', \$rules['{$field}']);")
            ->implode("\n");
        $passwordUpdateRules = $passwordUpdateRules ? "\n{$passwordUpdateRules}" : '';
        $passwordCleanup = $passwordFields === ''
            ? ''
            : <<<PHP

        foreach ([{$passwordFields}] as \$passwordField) {
            if ((\$attributes[\$passwordField] ?? null) === null || \$attributes[\$passwordField] === '') {
                \$attributes->forget(\$passwordField);
            }
        }
PHP;
        $fileFields = collect($entity['fields'])
            ->whereIn('type', ['file', 'image'])
            ->pluck('name')
            ->map(fn (string $field) => "'{$field}'")
            ->implode(', ');
        $fileUploadHandling = $fileFields === ''
            ? ''
            : <<<PHP

        foreach ([{$fileFields}] as \$fileField) {
            if (\$request->hasFile(\$fileField)) {
                \$attributes[\$fileField] = \$request->file(\$fileField)->store(\$fileField, 'public');
            } elseif (\$ignoreId) {
                \$attributes->forget(\$fileField);
            }
        }
PHP;
        $jsonFields = collect($entity['fields'])
            ->where('type', 'json')
            ->pluck('name')
            ->map(fn (string $field) => "'{$field}'")
            ->implode(', ');
        $jsonHandling = $jsonFields === ''
            ? ''
            : <<<PHP

        foreach ([{$jsonFields}] as \$jsonField) {
            if (isset(\$attributes[\$jsonField]) && is_string(\$attributes[\$jsonField])) {
                \$attributes[\$jsonField] = json_decode(\$attributes[\$jsonField], true);
            }
        }
PHP;
        $indexMethod = $this->entityFeature($entity, 'index')
            ? <<<PHP

    public function index(): View
    {
        \${$collection} = {$entity['name']}::query(){$with}->latest()->paginate(15);

        return view('{$route}.index', compact('{$collection}'));
    }
PHP
            : '';
        $createMethod = $this->entityFeature($entity, 'create')
            ? <<<PHP

    public function create(): View
    {
{$createRelationData}
{$createReturn}
    }
PHP
            : '';
        $saveRedirect = $this->entityRedirect($entity, $variable);
        $storeMethod = $this->entityFeature($entity, 'create')
            ? <<<PHP

    public function store(Request \$request): RedirectResponse
    {
        \$validated = \$this->validatedData(\$request);
        \${$variable} = {$entity['name']}::query()->create(\$validated['attributes']);
        \$this->syncRelationships(\${$variable}, \$validated['relations']);

        return {$saveRedirect}
            ->with('success', '{$entity['name']} created successfully.');
    }
PHP
            : '';
        $showMethod = $this->entityFeature($entity, 'show')
            ? <<<PHP

    public function show({$entity['name']} \${$variable}): View
    {
{$showLoad}
        return view('{$route}.show', compact('{$variable}'));
    }
PHP
            : '';
        $editMethod = $this->entityFeature($entity, 'edit')
            ? <<<PHP

    public function edit({$entity['name']} \${$variable}): View
    {
{$createRelationData}
{$editReturn}
    }
PHP
            : '';
        $updateMethod = $this->entityFeature($entity, 'edit')
            ? <<<PHP

    public function update(Request \$request, {$entity['name']} \${$variable}): RedirectResponse
    {
        \$validated = \$this->validatedData(\$request, \${$variable}->id);
        \${$variable}->update(\$validated['attributes']);
        \$this->syncRelationships(\${$variable}, \$validated['relations']);

        return {$saveRedirect}
            ->with('success', '{$entity['name']} updated successfully.');
    }
PHP
            : '';
        $destroyRedirect = $this->entityFeature($entity, 'index')
            ? "redirect()->route('{$route}.index')"
            : "redirect()->route('dashboard')";
        $destroyMethod = $this->entityFeature($entity, 'delete')
            ? <<<PHP

    public function destroy({$entity['name']} \${$variable}): RedirectResponse
    {
        \${$variable}->delete();

        return {$destroyRedirect}
            ->with('success', '{$entity['name']} deleted successfully.');
    }
PHP
            : '';
        $validationMethods = ($this->entityFeature($entity, 'create') || $this->entityFeature($entity, 'edit'))
            ? <<<PHP

    private function validatedData(Request \$request, ?int \$ignoreId = null): array
    {
        \$rules = [
{$rules}
        ];

        if (\$ignoreId) {
            foreach (\$rules as \$field => \$rule) {
                \$rules[\$field] = str_replace(','.\$field, ','.\$field.','.\$ignoreId, \$rule);
            }
{$passwordUpdateRules}
        }

        \$validated = \$request->validate(\$rules, \$this->validationMessages());
        \$attributes = collect(\$validated)->except([{$relationKeys}]);{$passwordCleanup}{$fileUploadHandling}{$jsonHandling}

        return [
            'attributes' => \$attributes->all(),
            'relations' => collect(\$validated)->only([{$relationKeys}])->all(),
        ];
    }

    private function validationMessages(): array
    {
        return [
            'required' => 'Please fill out this field.',
            'email' => 'Please enter a valid email address.',
            'url' => 'Please enter a valid URL.',
            'integer' => 'Please enter a whole number.',
            'numeric' => 'Please enter a valid number.',
            'date' => 'Please enter a valid date.',
            'date_format' => 'Please enter a valid time.',
            'file' => 'Please choose a valid file.',
            'image' => 'Please choose a valid image.',
            'max' => 'This value is too large or too long.',
            'min' => 'This value is too small or too short.',
            'unique' => 'This value is already in use.',
            'exists' => 'Please choose a valid option.',
            'array' => 'Please choose one or more valid options.',
            'in' => 'Please choose one of the available options.',
        ];
    }

{$syncRelationships}
PHP
            : '';

        return <<<PHP
<?php

namespace App\Http\Controllers;

use App\Models\\{$entity['name']};
{$relationImports}use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class {$entity['name']}Controller extends Controller
{
{$indexMethod}{$createMethod}{$storeMethod}{$showMethod}{$editMethod}{$updateMethod}{$destroyMethod}{$validationMethods}
}

PHP;
    }

    private function controllerRelationData(array $entity): string
    {
        return collect($this->formRelations($entity))
            ->map(fn (array $relation) => "        \${$relation['target_collection']} = {$relation['target']}::query()->orderBy('id')->get();")
            ->implode("\n");
    }

    private function controllerCreateReturn(array $entity): string
    {
        $collections = collect($this->formRelations($entity))
            ->pluck('target_collection')
            ->unique()
            ->map(fn (string $collection) => "'{$collection}'")
            ->implode(', ');

        if ($collections === '') {
            return "        return view('{$entity['route']}.create');";
        }

        return "        return view('{$entity['route']}.create', compact({$collections}));";
    }

    private function controllerEditReturn(array $entity): string
    {
        $compactItems = collect([$entity['variable']])
            ->merge(collect($this->formRelations($entity))->pluck('target_collection'))
            ->unique()
            ->map(fn (string $item) => "'{$item}'")
            ->implode(', ');

        return "        return view('{$entity['route']}.edit', compact({$compactItems}));";
    }

    private function controllerValidationRules(array $entity): string
    {
        return collect($entity['fields'])
            ->map(function (array $field) use ($entity) {
                $rule = $field['required'] ? 'required' : 'nullable';
                $rule .= '|'.$this->validationRule($field);
                if ($this->isUniqueField($field)) {
                    $rule .= '|unique:'.$entity['table'].','.$field['name'];
                }

                return "            '{$field['name']}' => '{$rule}',";
            })
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => "            '{$relation['foreign_key']}' => 'nullable|integer|exists:{$relation['target_table']},id',"))
            ->merge(collect($this->belongsToManyRelations($entity))
                ->flatMap(fn (array $relation) => [
                    "            '{$relation['method']}' => 'nullable|array',",
                    "            '{$relation['method']}.*' => 'integer|exists:{$relation['target_table']},id',",
                ]))
            ->implode("\n");
    }

    private function syncRelationshipsMethod(array $entity): string
    {
        $syncLines = collect($this->belongsToManyRelations($entity))
            ->map(fn (array $relation) => "        \${$entity['variable']}->{$relation['method']}()->sync(\$relations['{$relation['method']}'] ?? []);")
            ->implode("\n");

        if ($syncLines === '') {
            $syncLines = '        // This model does not define many-to-many relationships.';
        }

        return <<<PHP
    private function syncRelationships({$entity['name']} \${$entity['variable']}, array \$relations): void
    {
{$syncLines}
    }

PHP;
    }

    private function formRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->whereIn('type', ['belongsTo', 'belongsToMany'])
            ->values()
            ->all();
    }

    private function eagerLoadRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->whereIn('type', ['belongsTo', 'belongsToMany', 'hasOne'])
            ->values()
            ->all();
    }

    private function showRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->whereIn('type', ['belongsTo', 'belongsToMany', 'hasMany', 'hasOne'])
            ->values()
            ->all();
    }

    private function migration(array $entity): string
    {
        $columns = collect($entity['fields'])
            ->map(fn (array $field) => '            '.$this->migrationColumn($field))
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => '            '.$this->relationColumn($relation)))
            ->implode("\n");

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$entity['table']}', function (Blueprint \$table) {
            \$table->id();
{$columns}
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$entity['table']}');
    }
};

PHP;
    }

    private function foreignKeysMigration(array $entity): string
    {
        $foreignKeys = collect($this->belongsToRelations($entity))
            ->map(fn (array $relation) => '            '.$this->relationForeignKey($relation))
            ->implode("\n");
        $dropForeignKeys = collect($this->belongsToRelations($entity))
            ->map(fn (array $relation) => "            \$table->dropForeign(['{$relation['foreign_key']}']);")
            ->implode("\n");

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('{$entity['table']}', function (Blueprint \$table) {
{$foreignKeys}
        });
    }

    public function down(): void
    {
        Schema::table('{$entity['table']}', function (Blueprint \$table) {
{$dropForeignKeys}
        });
    }
};

PHP;
    }

    private function pivotMigration(array $relation): string
    {
        $models = $relation['pivot_models'];
        $firstModel = $models[0];
        $secondModel = $models[1];
        $firstKey = Str::snake($firstModel).'_id';
        $secondKey = Str::snake($secondModel).'_id';
        $firstTable = Str::snake(Str::pluralStudly($firstModel));
        $secondTable = Str::snake(Str::pluralStudly($secondModel));

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$relation['pivot_table']}', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('{$firstKey}')->constrained('{$firstTable}')->cascadeOnDelete();
            \$table->foreignId('{$secondKey}')->constrained('{$secondTable}')->cascadeOnDelete();
            \$table->unique(['{$firstKey}', '{$secondKey}']);
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$relation['pivot_table']}');
    }
};

PHP;
    }

    private function writeViews(string $outputDir, array $entity): void
    {
        $base = $outputDir.'/resources/views/'.$entity['route'];

        if ($this->entityFeature($entity, 'index')) {
            $this->write($base.'/index.blade.php', $this->indexView($entity));
        }

        if ($this->entityFeature($entity, 'create')) {
            $this->write($base.'/create.blade.php', $this->formView($entity, false));
        }

        if ($this->entityFeature($entity, 'edit')) {
            $this->write($base.'/edit.blade.php', $this->formView($entity, true));
        }

        if ($this->entityFeature($entity, 'show')) {
            $this->write($base.'/show.blade.php', $this->showView($entity));
        }
    }

    private function writeUiComponents(string $outputDir): void
    {
        foreach ($this->uiComponents() as $path => $content) {
            $this->write($outputDir.'/resources/views/components/ui/'.$path, $content);
        }
    }

    private function uiComponents(): array
    {
        return [
            'page-header.blade.php' => <<<'BLADE'
@props(['eyebrow' => null, 'title', 'description' => null])
<div {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="page-title">
        @if($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1>{{ $title }}</h1>
        @if($description)
            <p class="muted">{{ $description }}</p>
        @endif
    </div>
    @if(trim($slot) !== '')
        <div class="actions-row mt-0">{{ $slot }}</div>
    @endif
</div>
BLADE,
            'card.blade.php' => <<<'BLADE'
<section {{ $attributes->merge(['class' => 'card']) }}>{{ $slot }}</section>
BLADE,
            'button.blade.php' => <<<'BLADE'
@props(['variant' => 'secondary', 'type' => 'button', 'loading' => false])
<button type="{{ $type }}" @disabled($attributes->get('disabled') || $loading) {{ $attributes->class(['button', $variant]) }}>
    {{ $slot }}
</button>
BLADE,
            'icon-button.blade.php' => <<<'BLADE'
@props(['variant' => 'secondary', 'label'])
<button type="button" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class(['icon-button', $variant]) }}>
    {{ $slot }}
</button>
BLADE,
            'input.blade.php' => <<<'BLADE'
@props(['invalid' => false])
<input {{ $attributes->class([$invalid ? 'border-[#EF4444] focus:border-[#EF4444] focus:ring-[#EF4444]' : '']) }}>
BLADE,
            'textarea.blade.php' => <<<'BLADE'
@props(['invalid' => false])
<textarea {{ $attributes->class([$invalid ? 'border-[#EF4444] focus:border-[#EF4444] focus:ring-[#EF4444]' : '']) }}>{{ $slot }}</textarea>
BLADE,
            'select.blade.php' => <<<'BLADE'
@props(['invalid' => false])
<select {{ $attributes->class([$invalid ? 'border-[#EF4444] focus:border-[#EF4444] focus:ring-[#EF4444]' : '']) }}>{{ $slot }}</select>
BLADE,
            'checkbox.blade.php' => <<<'BLADE'
<input type="checkbox" {{ $attributes }}>
BLADE,
            'switch.blade.php' => <<<'BLADE'
@props(['checked' => false, 'label' => null])
<label class="inline-flex items-center gap-2">
    <input type="checkbox" role="switch" @checked($checked) {{ $attributes }}>
    @if($label)<span>{{ $label }}</span>@endif
</label>
BLADE,
            'label.blade.php' => <<<'BLADE'
@props(['required' => false])
<label {{ $attributes->merge(['class' => '']) }}>
    {{ $slot }} @if($required)<span class="required-mark">*</span>@endif
</label>
BLADE,
            'field-error.blade.php' => <<<'BLADE'
@props(['messages'])
@if($messages)
    @foreach((array) $messages as $message)
        <p class="error">{{ $message }}</p>
    @endforeach
@endif
BLADE,
            'form-group.blade.php' => <<<'BLADE'
@props(['label' => null, 'for' => null, 'required' => false, 'help' => null, 'error' => null])
<div {{ $attributes }}>
    @if($label)
        <label for="{{ $for }}" class="mb-2 block text-sm font-medium text-[#1E293B]">{{ $label }} @if($required)<span class="required-mark">*</span>@endif</label>
    @endif
    {{ $slot }}
    @if($help)<p class="mt-1 text-sm text-[#64748B]">{{ $help }}</p>@endif
    @if($error)<p class="error mt-1">{{ $error }}</p>@endif
</div>
BLADE,
            'badge.blade.php' => <<<'BLADE'
@props(['variant' => 'muted'])
<span {{ $attributes->class(['badge', $variant]) }}>{{ $slot }}</span>
BLADE,
            'alert.blade.php' => <<<'BLADE'
@props(['variant' => 'success'])
<div role="alert" {{ $attributes->class(['status', $variant]) }}>{{ $slot }}</div>
BLADE,
            'table.blade.php' => <<<'BLADE'
<div {{ $attributes->merge(['class' => 'table-scroll']) }}>{{ $slot }}</div>
BLADE,
            'empty-state.blade.php' => <<<'BLADE'
@props(['title', 'description' => null])
<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <div class="empty-icon">{{ $icon ?? '' }}</div>
    <div>
        <h2>{{ $title }}</h2>
        @if($description)<p class="muted">{{ $description }}</p>@endif
    </div>
</div>
BLADE,
            'pagination.blade.php' => <<<'BLADE'
@props(['paginator'])
@if($paginator->hasPages())
    @php
        $start = max($paginator->currentPage() - 2, 1);
        $end = min($start + 4, $paginator->lastPage());
        $start = max($end - 4, 1);
    @endphp

    <nav {{ $attributes->merge(['class' => 'pagination']) }} aria-label="Pagination">
        <div>
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </div>

        <div class="pagination-pages">
            @if($paginator->onFirstPage())
                <span class="pagination-disabled">Previous</span>
            @else
                <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif

            @if($start > 1)
                <a class="pagination-link" href="{{ $paginator->url(1) }}">1</a>
                @if($start > 2)
                    <span class="pagination-disabled">...</span>
                @endif
            @endif

            @foreach(range($start, $end) as $page)
                @if($page == $paginator->currentPage())
                    <span class="pagination-current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="pagination-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                @endif
            @endforeach

            @if($end < $paginator->lastPage())
                @if($end < $paginator->lastPage() - 1)
                    <span class="pagination-disabled">...</span>
                @endif
                <a class="pagination-link" href="{{ $paginator->url($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a>
            @endif

            @if($paginator->hasMorePages())
                <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="pagination-disabled">Next</span>
            @endif
        </div>
    </nav>
@endif
BLADE,
            'confirm-dialog.blade.php' => <<<'BLADE'
@props([
    'action',
    'title' => 'Delete record',
    'message' => 'This action cannot be undone.',
    'confirmLabel' => 'Delete',
    'triggerLabel' => 'Delete',
    'triggerClass' => 'icon-button danger',
    'confirmClass' => 'button danger',
])

<div x-data="{ open: false }" class="inline">
    <button type="button" class="{{ $triggerClass }}" aria-label="{{ $triggerLabel }}" title="{{ $triggerLabel }}" @click="open = true">
        {{ $slot }}
    </button>

    <template x-teleport="body">
        <div class="confirm-dialog-backdrop" x-show="open" x-cloak x-transition.opacity @keydown.escape.window="open = false" role="dialog" aria-modal="true">
            <div class="confirm-dialog-panel" @click.outside="open = false">
                <h2>{{ $title }}</h2>
                <p>{{ $message }}</p>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method('DELETE')

                    <div class="confirm-dialog-actions">
                        <button type="button" class="button" @click="open = false">Cancel</button>
                        <button type="submit" class="{{ $confirmClass }}">{{ $confirmLabel }}</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
BLADE,
            'breadcrumbs.blade.php' => <<<'BLADE'
@props(['items' => []])
<nav aria-label="Breadcrumb" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-2 text-sm text-[#64748B]">
        @foreach($items as $label => $url)
            <li>
                @if($url)
                    <a class="ui-link" href="{{ $url }}">{{ $label }}</a>
                @else
                    <span>{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
BLADE,
        ];
    }

    private function indexView(array $entity): string
    {
        $plusIcon = $this->icon('plus');
        $showIcon = $this->icon('show');
        $editIcon = $this->icon('edit');
        $trashIcon = $this->icon('trash');
        $emptyIcon = $this->icon('empty');
        $createButton = $this->entityFeature($entity, 'create')
            ? "\n        <a class=\"button primary\" href=\"{{ route('{$entity['route']}.create') }}\">{$plusIcon}<span>New {$entity['name']}</span></a>"
            : '';
        $actionLinks = collect([
            $this->entityFeature($entity, 'show')
                ? "<a class=\"icon-button\" href=\"{{ route('{$entity['route']}.show', \${$entity['variable']}) }}\" aria-label=\"View {$entity['name']}\" title=\"View\">{$showIcon}</a>"
                : null,
            $this->entityFeature($entity, 'edit')
                ? "<a class=\"icon-button primary\" href=\"{{ route('{$entity['route']}.edit', \${$entity['variable']}) }}\" aria-label=\"Edit {$entity['name']}\" title=\"Edit\">{$editIcon}</a>"
                : null,
            $this->entityFeature($entity, 'delete')
                ? <<<BLADE
                        <x-ui.confirm-dialog
                            action="{{ route('{$entity['route']}.destroy', \${$entity['variable']}) }}"
                            title="Delete {$entity['name']}"
                            message="This permanently removes the record."
                            trigger-label="Delete {$entity['name']}"
                        >
                            {$trashIcon}
                        </x-ui.confirm-dialog>
BLADE
                : null,
        ])->filter()->implode("\n                        ");
        $actionsHeader = $actionLinks ? "\n                    <th class=\"actions-heading\">Actions</th>" : '';
        $actionsCell = $actionLinks
            ? <<<BLADE

                    <td class="actions">
                        <div class="action-list">
                            {$actionLinks}
                        </div>
                    </td>
BLADE
            : '';
        $headers = collect($entity['fields'])
            ->map(fn (array $field) => $this->indexHeaderCell($field))
            ->merge(collect($this->indexRelations($entity))
                ->map(fn (array $relation) => '<th>'.$relation['target'].'</th>'))
            ->implode("\n                ");
        $cells = collect($entity['fields'])
            ->map(fn (array $field) => $this->indexFieldCell($entity, $field))
            ->merge(collect($this->indexRelations($entity))
                ->map(fn (array $relation) => $this->indexRelationCell($entity, $relation)))
            ->implode("\n                ");
        $columnCount = count($entity['fields']) + count($this->indexRelations($entity)) + ($actionLinks ? 1 : 0);
        $columnCount = max(1, $columnCount);

        return <<<BLADE
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <p class="eyebrow">Records</p>
            <h1>{$entity['name']}</h1>
            <p class="muted">Manage generated {$entity['name']} records.</p>
        </div>{$createButton}
    </div>

    <div class="card table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">{$entity['name']} records</div>
                <div class="table-meta">{{ \${$entity['collection']}->total() }} total</div>
            </div>
        </div>

        <x-ui.table>
            <table>
                <thead>
                    <tr>
                        {$headers}
                        {$actionsHeader}
                    </tr>
                </thead>
                <tbody>
                    @forelse(\${$entity['collection']} as \${$entity['variable']})
                    <tr>
                        {$cells}
                        {$actionsCell}
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{$columnCount}">
                            <div class="empty-state">
                                <div class="empty-icon">{$emptyIcon}</div>
                                <div>
                                    <h2>No {$entity['name']} records yet</h2>
                                    <p class="muted">Create the first record to populate this table.</p>
                                    @if(\Illuminate\Support\Facades\Route::has('{$entity['route']}.create'))
                                        <div class="actions-row justify-center">
                                            <a class="button primary" href="{{ route('{$entity['route']}.create') }}">{$plusIcon}<span>New {$entity['name']}</span></a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.table>
    </div>

    <x-ui.pagination :paginator="\${$entity['collection']}" />
@endsection

BLADE;
    }

    private function formView(array $entity, bool $editing): string
    {
        $variable = $entity['variable'];
        $action = $editing
            ? "{{ route('{$entity['route']}.update', \${$variable}) }}"
            : "{{ route('{$entity['route']}.store') }}";
        $method = $editing ? "\n        @method('PUT')" : '';
        $backHref = '{{ '.$this->entityBackRoute($entity).' }}';
        $enctype = collect($entity['fields'])->contains(fn (array $field): bool => in_array($field['type'], ['file', 'image'], true))
            ? ' enctype="multipart/form-data"'
            : '';

        $inputs = collect($entity['fields'])
            ->map(fn (array $field) => $this->fieldInput($field, $editing, $variable))
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => $this->relationSelectInput($relation, $editing, $variable)))
            ->merge(collect($this->belongsToManyRelations($entity))
                ->map(fn (array $relation) => $this->manyRelationSelectInput($relation, $editing, $variable)))
            ->implode("\n\n");

        $title = $editing ? 'Edit '.$entity['name'] : 'Create '.$entity['name'];
        $submitLabel = $editing ? 'Save Changes' : 'Create '.$entity['name'];

        return <<<BLADE
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <p class="eyebrow">Form</p>
            <h1>{$title}</h1>
            <p class="muted">Fill out the fields below and save the record.</p>
        </div>
        <a class="button" href="{$backHref}">Back</a>
    </div>

    <form class="card form-card" method="POST" action="{$action}"{$enctype}>
        @csrf{$method}

        @if (\$errors->any())
            <div class="status danger">
                Please review the highlighted fields and try again.
            </div>
        @endif

        <div class="form-section">
            <h2 class="form-section-title">{$entity['name']} information</h2>
{$inputs}
        </div>

        <div class="form-actions">
            <a class="button" href="{$backHref}">Cancel</a>
            <button class="button primary" type="submit">{$submitLabel}</button>
        </div>
    </form>
@endsection

BLADE;
    }

    private function showView(array $entity): string
    {
        $editIcon = $this->icon('edit');
        $trashIcon = $this->icon('trash');
        $backIcon = $this->icon('arrow-left');
        $rows = collect($entity['fields'])
            ->map(fn (array $field) => $this->detailItem($field['label'], $this->fieldDisplayValue($entity, $field)))
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => $this->detailItem($relation['target'], "{{ \${$entity['variable']}->{$relation['method']}?->displayName() ?? '-' }}")))
            ->merge(collect($this->hasOneRelations($entity))
                ->map(fn (array $relation) => $this->detailItem($relation['target'], "{{ \${$entity['variable']}->{$relation['method']}?->displayName() ?? '-' }}")))
            ->implode("\n        ");
        $relatedSections = collect($this->hasManyRelations($entity))
            ->merge($this->belongsToManyRelations($entity))
            ->map(fn (array $relation) => $this->relatedRecordsSection($entity, $relation))
            ->implode("\n");
        $backHref = '{{ '.$this->entityBackRoute($entity).' }}';
        $editAction = $this->entityFeature($entity, 'edit')
            ? "<a class=\"button primary\" href=\"{{ route('{$entity['route']}.edit', \${$entity['variable']}) }}\">{$editIcon}<span>Edit</span></a>"
            : '';
        $deleteAction = $this->entityFeature($entity, 'delete')
            ? <<<BLADE
        <x-ui.confirm-dialog
            action="{{ route('{$entity['route']}.destroy', \${$entity['variable']}) }}"
            title="Delete {$entity['name']}"
            message="This permanently removes the record."
            trigger-label="Delete {$entity['name']}"
            trigger-class="button danger"
        >
            {$trashIcon}<span>Delete</span>
        </x-ui.confirm-dialog>
BLADE
            : '';
        $headerActions = trim(<<<BLADE
        <div class="top-actions">
            {$editAction}
            {$deleteAction}
        </div>
BLADE);

        return <<<BLADE
@extends('layouts.app')

@section('content')
    <header class="detail-header">
        <div class="detail-header-top">
            <div class="detail-breadcrumbs">
                <a class="back-button" href="{$backHref}" aria-label="Back">{$backIcon}</a>
                <a class="ui-link" href="{$backHref}">{$entity['name']}</a>
                <span>/</span>
                <span>{{ \${$entity['variable']}->displayName() }}</span>
            </div>
{$headerActions}
        </div>

        <div class="page-title mt-5">
            <h1>{{ \${$entity['variable']}->displayName() }}</h1>
            <p class="muted">Review the saved values and related records.</p>
        </div>
    </header>

    <section class="card record-card">
        <h2>Information</h2>
        <div class="detail-grid">
        {$rows}
        </div>
    </section>

{$relatedSections}
@endsection

BLADE;
    }

    private function detailItem(string $label, string $value): string
    {
        return <<<BLADE
            <div class="detail-item">
                <span>{$label}</span>
                <strong>{$value}</strong>
            </div>
BLADE;
    }

    private function fieldInput(array $field, bool $editing, string $variable): string
    {
        $component = $this->fieldComponent($field);
        $value = $this->fieldInputValue($field, $editing, $variable);
        $label = $this->inputLabel($field['label'], $field['required']);
        $attributes = $this->fieldInputAttributes($field, $component, $editing);
        $wrapperClass = $component['full'] ? ' class="field-full"' : '';
        $help = $this->fieldHelp($field);
        $id = 'field_'.$field['name'];
        $errorAttributes = "@error('{$field['name']}') aria-invalid=\"true\" aria-describedby=\"{$id}_error\" @enderror";
        $error = "@error('{$field['name']}') <div id=\"{$id}_error\" class=\"error\">{{ \$message }}</div> @enderror";

        if ($component['control'] === 'switch') {
            return <<<BLADE
        <div{$wrapperClass}>
            <input type="hidden" name="{$field['name']}" value="0">
            <label for="{$id}" class="inline-flex items-center gap-3">
                <input id="{$id}" type="checkbox" name="{$field['name']}" value="1" @checked((string) {$value} === '1') {$errorAttributes}>
                <span>{$label}</span>
            </label>
            {$help}
            {$error}
        </div>
BLADE;
        }

        if ($component['control'] === 'select') {
            $options = collect($field['metadata']['options'] ?? [])
                ->map(fn (string $option) => "                <option value=\"{$option}\" @selected((string) {$value} === '{$option}')>{$option}</option>")
                ->implode("\n");

            return <<<BLADE
        <div{$wrapperClass}>
            <label for="{$id}">{$label}</label>
            <select id="{$id}" name="{$field['name']}"{$attributes} {$errorAttributes}>
                <option value="">Choose {$field['label']}</option>
{$options}
            </select>
            {$help}
            {$error}
        </div>
BLADE;
        }

        if ($component['control'] === 'textarea') {
            $textareaClass = $field['type'] === 'json' ? ' class="code-input"' : '';
            return <<<BLADE
        <div{$wrapperClass}>
            <label for="{$id}">{$label}</label>
            <textarea id="{$id}" name="{$field['name']}"{$attributes}{$textareaClass} {$errorAttributes}>{{ {$value} }}</textarea>
            {$help}
            {$error}
        </div>
BLADE;
        }

        if ($component['control'] === 'file') {
            return <<<BLADE
        <div{$wrapperClass}>
            <label for="{$id}">{$label}</label>
            <input id="{$id}" type="file" name="{$field['name']}"{$attributes} {$errorAttributes}>
            {$help}
            {$error}
        </div>
BLADE;
        }

        $type = $component['input'];
        $autocomplete = $this->autocompleteAttribute($field);
        $valueAttribute = $field['type'] === 'password' && $editing
            ? ''
            : ' value="{{ '.$value.' }}"';

        return <<<BLADE
        <div{$wrapperClass}>
            <label for="{$id}">{$label}</label>
            <input id="{$id}" type="{$type}" name="{$field['name']}"{$autocomplete}{$valueAttribute}{$attributes} {$errorAttributes}>
            {$help}
            {$error}
        </div>
BLADE;
    }

    private function inputLabel(string $label, bool $required): string
    {
        return $required ? "{$label} <span class=\"required-mark\">*</span>" : $label;
    }

    private function autocompleteAttribute(array $field): string
    {
        return match ($field['type']) {
            'email' => ' autocomplete="email"',
            'password' => ' autocomplete="new-password"',
            'phone' => ' autocomplete="tel"',
            'url' => ' autocomplete="url"',
            default => in_array($field['name'], ['name', 'title'], true)
                ? ' autocomplete="name"'
                : '',
        };
    }

    private function fieldInputAttributes(array $field, array $component, bool $editing): string
    {
        $metadata = $field['metadata'] ?? [];
        $attributes = [];

        if (in_array($field['type'] ?? null, ['file', 'image'], true)) {
            if (isset($metadata['accept']) && $metadata['accept'] !== '') {
                $attributes[] = 'accept="'.e((string) $metadata['accept']).'"';
            } elseif (($field['type'] ?? null) === 'image') {
                $attributes[] = 'accept="image/*"';
            }

            return $attributes === [] ? '' : ' '.implode(' ', $attributes);
        }

        foreach (['min', 'max', 'step', 'placeholder'] as $key) {
            if (isset($metadata[$key]) && $metadata[$key] !== '') {
                $attributes[] = $key.'="'.e((string) $metadata[$key]).'"';
            }
        }

        if ($component['step'] !== null && !isset($metadata['step'])) {
            $attributes[] = 'step="'.e((string) $component['step']).'"';
        }

        if (isset($metadata['minLength'])) {
            $attributes[] = 'minlength="'.e((string) $metadata['minLength']).'"';
        }

        if (isset($metadata['maxLength'])) {
            $attributes[] = 'maxlength="'.e((string) $metadata['maxLength']).'"';
        }

        return $attributes === [] ? '' : ' '.implode(' ', $attributes);
    }

    private function fieldHelp(array $field): string
    {
        $help = $field['metadata']['help'] ?? null;

        return $help ? '<span class="muted">'.e((string) $help).'</span>' : '';
    }

    private function fieldInputValue(array $field, bool $editing, string $variable): string
    {
        $default = array_key_exists('default', $field['metadata'] ?? [])
            ? ", ".var_export($field['metadata']['default'], true)
            : '';

        if (!$editing || $field['type'] === 'password') {
            return "old('{$field['name']}'{$default})";
        }

        return match ($field['type']) {
            'boolean' => "old('{$field['name']}', \${$variable}->{$field['name']} === null ? '' : (string) (int) \${$variable}->{$field['name']})",
            'date' => "old('{$field['name']}', optional(\${$variable}->{$field['name']})->format('Y-m-d'))",
            'datetime' => "old('{$field['name']}', optional(\${$variable}->{$field['name']})->format('Y-m-d\\TH:i'))",
            'timestamp' => "old('{$field['name']}', optional(\${$variable}->{$field['name']})->format('Y-m-d\\TH:i'))",
            'json' => "old('{$field['name']}', json_encode(\${$variable}->{$field['name']}, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))",
            default => "old('{$field['name']}', \${$variable}->{$field['name']})",
        };
    }

    private function indexFieldCell(array $entity, array $field): string
    {
        $class = in_array($field['type'], ['bigInteger', 'decimal', 'integer'], true) ? ' class="numeric-cell"' : '';

        return '<td'.$class.'>'.$this->fieldDisplayValue($entity, $field).'</td>';
    }

    private function indexHeaderCell(array $field): string
    {
        $class = in_array($field['type'], ['bigInteger', 'decimal', 'integer'], true) ? ' class="numeric-cell"' : '';

        return '<th'.$class.'>'.$field['label'].'</th>';
    }

    private function fieldDisplayValue(array $entity, array $field): string
    {
        $variable = $entity['variable'];
        $name = $field['name'];

        return match ($field['type']) {
            'boolean' => "{!! \${$variable}->{$name} === null ? '<span class=\"badge muted\">Not set</span>' : (\${$variable}->{$name} ? '<span class=\"badge success\">Yes</span>' : '<span class=\"badge muted\">No</span>') !!}",
            'date' => "{{ optional(\${$variable}->{$name})->format('Y-m-d') ?? '-' }}",
            'datetime' => "{{ optional(\${$variable}->{$name})->format('Y-m-d H:i') ?? '-' }}",
            'timestamp' => "{{ optional(\${$variable}->{$name})->format('Y-m-d H:i') ?? '-' }}",
            'file' => "{!! \${$variable}->{$name} ? '<a class=\"ui-link\" href=\"'.\\Illuminate\\Support\\Facades\\Storage::url(\${$variable}->{$name}).'\" target=\"_blank\" rel=\"noreferrer\">Open file</a>' : '-' !!}",
            'image' => "{!! \${$variable}->{$name} ? '<a class=\"ui-link\" href=\"'.\\Illuminate\\Support\\Facades\\Storage::url(\${$variable}->{$name}).'\" target=\"_blank\" rel=\"noreferrer\"><img class=\"image-thumb\" src=\"'.\\Illuminate\\Support\\Facades\\Storage::url(\${$variable}->{$name}).'\" alt=\"{$field['label']}\"></a>' : '-' !!}",
            'json' => "{{ \${$variable}->{$name} ? \\Illuminate\\Support\\Str::limit(json_encode(\${$variable}->{$name}, JSON_UNESCAPED_SLASHES), 80) : '-' }}",
            'password' => "{{ \${$variable}->{$name} ? 'Set' : '-' }}",
            'text' => "{{ \${$variable}->{$name} ? \\Illuminate\\Support\\Str::limit(\${$variable}->{$name}, 90) : '-' }}",
            default => "{{ \${$variable}->{$name} }}",
        };
    }

    private function relationSelectInput(array $relation, bool $editing, string $variable): string
    {
        $value = $editing
            ? "old('{$relation['foreign_key']}', \${$variable}->{$relation['foreign_key']})"
            : "old('{$relation['foreign_key']}')";
        $id = 'field_'.$relation['foreign_key'];
        $targetRoute = Str::kebab(Str::pluralStudly($relation['target']));

        return <<<BLADE
        <div>
            <label for="{$id}">{$relation['target']}</label>
            <select id="{$id}" name="{$relation['foreign_key']}" @disabled(\${$relation['target_collection']}->isEmpty()) @error('{$relation['foreign_key']}') aria-invalid="true" aria-describedby="{$id}_error" @enderror>
                <option value="">Choose {$relation['target']}</option>
                @foreach(\${$relation['target_collection']} as \${$relation['target_variable']})
                    <option value="{{ \${$relation['target_variable']}->id }}" @selected((string) {$value} === (string) \${$relation['target_variable']}->id)>
                        {{ \${$relation['target_variable']}->displayName() }}
                    </option>
                @endforeach
            </select>
            @if(\${$relation['target_collection']}->isEmpty())
                <p class="muted">No {$relation['target']} records yet. You can save this record now and connect it later.</p>
                @if(\Illuminate\Support\Facades\Route::has('{$targetRoute}.create'))
                    <a class="ui-link" href="{{ route('{$targetRoute}.create') }}">Add {$relation['target']}</a>
                @endif
            @endif
            @error('{$relation['foreign_key']}') <div id="{$id}_error" class="error">{{ \$message }}</div> @enderror
        </div>
BLADE;
    }

    private function manyRelationSelectInput(array $relation, bool $editing, string $variable): string
    {
        $selectedExpression = $editing
            ? "collect(old('{$relation['method']}', \${$variable}->{$relation['method']}->pluck('id')->all()))"
            : "collect(old('{$relation['method']}', []))";

        $id = 'field_'.$relation['method'];
        $targetRoute = Str::kebab(Str::pluralStudly($relation['target']));

        return <<<BLADE
        @php(\$selected{$relation['target_collection']} = {$selectedExpression}->map(fn (\$id) => (string) \$id))
        <fieldset class="field-full">
            <legend>{$relation['target']}</legend>
            @if(\${$relation['target_collection']}->isEmpty())
                <div class="relation-empty">
                    <p class="muted">No {$relation['target']} records yet. Save this record now and attach them later.</p>
                    @if(\Illuminate\Support\Facades\Route::has('{$targetRoute}.create'))
                        <a class="ui-link" href="{{ route('{$targetRoute}.create') }}">Add {$relation['target']}</a>
                    @endif
                </div>
            @elseif(\${$relation['target_collection']}->count() <= 12)
                <div class="checkbox-list" @error('{$relation['method']}') aria-invalid="true" aria-describedby="{$id}_error" @enderror>
                    @foreach(\${$relation['target_collection']} as \${$relation['target_variable']})
                        <label for="{$id}_{{ \${$relation['target_variable']}->id }}" class="checkbox-card">
                            <input id="{$id}_{{ \${$relation['target_variable']}->id }}" type="checkbox" name="{$relation['method']}[]" value="{{ \${$relation['target_variable']}->id }}" @checked(\$selected{$relation['target_collection']}->contains((string) \${$relation['target_variable']}->id))>
                            <span>{{ \${$relation['target_variable']}->displayName() }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <select id="{$id}" name="{$relation['method']}[]" multiple @error('{$relation['method']}') aria-invalid="true" aria-describedby="{$id}_error" @enderror>
                    @foreach(\${$relation['target_collection']} as \${$relation['target_variable']})
                        <option value="{{ \${$relation['target_variable']}->id }}" @selected(\$selected{$relation['target_collection']}->contains((string) \${$relation['target_variable']}->id))>
                            {{ \${$relation['target_variable']}->displayName() }}
                        </option>
                    @endforeach
                </select>
                <p class="muted">Hold Ctrl or Command to select multiple records.</p>
            @endif
            @error('{$relation['method']}') <div id="{$id}_error" class="error">{{ \$message }}</div> @enderror
            @error('{$relation['method']}.*') <div class="error">{{ \$message }}</div> @enderror
        </fieldset>
BLADE;
    }

    private function indexRelationCell(array $entity, array $relation): string
    {
        if (in_array($relation['type'], ['belongsTo', 'hasOne'], true)) {
            return '<td>{{ $'.$entity['variable'].'->'.$relation['method'].'?->displayName() ?? \'-\' }}</td>';
        }

        if ($relation['type'] === 'belongsToMany') {
            return '<td>{{ $'.$entity['variable'].'->'.$relation['method'].'->map->displayName()->join(\', \') ?: \'-\' }}</td>';
        }

        if ($relation['type'] === 'hasMany') {
            return '<td>{{ $'.$entity['variable'].'->'.$relation['method'].'->count() }}</td>';
        }

        return '<td>-</td>';
    }

    private function relatedRecordsSection(array $entity, array $relation): string
    {
        $targetRoute = Str::kebab(Str::pluralStudly($relation['target']));
        $emptyIcon = $this->icon('empty');
        $plusIcon = $this->icon('plus');
        $showIcon = $this->icon('show');

        return <<<BLADE
    <section class="card related-card">
        <div class="table-toolbar">
            <div>
                <h2>{$relation['target']}</h2>
                <p class="muted">{{ \${$entity['variable']}->{$relation['method']}->count() }} related records</p>
            </div>
            @if(\Illuminate\Support\Facades\Route::has('{$targetRoute}.create'))
                <a class="button primary" href="{{ route('{$targetRoute}.create') }}">{$plusIcon}<span>Add {$relation['target']}</span></a>
            @endif
        </div>

        <x-ui.table>
            <table>
                <thead>
                    <tr>
                        <th>{$relation['target']}</th>
                        <th>Created</th>
                        <th class="actions-heading">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(\${$entity['variable']}->{$relation['method']} as \${$relation['target_variable']})
                        <tr>
                            <td>{{ \${$relation['target_variable']}->displayName() }}</td>
                            <td>{{ optional(\${$relation['target_variable']}->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="actions">
                                <div class="action-list">
                                    @if(\Illuminate\Support\Facades\Route::has('{$targetRoute}.show'))
                                        <a class="icon-button" href="{{ route('{$targetRoute}.show', \${$relation['target_variable']}) }}" aria-label="View {$relation['target']}" title="View">{$showIcon}</a>
                                    @else
                                        <span class="badge muted">No action</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="empty-state">
                                    <div class="empty-icon">{$emptyIcon}</div>
                                    <div>
                                        <h2>No related {$relation['target']} records</h2>
                                        <p class="muted">Related records will appear here after they are added.</p>
                                        @if(\Illuminate\Support\Facades\Route::has('{$targetRoute}.create'))
                                            <div class="actions-row justify-center">
                                                <a class="button primary" href="{{ route('{$targetRoute}.create') }}">{$plusIcon}<span>Add {$relation['target']}</span></a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.table>
    </section>

BLADE;
    }

    private function icon(string $name): string
    {
        return match ($name) {
            'arrow-left' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>',
            'plus' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
            'show' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
            'edit' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>',
            'trash' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>',
            'table' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 4v16"/></svg>',
            'dashboard' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="8" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="15" width="7" height="6" rx="2"/></svg>',
            'menu' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
            'x' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>',
            'logout' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M21 3v18"/></svg>',
            'empty' => '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>',
            default => '',
        };
    }

    private function layout(string $appName, array $entities): string
    {
        $menuIcon = $this->icon('menu');
        $closeIcon = $this->icon('x');
        $dashboardIcon = $this->icon('dashboard');
        $logoutIcon = $this->icon('logout');
        $nav = collect($entities)
            ->map(function (array $entity): ?string {
                $route = $this->entityPrimaryRouteName($entity);
                $icon = $this->icon('table');

                return $route ? "<a @class(['nav-link', 'active' => request()->routeIs('{$entity['route']}.*')]) href=\"{{ route('{$route}') }}\">{$icon}<span>{$entity['name']}</span></a>" : null;
            })
            ->filter()
            ->implode("\n            ");

        return <<<BLADE
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{$appName}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased" x-data="{ sidebarOpen: false }">
    <div class="app-shell">
        <div class="mobile-topbar">
            <a class="brand-link" href="{{ route('dashboard') }}">
                <img class="brand-logo" src="{{ asset('logo.svg') }}" alt="{$appName}">
                <span>{$appName}</span>
            </a>
            <button class="icon-button" type="button" aria-label="Open navigation" @click="sidebarOpen = true">
                {$menuIcon}
            </button>
        </div>

        <div class="sidebar-backdrop" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>

        <aside class="sidebar" :class="{ 'is-closed': ! sidebarOpen }" aria-label="Primary navigation">
            <div class="sidebar-header">
                <a class="brand-link" href="{{ route('dashboard') }}">
                    <img class="brand-logo" src="{{ asset('logo.svg') }}" alt="{$appName}">
                    <span>{$appName}</span>
                </a>
                <button class="icon-button lg:hidden" type="button" aria-label="Close navigation" @click="sidebarOpen = false">
                    {$closeIcon}
                </button>
            </div>

            @auth
                <nav class="sidebar-nav">
                    <a @class(['nav-link', 'active' => request()->routeIs('dashboard')]) href="{{ route('dashboard') }}">
                        {$dashboardIcon}
                        <span>Dashboard</span>
                    </a>
                    {$nav}
                </nav>

                <div class="sidebar-footer">
                    <div class="user-chip">
                        <span class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="min-w-0 flex-1 truncate">{{ auth()->user()->name }}</span>
                    </div>
                    <form class="mt-3" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="button secondary w-full" type="submit">
                            {$logoutIcon}
                            <span>Log out</span>
                        </button>
                    </form>
                </div>
            @else
                <div class="sidebar-nav">
                    <a class="button secondary" href="{{ route('login') }}">Log in</a>
                    <a class="button primary" href="{{ route('register') }}">Register</a>
                </div>
            @endauth
        </aside>

        <div class="content-shell">
            <main class="page-main">
                <div class="flash-region">
                    @foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'status' => 'info'] as \$flashKey => \$flashVariant)
                        @if(session(\$flashKey))
                            <div class="status {{ \$flashVariant }} flash-alert" role="alert" x-data="{ visible: true }" x-show="visible">
                                <span>{{ session(\$flashKey) }}</span>
                                <button class="flash-dismiss" type="button" aria-label="Dismiss alert" @click="visible = false">{$closeIcon}</button>
                            </div>
                        @endif
                    @endforeach
                </div>

            @hasSection('content')
                @yield('content')
            @else
                {{ \$slot ?? '' }}
            @endif
            </main>
        </div>
    </div>
</body>
</html>

BLADE;
    }

    private function authLayout(string $appName): string
    {
        return <<<BLADE
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{$appName}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body font-sans antialiased">
    <main class="auth-card">
        <a class="brand-link auth-brand" href="{{ url('/') }}">
            <img class="brand-logo" src="{{ asset('logo.svg') }}" alt="{$appName}">
            <span>{$appName}</span>
        </a>
        @yield('content')
    </main>
</body>
</html>

BLADE;
    }

    private function dashboardView(string $appName, array $entities): string
    {
        $resourceList = collect($entities)
            ->map(fn (array $entity): string => "                <li>{$entity['name']}</li>")
            ->implode("\n");
        $resourceList = $resourceList ?: '                <li>No resources enabled</li>';
        $links = collect($entities)
            ->map(function (array $entity): ?string {
                $route = $this->entityPrimaryRouteName($entity);

                return $route ? "                <a class=\"ui-link\" href=\"{{ route('{$route}') }}\">{$entity['name']}</a>" : null;
            })
            ->filter()
            ->implode("\n");
        $links = $links ?: '                <span class="muted">No generated screens are enabled yet.</span>';

        return <<<BLADE
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>{$appName}</h1>
            <p class="muted">A generated Laravel admin application for managing your data.</p>
        </div>
    </div>

    <section class="card summary-card">
        <h2>What this app includes</h2>
        <p class="muted">This generated application includes authentication, database migrations, Eloquent models, validation, Blade CRUD screens, relationship handling, flash messages, and empty states.</p>

        <div class="summary-grid">
            <div>
                <h3>Resources</h3>
                <ul>
{$resourceList}
                </ul>
            </div>
            <div>
                <h3>Available screens</h3>
                <div class="resource-links">
{$links}
                </div>
            </div>
        </div>
    </section>
@endsection

BLADE;
    }

    private function loginView(): string
    {
        return <<<'BLADE'
@extends('layouts.auth')

@section('content')
    <h1>Log in</h1>
    <p>Access the generated Laravel application.</p>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label>
            <span>Email</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </label>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label>
            <span>Password</span>
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label>
            <span>
                <input type="checkbox" name="remember">
                Remember me
            </span>
        </label>

        <div class="row">
            <a href="{{ route('register') }}">Create an account</a>
            <button class="button" type="submit">Log in</button>
        </div>
    </form>
@endsection

BLADE;
    }

    private function registerView(): string
    {
        return <<<'BLADE'
@extends('layouts.auth')

@section('content')
    <h1>Register</h1>
    <p>Create the first user for this generated application.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label>
            <span>Name</span>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </label>
        @error('name') <div class="error">{{ $message }}</div> @enderror

        <label>
            <span>Email</span>
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        </label>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label>
            <span>Password</span>
            <input type="password" name="password" required autocomplete="new-password">
        </label>
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label>
            <span>Confirm password</span>
            <input type="password" name="password_confirmation" required autocomplete="new-password">
        </label>

        <div class="row">
            <a href="{{ route('login') }}">Already registered?</a>
            <button class="button" type="submit">Register</button>
        </div>
    </form>
@endsection

BLADE;
    }

    private function authenticatedSessionController(): string
    {
        return <<<'PHP'
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

PHP;
    }

    private function registeredUserController(): string
    {
        return <<<'PHP'
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}

PHP;
    }

    private function loginRequest(): string
    {
        return <<<'PHP'
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}

PHP;
    }

    private function testCase(): string
    {
        return <<<'PHP'
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}

PHP;
    }

    private function featureExampleTest(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_redirects_to_dashboard(): void
    {
        $this->get('/')
            ->assertRedirect(route('dashboard'));
    }
}

PHP;
    }

    private function unitExampleTest(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}

PHP;
    }

    private function migrationFileName(int $index, array $entity): string
    {
        return now()->addSeconds($index)->format('Y_m_d_His').'_create_'.$entity['table'].'_table';
    }

    private function pivotMigrationFileName(int $index, array $relation): string
    {
        return now()->addSeconds($index)->format('Y_m_d_His').'_create_'.$relation['pivot_table'].'_table';
    }

    private function foreignKeysMigrationFileName(int $index, array $entity): string
    {
        return now()->addSeconds($index)->format('Y_m_d_His').'_add_foreign_keys_to_'.$entity['table'].'_table';
    }

    private function migrationColumn(array $field): string
    {
        $component = $this->fieldComponent($field);
        $name = $field['name'];
        $column = match ($component['migration']) {
            'bigInteger' => "\$table->bigInteger('{$name}')",
            'boolean' => "\$table->boolean('{$name}')",
            'date' => "\$table->date('{$name}')",
            'dateTime' => "\$table->dateTime('{$name}')",
            'decimal' => "\$table->decimal('{$name}', 10, 2)",
            'float' => "\$table->float('{$name}')",
            'foreignId' => "\$table->foreignId('{$name}')",
            'integer' => "\$table->integer('{$name}')",
            'json' => "\$table->json('{$name}')",
            'text' => "\$table->text('{$name}')",
            'time' => "\$table->time('{$name}')",
            'timestamp' => "\$table->timestamp('{$name}')",
            default => "\$table->string('{$name}', ".$this->stringLength($field).')',
        };

        if (!$field['required']) {
            $column .= '->nullable()';
        }

        if ($this->isUniqueField($field)) {
            $column .= '->unique()';
        }

        return $column.';';
    }

    private function isUniqueField(array $field): bool
    {
        return (bool) ($field['unique'] ?? false)
            && (bool) ($field['required'] ?? false)
            && in_array($field['type'] ?? 'string', [
                'bigInteger',
                'date',
                'datetime',
                'decimal',
                'email',
                'enum',
                'float',
                'integer',
                'phone',
                'string',
                'time',
                'timestamp',
                'url',
            ], true);
    }

    private function relationColumn(array $relation): string
    {
        return "\$table->foreignId('{$relation['foreign_key']}')->nullable();";
    }

    private function relationForeignKey(array $relation): string
    {
        return "\$table->foreign('{$relation['foreign_key']}')->references('id')->on('{$relation['target_table']}')->cascadeOnDelete();";
    }

    private function validationRule(array $field): string
    {
        $rules = explode('|', $this->fieldComponent($field)['validation']);
        $metadata = $field['metadata'] ?? [];

        if (isset($metadata['min'])) {
            $rules[] = 'min:'.$metadata['min'];
        }

        if (isset($metadata['max'])) {
            $rules[] = 'max:'.$metadata['max'];
        }

        if (isset($metadata['minLength'])) {
            $rules[] = 'min:'.$metadata['minLength'];
        }

        if (isset($metadata['maxLength'])) {
            $rules[] = 'max:'.$metadata['maxLength'];
        }

        if (($field['type'] ?? null) === 'enum' && !empty($metadata['options'])) {
            $rules[] = 'in:'.implode(',', $metadata['options']);
        }

        if (in_array($field['type'] ?? null, ['file', 'image'], true) && !empty($metadata['accept'])) {
            $acceptedTypes = collect(explode(',', (string) $metadata['accept']))
                ->map(fn (string $type): string => trim($type))
                ->filter();
            $extensions = $acceptedTypes
                ->filter(fn (string $type): bool => str_starts_with($type, '.'))
                ->map(fn (string $type): string => ltrim($type, '.'))
                ->values()
                ->all();
            $mimeTypes = $acceptedTypes
                ->filter(fn (string $type): bool => str_contains($type, '/') && !str_contains($type, '*'))
                ->values()
                ->all();

            if ($extensions !== []) {
                $rules[] = 'mimes:'.implode(',', $extensions);
            }

            if ($mimeTypes !== []) {
                $rules[] = 'mimetypes:'.implode(',', $mimeTypes);
            }
        }

        return implode('|', array_values(array_unique($rules)));
    }

    private function stringLength(array $field): int
    {
        $length = (int) ($field['metadata']['maxLength'] ?? 255);

        return max(1, min(255, $length));
    }

    private function belongsToRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->where('type', 'belongsTo')
            ->values()
            ->all();
    }

    private function hasManyRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->where('type', 'hasMany')
            ->values()
            ->all();
    }

    private function hasOneRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->where('type', 'hasOne')
            ->values()
            ->all();
    }

    private function belongsToManyRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->where('type', 'belongsToMany')
            ->values()
            ->all();
    }

    private function indexRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->whereIn('type', ['belongsTo', 'belongsToMany', 'hasMany', 'hasOne'])
            ->values()
            ->all();
    }

    private function belongsToManyPivotRelations(array $entities): array
    {
        return collect($entities)
            ->flatMap(fn (array $entity) => $this->belongsToManyRelations($entity))
            ->unique('pivot_table')
            ->values()
            ->all();
    }

    private function sortEntitiesForMigrations(array $entities): array
    {
        $byName = collect($entities)->keyBy('name');
        $sorted = [];
        $visited = [];
        $visiting = [];

        $visit = function (array $entity) use (&$visit, &$sorted, &$visited, &$visiting, $byName): void {
            $name = $entity['name'];
            if (isset($visited[$name])) {
                return;
            }

            if (isset($visiting[$name])) {
                return;
            }

            $visiting[$name] = true;

            foreach ($this->belongsToRelations($entity) as $relation) {
                $target = $byName->get($relation['target']);
                if ($target) {
                    $visit($target);
                }
            }

            $visited[$name] = true;
            unset($visiting[$name]);
            $sorted[$name] = $entity;
        };

        foreach ($entities as $entity) {
            $visit($entity);
        }

        return array_values($sorted);
    }
}
