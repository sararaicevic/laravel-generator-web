# Laravel DSL Generator

Praktični prototip za master rad:

**Razvoj domenski specifičnog jezika (DSL) za generisanje Laravel aplikacija**

## Svrha

Aplikacija omogućava korisniku da kroz deklarativnu DSL specifikaciju definiše naziv aplikacije, entitete i polja. Sistem zatim parsira specifikaciju, čuva izdvojeni metamodel i generiše ZIP paket sa osnovnom Laravel aplikacijskom strukturom.

Generisani paket sadrži:

- Eloquent modele
- Resource kontrolere
- Migracije
- Web rute
- Blade CRUD prikaze

## DSL primjer

```text
app InventorySystem {
  entity Product {
    name: string required
    sku: string required unique
    description: text nullable
    price: decimal required
    active: boolean
  }

  entity Category {
    title: string required unique
    description: text nullable
  }
}
```

Svako polje se definiše u zasebnoj liniji.

Podržani tipovi:

`string`, `text`, `integer`, `bigInteger`, `decimal`, `boolean`, `date`, `datetime`, `email`, `password`

Podržani modifikatori:

`required`, `nullable`, `unique`

## Pokretanje

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

Za obradu generisanja potrebno je pokrenuti queue worker:

```bash
php artisan queue:work
```

Generator se koristi na ruti:

```text
/generator
```

## Napomena za testove

`phpunit.xml` koristi SQLite in-memory bazu za test okruženje. Lokalni PHP mora imati instaliran `pdo_sqlite` driver.
