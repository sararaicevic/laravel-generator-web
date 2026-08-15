<?php

namespace App\Services\Dsl;

use Illuminate\Support\Str;

class DslParser
{
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

            $fields = $this->parseFields($entityName, $entityBody);
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
            ];

            $offset = $closeBrace + 1;
        }

        return array_values($entities);
    }

    private function parseFields(string $entityName, string $entityBody): array
    {
        $fields = [];
        $lines = preg_split('/\R/', trim($entityBody)) ?: [];

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (!preg_match('/^([a-z][A-Za-z0-9_]*)\s*:\s*([A-Za-z][A-Za-z0-9_]*)(.*)$/', $line, $match)) {
                throw new DslParseException("Neispravna definicija polja u entitetu {$entityName}, linija ".($lineNumber + 1).'.');
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

            if (isset($fields[$name])) {
                throw new DslParseException("Polje {$entityName}.{$name} je definisano više puta.");
            }

            $fields[$name] = [
                'name' => $name,
                'label' => Str::headline($name),
                'type' => $type,
                'required' => in_array('required', $modifiers, true),
                'unique' => in_array('unique', $modifiers, true),
            ];
        }

        return array_values($fields);
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
