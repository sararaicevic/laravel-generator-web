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
