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

    public function test_it_adds_inverse_belongs_to_for_has_many_relations(): void
    {
        $specification = (new DslParser())->parse(<<<'DSL'
app BlogPlatform {
  entity User {
    name: string required
    hasMany Post
  }

  entity Post {
    title: string required
  }
}
DSL);

        $post = collect($specification['entities'])->firstWhere('name', 'Post');

        $this->assertSame('belongsTo', $post['relations'][0]['type']);
        $this->assertSame('User', $post['relations'][0]['target']);
        $this->assertSame('user_id', $post['relations'][0]['foreign_key']);
        $this->assertTrue($post['relations'][0]['inferred']);
    }

    public function test_it_adds_inverse_has_many_for_belongs_to_relations(): void
    {
        $specification = (new DslParser())->parse(<<<'DSL'
app InventorySystem {
  entity Category {
    title: string required
  }

  entity Product {
    name: string required
    belongsTo Category
  }
}
DSL);

        $category = collect($specification['entities'])->firstWhere('name', 'Category');

        $this->assertSame('hasMany', $category['relations'][0]['type']);
        $this->assertSame('Product', $category['relations'][0]['target']);
        $this->assertSame('products', $category['relations'][0]['method']);
        $this->assertTrue($category['relations'][0]['inferred']);
    }

    public function test_it_adds_inverse_belongs_to_for_has_one_relations(): void
    {
        $specification = (new DslParser())->parse(<<<'DSL'
app AccountSystem {
  entity User {
    name: string required
    hasOne Profile
  }

  entity Profile {
    bio: text nullable
  }
}
DSL);

        $profile = collect($specification['entities'])->firstWhere('name', 'Profile');

        $this->assertSame('belongsTo', $profile['relations'][0]['type']);
        $this->assertSame('User', $profile['relations'][0]['target']);
        $this->assertSame('user_id', $profile['relations'][0]['foreign_key']);
    }

    public function test_it_adds_inverse_belongs_to_many_relations(): void
    {
        $specification = (new DslParser())->parse(<<<'DSL'
app BlogPlatform {
  entity Post {
    title: string required
    belongsToMany Tag
  }

  entity Tag {
    name: string required
  }
}
DSL);

        $post = collect($specification['entities'])->firstWhere('name', 'Post');
        $tag = collect($specification['entities'])->firstWhere('name', 'Tag');

        $this->assertSame('belongsToMany', $post['relations'][0]['type']);
        $this->assertSame('post_tag', $post['relations'][0]['pivot_table']);
        $this->assertSame('belongsToMany', $tag['relations'][0]['type']);
        $this->assertSame('Post', $tag['relations'][0]['target']);
        $this->assertSame('post_tag', $tag['relations'][0]['pivot_table']);
        $this->assertTrue($tag['relations'][0]['inferred']);
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

    public function test_it_rejects_conflicting_required_and_nullable_modifiers(): void
    {
        $this->expectException(DslParseException::class);

        (new DslParser())->parse(<<<'DSL'
app InventorySystem {
  entity Product {
    name: string required nullable
  }
}
DSL);
    }
}
