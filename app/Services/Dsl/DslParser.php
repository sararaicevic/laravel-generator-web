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
        'integer',
        'password',
        'string',
        'text',
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

            [$fields, $relations] = $this->parseEntityMembers($entityName, $entityBody);
            if ($fields === []) {
                throw new DslParseException("Entitet {$entityName} mora imati najmanje jedno polje.");
            }

            $entities[$entityName] = [
                'name' => $entityName,
                'table' => Str::snake(Str::pluralStudly($entityName)),
                'route' => Str::kebab(Str::pluralStudly($entityName)),
                'variable' => Str::camel($entityName),
                'collection' => Str::camel(Str::pluralStudly($entityName)),
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
        $lines = preg_split('/\R/', trim($entityBody)) ?: [];

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if ($line === '') {
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
            $modifiers = preg_split('/\s+/', trim($match[3])) ?: [];
            $modifiers = array_values(array_filter($modifiers));

            if (!in_array($type, self::TYPES, true)) {
                throw new DslParseException("Tip {$type} nije podržan za polje {$entityName}.{$name}.");
            }

            foreach ($modifiers as $modifier) {
                if (!in_array($modifier, ['required', 'unique', 'nullable'], true)) {
                    throw new DslParseException("Modifikator {$modifier} nije podržan za polje {$entityName}.{$name}.");
                }
            }

            $required = in_array('required', $modifiers, true);
            $nullable = in_array('nullable', $modifiers, true);

            if ($required && $nullable) {
                throw new DslParseException("Polje {$entityName}.{$name} ne može biti i required i nullable.");
            }

            if (isset($fields[$name])) {
                throw new DslParseException("Polje {$entityName}.{$name} je definisano više puta.");
            }

            $fields[$name] = [
                'name' => $name,
                'label' => Str::headline($name),
                'type' => $type,
                'required' => $required,
                'unique' => in_array('unique', $modifiers, true),
            ];
        }

        return [array_values($fields), array_values($relations)];
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
