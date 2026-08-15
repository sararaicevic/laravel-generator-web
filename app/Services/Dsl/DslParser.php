<?php

namespace App\Services\Dsl;

use Illuminate\Support\Str;

class DslParser
{
    private const RELATION_TYPES = [
        'belongsTo',
        'belongsToMany',
        'hasMany',
        'hasOne',
    ];

    private const TYPES = [
        'bigInteger',
        'boolean',
        'date',
        'datetime',
        'decimal',
        'email',
        'enum',
        'file',
        'float',
        'foreignId',
        'image',
        'integer',
        'json',
        'password',
        'phone',
        'string',
        'time',
        'timestamp',
        'text',
        'url',
    ];

    private const UNIQUE_TYPES = [
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
    ];

    private const FEATURES = [
        'index',
        'create',
        'edit',
        'show',
        'delete',
    ];

    public function parse(string $source): array
    {
        $source = $this->stripComments($source);

        if (!preg_match('/^\s*app\s+([A-Z][A-Za-z0-9_]*)\s*\{/m', $source, $appMatch, PREG_OFFSET_CAPTURE)) {
            throw new DslParseException('DSL mora početi definicijom: app NazivAplikacije { ... }');
        }

        $appName = $appMatch[1][0];
        $appOpenBrace = strpos($source, '{', $appMatch[0][1]);
        $appCloseBrace = $this->findMatchingBrace($source, $appOpenBrace);
        $body = substr($source, $appOpenBrace + 1, $appCloseBrace - $appOpenBrace - 1);

        $entities = $this->parseEntities($body);
        if ($entities === []) {
            throw new DslParseException('DSL mora sadržati najmanje jedan entity blok.');
        }

        return [
            'app' => $appName,
            'entities' => $entities,
        ];
    }

    private function parseEntities(string $body): array
    {
        $entities = [];
        $offset = 0;

        while (preg_match('/entity\s+([A-Z][A-Za-z0-9_]*)\s*\{/m', $body, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $entityName = $match[1][0];
            if (isset($entities[$entityName])) {
                throw new DslParseException("Entitet {$entityName} je definisan više puta.");
            }

            $openBrace = strpos($body, '{', $match[0][1]);
            $closeBrace = $this->findMatchingBrace($body, $openBrace);
            $entityBody = substr($body, $openBrace + 1, $closeBrace - $openBrace - 1);

            [$fields, $relations, $features, $displayField] = $this->parseEntityMembers($entityName, $entityBody);
            if ($fields === []) {
                throw new DslParseException("Entitet {$entityName} mora imati najmanje jedno polje.");
            }

            $entities[$entityName] = [
                'name' => $entityName,
                'table' => Str::snake(Str::pluralStudly($entityName)),
                'route' => Str::kebab(Str::pluralStudly($entityName)),
                'variable' => Str::camel($entityName),
                'collection' => Str::camel(Str::pluralStudly($entityName)),
                'features' => $features,
                'display_field' => $displayField,
                'fields' => $fields,
                'relations' => $relations,
            ];

            $offset = $closeBrace + 1;
        }

        return $this->validateRelationTargets($entities);
    }

    private function parseEntityMembers(string $entityName, string $entityBody): array
    {
        $fields = [];
        $relations = [];
        $displayField = null;
        $lines = preg_split('/\R/', trim($entityBody)) ?: [];

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^features\s*:\s*(.*)$/', $line, $featureMatch)) {
                $features = $this->parseFeatures($entityName, $featureMatch[1]);
                continue;
            }

            if (preg_match('/^display\s*:\s*([a-z][A-Za-z0-9_]*)$/', $line, $displayMatch)) {
                if ($displayField !== null) {
                    throw new DslParseException("Entitet {$entityName} ima vise display definicija.");
                }

                $displayField = $displayMatch[1];
                continue;
            }

            if (preg_match('/^('.implode('|', self::RELATION_TYPES).')\s+([A-Z][A-Za-z0-9_]*)(?:\s+pivot\s+([a-z][a-z0-9_]*))?$/', $line, $relationMatch)) {
                $type = $relationMatch[1];
                $target = $relationMatch[2];
                $pivotTable = $relationMatch[3] ?? null;
                $key = $type.'_'.$target;

                if ($pivotTable !== null && $type !== 'belongsToMany') {
                    throw new DslParseException("Pivot tabela može biti definisana samo za belongsToMany relaciju {$entityName}.{$type} {$target}.");
                }

                if (isset($relations[$key])) {
                    throw new DslParseException("Relacija {$entityName}.{$type} {$target} je definisana više puta.");
                }

                $relations[$key] = $this->relationSpec($entityName, $type, $target, false, $pivotTable);
                continue;
            }

            if (!preg_match('/^([a-z][A-Za-z0-9_]*)\s*:\s*([A-Za-z][A-Za-z0-9_]*)(.*)$/', $line, $match)) {
                throw new DslParseException("Neispravna definicija polja ili relacije u entitetu {$entityName}, linija ".($lineNumber + 1).'.');
            }

            $name = $match[1];
            $type = $match[2];
            $tokens = $this->fieldTokens(trim($match[3]));
            [$modifiers, $metadata] = $this->parseFieldTokens($entityName, $name, $tokens);

            if (!in_array($type, self::TYPES, true)) {
                throw new DslParseException("Tip {$type} nije podržan za polje {$entityName}.{$name}.");
            }

            $required = in_array('required', $modifiers, true);
            $nullable = in_array('nullable', $modifiers, true);

            if ($required && $nullable) {
                throw new DslParseException("Polje {$entityName}.{$name} ne može biti i required i nullable.");
            }

            $unique = in_array('unique', $modifiers, true);

            if ($unique && !$required) {
                throw new DslParseException("Polje {$entityName}.{$name} mora biti required da bi moglo biti unique.");
            }

            if ($unique && !in_array($type, self::UNIQUE_TYPES, true)) {
                throw new DslParseException("Polje {$entityName}.{$name} tipa {$type} ne može biti unique.");
            }

            if (isset($fields[$name])) {
                throw new DslParseException("Polje {$entityName}.{$name} je definisano više puta.");
            }

            $fields[$name] = [
                'name' => $name,
                'label' => Str::headline($name),
                'type' => $type,
                'required' => $required,
                'unique' => $unique,
                'metadata' => $metadata,
            ];
        }

