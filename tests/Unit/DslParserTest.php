<?php

namespace Tests\Unit;

use App\Services\Dsl\DslParseException;
use App\Services\Dsl\DslParser;
use PHPUnit\Framework\TestCase;

class DslParserTest extends TestCase
{
    public function test_it_parses_app_entities_and_fields(): void
    {
        $specification = (new DslParser())->parse(<<<'DSL'
app InventorySystem {
  entity Product {
    name: string required
    sku: string required unique
    price: decimal required
  }
}
DSL);

        $this->assertSame('InventorySystem', $specification['app']);
        $this->assertSame('Product', $specification['entities'][0]['name']);
        $this->assertSame('products', $specification['entities'][0]['table']);
        $this->assertSame('sku', $specification['entities'][0]['fields'][1]['name']);
        $this->assertTrue($specification['entities'][0]['fields'][1]['unique']);
    }

    public function test_it_parses_entity_relations(): void
    {
        $specification = (new DslParser())->parse(<<<'DSL'
app InventorySystem {
  entity Category {
    title: string required
    hasMany Product
  }

  entity Product {
    name: string required
    belongsTo Category
  }
}
DSL);

        $category = $specification['entities'][0];
        $product = $specification['entities'][1];

        $this->assertSame('hasMany', $category['relations'][0]['type']);
        $this->assertSame('Product', $category['relations'][0]['target']);
        $this->assertSame('products', $category['relations'][0]['method']);

        $this->assertSame('belongsTo', $product['relations'][0]['type']);
        $this->assertSame('Category', $product['relations'][0]['target']);
        $this->assertSame('category_id', $product['relations'][0]['foreign_key']);
    }

    public function test_it_rejects_relations_to_unknown_entities(): void
    {
        $this->expectException(DslParseException::class);

        (new DslParser())->parse(<<<'DSL'
app InventorySystem {
  entity Product {
    name: string required
    belongsTo Category
  }
}
DSL);
    }

    public function test_it_rejects_unsupported_field_types(): void
    {
        $this->expectException(DslParseException::class);

        (new DslParser())->parse(<<<'DSL'
app InventorySystem {
  entity Product {
    name: json required
  }
}
DSL);
    }
}
