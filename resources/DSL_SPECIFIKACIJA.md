# Specifikacija domenski specificnog jezika za generisanje Laravel aplikacija

## 1. Svrha jezika

Domenski specificni jezik definisan u ovom prototipu koristi se za opis osnovne strukture Laravel aplikacije na visem nivou apstrakcije. Korisnik ne pise direktno Laravel modele, kontrolere, migracije, rute i prikaze, vec kroz deklarativnu specifikaciju opisuje aplikaciju, entitete i njihova polja.

Generator na osnovu takve specifikacije proizvodi osnovnu Laravel aplikacijsku strukturu. Time se demonstrira osnovna hipoteza master rada: da je moguce implementirati DSL i generator koda koji automatski generise Laravel komponente iz formalno definisane specifikacije domena.

## 2. Opsti oblik specifikacije

DSL specifikacija se sastoji od jednog `app` bloka. Unutar njega definise se jedan ili vise `entity` blokova.

```text
app NazivAplikacije {
  entity NazivEntiteta {
    nazivPolja: tipPolja modifikatori
  }
}
```

Pravila imenovanja:

- naziv aplikacije pocinje velikim slovom i koristi PascalCase oblik
- naziv entiteta pocinje velikim slovom i koristi PascalCase oblik
- naziv polja pocinje malim slovom i koristi camelCase oblik
- svako polje se pise u zasebnoj liniji

Specifikacija nastaje kroz interaktivni web interfejs. Korisnik unosi naziv aplikacije, entitete, polja i relacije, dok aplikacija automatski formira DSL tekst koji se salje parseru i generatoru. DSL se ne prikazuje krajnjem korisniku u primarnom UI toku, vec se cuva kao formalni ulaz generatora i upisuje u logove projekta.

## 3. Primjer specifikacije

```text
app InventorySystem {
  entity Product {
    name: string required
    sku: string required unique
    description: text nullable
    price: decimal required
    active: boolean
    belongsTo Category
  }

  entity Category {
    title: string required unique
    description: text nullable
    hasMany Product
  }
}
```

Iz ove specifikacije generator formira Laravel strukture za entitete `Product` i `Category`.

## 4. Gramatika jezika

Pojednostavljena gramatika jezika moze se opisati na sljedeci nacin:

```text
Specification = AppDeclaration

AppDeclaration = "app" AppName "{" EntityDeclaration+ "}"

EntityDeclaration = "entity" EntityName "{" EntityMember+ "}"

EntityMember = FieldDeclaration | RelationDeclaration

FieldDeclaration = FieldName ":" FieldType FieldModifier*

RelationDeclaration = RelationType EntityName

AppName = PascalCaseIdentifier
EntityName = PascalCaseIdentifier
FieldName = camelCaseIdentifier

FieldType =
    "string"
  | "text"
  | "integer"
  | "bigInteger"
  | "decimal"
  | "boolean"
  | "date"
  | "datetime"
  | "email"
  | "password"

FieldModifier =
    "required"
  | "nullable"
  | "unique"

RelationType =
    "belongsTo"
  | "hasMany"
```

## 5. Podrzani tipovi podataka

| DSL tip | Laravel migracija | Laravel validacija |
| --- | --- | --- |
| `string` | `string` | `string` |
| `text` | `text` | `string` |
| `integer` | `integer` | `integer` |
| `bigInteger` | `bigInteger` | `integer` |
| `decimal` | `decimal(10, 2)` | `numeric` |
| `boolean` | `boolean` | `boolean` |
| `date` | `date` | `date` |
| `datetime` | `dateTime` | `date` |
| `email` | `string` | `email` |
| `password` | `string` | `string|min:8` |

## 6. Modifikatori polja

Podrzani su sljedeci modifikatori:

- `required`: polje je obavezno u validaciji
- `nullable`: polje nije obavezno i migracija dobija `nullable`
- `unique`: polje mora biti jedinstveno u bazi

Ako polje nema `required`, generator ga tretira kao opcionalno.

## 7. Izlaz generatora

Za svaki definisani entitet generator pravi:

- Eloquent model
- resource kontroler
- migraciju baze podataka
- resource rutu
- Blade prikaze za CRUD operacije
- Eloquent relacije i foreign key kolone za `belongsTo` odnose
- select polja u formama za izbor povezanog entiteta

Generisani fajlovi se pakuju u ZIP arhivu koju korisnik moze preuzeti nakon zavrsetka queue job-a.

## 8. Relacije između entiteta

Podrzane su dvije osnovne relacije:

- `belongsTo TargetEntity`: trenutni entitet dobija foreign key kolonu prema ciljnom entitetu, Eloquent `belongsTo` metodu i select polje u create/edit prikazima.
- `hasMany TargetEntity`: trenutni entitet dobija Eloquent `hasMany` metodu i prikaz povezanih zapisa na detail stranici.

Primjer:

```text
entity Product {
  name: string required
  belongsTo Category
}

entity Category {
  title: string required
  hasMany Product
}
```

Iz ovoga generator kreira `category_id` kolonu u `products` tabeli, `category()` metodu u `Product` modelu i `products()` metodu u `Category` modelu.

## 9. Validacija DSL-a

Parser provjerava:

- da specifikacija pocinje `app` blokom
- da postoji najmanje jedan entitet
- da svaki entitet ima najmanje jedno polje
- da nazivi aplikacije i entiteta pocinju velikim slovom
- da nazivi polja pocinju malim slovom
- da su tipovi polja iz skupa podrzanih tipova
- da su modifikatori iz skupa podrzanih modifikatora
- da nema duplih entiteta i duplih polja u istom entitetu
- da relacije pokazuju na postojece entitete
- da relacija ne pokazuje na isti entitet

## 10. Trenutna ogranicenja

Trenutna verzija DSL-a podrzava entitete, jednostavna polja i osnovne relacije `belongsTo` i `hasMany`. Ne pokriva kompleksne odnose kao sto su `belongsToMany`, polimorfne relacije, napredna poslovna pravila, autorizacija i generisanje testova.

## 11. Veza sa master radom

Ova specifikacija predstavlja osnovu za poglavlje o dizajnu DSL-a. Ona opisuje konkretnu sintaksu, semantiku, validaciju i mapiranje DSL elemenata na Laravel komponente, sto je direktno povezano sa ciljevima rada:

- definisanje formalne specifikacije DSL-a
- implementacija generatora koda
- generisanje Laravel modela, kontrolera, ruta i prikaza
- evaluacija upotrebljivosti i efikasnosti DSL pristupa