        if ($displayField !== null && !isset($fields[$displayField])) {
            throw new DslParseException("Display polje {$entityName}.{$displayField} ne postoji.");
        }

        return [array_values($fields), array_values($relations), $features ?? $this->defaultFeatures(), $displayField];
    }

    private function parseFieldTokens(string $entityName, string $fieldName, array $tokens): array
    {
        $modifiers = [];
        $metadata = [];

        foreach ($tokens as $token) {
            if (in_array($token, ['required', 'unique', 'nullable'], true)) {
                $modifiers[] = $token;
                continue;
            }

            if (!str_contains($token, '=')) {
                throw new DslParseException("Modifikator {$token} nije podržan za polje {$entityName}.{$fieldName}.");
            }

            [$key, $value] = explode('=', $token, 2);
            $key = $this->metadataKeyAlias(trim($key));
            $value = $this->unquoteMetadataValue(trim($value));

            if (!in_array($key, $this->fieldMetadataKeys(), true)) {
                throw new DslParseException("Metadata {$key} nije podržana za polje {$entityName}.{$fieldName}.");
            }

            $metadata[$key] = $this->normalizeMetadataValue($key, $value);
        }

        return [$modifiers, $metadata];
    }

    private function fieldTokens(string $source): array
    {
        if ($source === '') {
            return [];
        }

        preg_match_all('/[^\s=]+=(?:"(?:\\\\.|[^"\\\\])*"|[^\s]+)|[^\s]+/', $source, $matches);

        return array_values(array_filter($matches[0] ?? [], fn (string $token): bool => trim($token) !== ''));
    }

    private function unquoteMetadataValue(string $value): string
    {
        if (strlen($value) >= 2 && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return stripcslashes(substr($value, 1, -1));
        }

        return $value;
    }

    private function fieldMetadataKeys(): array
    {
        return [
            'accept',
            'allowedFileTypes',
            'default',
            'help',
            'max',
            'maximum',
            'maximumLength',
            'maxLength',
            'min',
            'minimum',
            'minimumLength',
            'minLength',
            'options',
            'placeholder',
            'step',
        ];
    }

    private function metadataKeyAlias(string $key): string
    {
        return [
            'allowedFileTypes' => 'accept',
            'maximum' => 'max',
            'maximumLength' => 'maxLength',
            'minimum' => 'min',
            'minimumLength' => 'minLength',
        ][$key] ?? $key;
    }

    private function normalizeMetadataValue(string $key, string $value): mixed
    {
        if ($key === 'options') {
            return collect(explode('|', $value))
                ->map(fn (string $option): string => trim($option))
                ->filter()
                ->values()
                ->all();
        }

        if (in_array($key, ['min', 'max', 'step'], true) && is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        if (in_array($key, ['minLength', 'maxLength'], true) && ctype_digit($value)) {
            return (int) $value;
        }

        return $value;
    }

    private function parseFeatures(string $entityName, string $source): array
    {
        $values = preg_split('/\s+/', trim($source)) ?: [];
        $values = array_values(array_filter($values));

        if ($values === []) {
            throw new DslParseException("Entity {$entityName} mora imati najmanje jednu feature opciju ili izostaviti features liniju.");
        }

        if ($values === ['none']) {
            return collect(self::FEATURES)
                ->mapWithKeys(fn (string $feature) => [$feature => false])
                ->all();
        }

        foreach ($values as $value) {
            if (!in_array($value, self::FEATURES, true)) {
                throw new DslParseException("Feature {$value} nije podržan za entity {$entityName}.");
            }
        }

        return collect(self::FEATURES)
            ->mapWithKeys(fn (string $feature) => [$feature => in_array($feature, $values, true)])
            ->all();
    }

    private function defaultFeatures(): array
    {
        return collect(self::FEATURES)
            ->mapWithKeys(fn (string $feature) => [$feature => true])
            ->all();
    }

    private function relationSpec(string $source, string $type, string $target, bool $inferred = false, ?string $pivotTable = null): array
    {
        $targetVariable = Str::camel($target);
        $targetCollection = Str::camel(Str::pluralStudly($target));
        $pivotModels = collect([$source, $target])
            ->sort()
            ->values()
            ->all();

        return [
            'type' => $type,
            'source' => $source,
            'target' => $target,
            'method' => in_array($type, ['belongsTo', 'hasOne'], true) ? $targetVariable : $targetCollection,
            'foreign_key' => $type === 'belongsTo' ? Str::snake($target).'_id' : null,
            'target_table' => Str::snake(Str::pluralStudly($target)),
            'target_variable' => $targetVariable,
            'target_collection' => $targetCollection,
            'pivot_table' => $type === 'belongsToMany' ? ($pivotTable ?: $this->pivotTable($source, $target)) : null,
            'pivot_models' => $type === 'belongsToMany' ? $pivotModels : [],
            'inferred' => $inferred,
        ];
    }

    private function validateRelationTargets(array $entities): array
    {
        $names = array_keys($entities);

        foreach ($entities as $entityName => $entity) {
            foreach ($entity['relations'] as $relation) {
                if (!in_array($relation['target'], $names, true)) {
                    throw new DslParseException("Relacija {$entityName}.{$relation['type']} {$relation['target']} pokazuje na nepostojeći entitet.");
                }

                if ($relation['target'] === $entityName) {
                    throw new DslParseException("Relacija {$entityName}.{$relation['type']} {$relation['target']} ne može pokazivati na isti entitet.");
                }
            }
        }

        return $this->addInverseRelations($entities);
    }

    private function addInverseRelations(array $entities): array
    {
        foreach ($entities as $entityName => $entity) {
            foreach ($entity['relations'] as $relation) {
                $target = $relation['target'];

                if ($relation['type'] === 'hasMany' || $relation['type'] === 'hasOne') {
                    $this->addRelationIfMissing($entities, $target, 'belongsTo', $entityName);
                }

                if ($relation['type'] === 'belongsTo') {
                    $this->addRelationIfMissing($entities, $target, 'hasMany', $entityName, ['hasOne', 'hasMany']);
                }

                if ($relation['type'] === 'belongsToMany') {
                    $this->addRelationIfMissing($entities, $target, 'belongsToMany', $entityName, [], $relation['pivot_table']);
                }
            }
        }

        foreach ($entities as $entityName => $entity) {
            $entities[$entityName]['relations'] = array_values($entity['relations']);
        }

        return array_values($entities);
    }

    private function addRelationIfMissing(array &$entities, string $source, string $type, string $target, array $equivalentTypes = [], ?string $pivotTable = null): void
    {
        $equivalentTypes = $equivalentTypes === [] ? [$type] : $equivalentTypes;

        foreach ($entities[$source]['relations'] as $relation) {
            if ($relation['target'] === $target && in_array($relation['type'], $equivalentTypes, true)) {
                return;
            }
        }

        $entities[$source]['relations'][] = $this->relationSpec($source, $type, $target, true, $pivotTable);
    }

    private function pivotTable(string $source, string $target): string
    {
        return collect([$source, $target])
            ->map(fn (string $model) => Str::snake($model))
            ->sort()
            ->implode('_');
    }

    private function stripComments(string $source): string
    {
        return preg_replace('/^\s*#.*$/m', '', $source) ?? $source;
    }

    private function findMatchingBrace(string $source, int|false $openBrace): int
    {
        if ($openBrace === false) {
            throw new DslParseException('Nedostaje otvorena vitičasta zagrada.');
        }

        $depth = 0;
        $length = strlen($source);

        for ($i = $openBrace; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            }

            if ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        throw new DslParseException('Nedostaje zatvorena vitičasta zagrada.');
    }
}
