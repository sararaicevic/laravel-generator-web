# 3. DIZAJN DOMENSKI SPECIFIČNOG JEZIKA

U ovom poglavlju prikazan je dizajn domenski specifičnog jezika namijenjenog generisanju Laravel aplikacija. Polazište za dizajn jezika predstavlja potreba da se osnovna struktura aplikacije opiše na višem nivou apstrakcije, bez direktnog pisanja Laravel modela, migracija, kontrolera, ruta i korisničkih prikaza. Umjesto ručne implementacije ponavljajućih komponenti, korisnik definiše domenski model aplikacije kroz deklarativnu specifikaciju, a generator na osnovu te specifikacije proizvodi odgovarajuću strukturu Laravel projekta.

Jezik je oblikovan kao eksterni domenski specifičan jezik, što znači da ima sopstvenu tekstualnu sintaksu i da se njegova specifikacija obrađuje posebnim sintaksnim analizatorom. Ovakav pristup omogućava da se opis aplikacije odvoji od ciljnog programskog jezika i razvojnog okvira. Korisnik u specifikaciji ne navodi PHP kod, SQL naredbe ili Blade šablone, već pojmove koji su bliži domenu aplikacije: aplikacija, entitet, polje, tip podatka, validaciono pravilo, funkcionalnost i relacija. Tehnički detalji se nalaze u generatoru, koji na osnovu definisanih pravila vrši transformaciju domenskog opisa u Laravel komponente.

Dizajn jezika oslanja se na principe preglednosti, jednoznačnosti i ograničene izražajnosti. Jezik ne pokušava da zamijeni PHP ili Laravel, već pokriva jasno ograničen skup funkcionalnosti koje se često ponavljaju pri izradi web aplikacija zasnovanih na radu sa podacima. Time se smanjuje složenost jezika i olakšava njegova sintaksna i semantička obrada, dok se istovremeno zadržava dovoljna izražajnost za generisanje funkcionalne početne aplikacione strukture.

## 3.1. Ciljevi dizajna jezika

Osnovni cilj dizajna jezika jeste omogućavanje deklarativnog opisa Laravel aplikacije na način koji je razumljiv, kratak i pogodan za automatsku obradu. U tom smislu, jezik treba da omogući definisanje naziva aplikacije, skupa entiteta, polja entiteta, tipova podataka, osnovnih validacionih pravila, relacija između entiteta i funkcionalnosti koje treba generisati za svaki entitet.

Prvi zahtjev odnosi se na jednostavnost upotrebe. Sintaksa jezika treba da bude dovoljno čitljiva da korisnik može brzo razumjeti strukturu specifikacije. Zbog toga se koriste blokovi sa jasno označenim ključnim riječima `app` i `entity`, dok se polja definišu u obliku `naziv: tip modifikatori`. Takav zapis je kratak, ali istovremeno sadrži sve informacije koje su potrebne za generisanje odgovarajućih aplikacionih slojeva.

Drugi zahtjev odnosi se na jednoznačnost. Svaka jezička konstrukcija mora imati jasno određeno značenje u procesu generisanja koda. Na primjer, deklaracija `email: email required unique` ne predstavlja samo tekstualni opis polja, već nosi semantičke informacije o nazivu kolone, tipu migracije, validacionom pravilu i jedinstvenom indeksu u bazi podataka. Time se izbjegava potreba da generator naknadno pretpostavlja značenje pojedinih elemenata.

Treći zahtjev odnosi se na usklađenost sa Laravel konvencijama. Laravel koristi ustaljene obrasce imenovanja tabela, modela, ruta, kontrolera i relacija. Zbog toga se u dizajnu jezika uvode pravila imenovanja koja omogućavaju automatsko izvođenje tih elemenata. Naziv entiteta se koristi kao osnova za Eloquent model i kontroler, dok se iz njega izvode naziv tabele, naziv rute, promjenljiva za pojedinačni zapis i promjenljiva za kolekciju zapisa.

Četvrti zahtjev odnosi se na proširivost. Iako početna verzija jezika obuhvata najčešće tipove podataka, osnovne relacije i standardne ekrane za upravljanje zapisima, struktura jezika je oblikovana tako da omogućava naknadno dodavanje novih tipova, modifikatora, pravila i generatora bez promjene osnovnog koncepta. Ovakav pristup je značajan jer se domenski specifični jezici najčešće razvijaju zajedno sa domenom za koji su namijenjeni.⁶

## 3.2. Domen jezika

Domen jezika predstavljaju Laravel aplikacije koje se zasnivaju na upravljanju podacima. Takve aplikacije najčešće obuhvataju više entiteta, njihove atribute, međusobne odnose i skup standardnih operacija nad zapisima. U okviru ovog rada posebno se razmatraju operacije kreiranja, čitanja, ažuriranja i brisanja podataka, jer se one često ponavljaju u poslovnim i informacionim sistemima.

U posmatranom domenu mogu se izdvojiti sljedeći osnovni pojmovi:

- aplikacija, kao cjelina koja ima naziv i skup entiteta;
- entitet, kao domenski objekat koji se mapira na Eloquent model i tabelu baze podataka;
- polje, kao atribut entiteta koji se mapira na kolonu baze, validaciono pravilo i polje forme;
- tip podatka, kao ograničenje koje određuje način čuvanja, validacije i prikaza vrijednosti;
- modifikator, kao dodatno pravilo koje utiče na obaveznost, jedinstvenost ili opcionost polja;
- relacija, kao veza između dva entiteta;
- funkcionalnost, kao skup ekrana i akcija koje generator treba da proizvede za entitet.

Ograničavanje jezika na ove pojmove ima praktično opravdanje. Veći dio početne strukture Laravel aplikacije može se izvesti upravo iz ovih informacija. Na primjer, ako je poznato da entitet `Product` ima polja `name`, `sku`, `price` i relaciju `belongsTo Category`, moguće je automatski generisati model `Product`, migraciju za tabelu `products`, kontroler `ProductController`, rute za upravljanje proizvodima, forme za unos i izmjenu podataka, tabelarni prikaz i relaciju prema entitetu `Category`.

Jezik nije namijenjen opisivanju kompletne poslovne logike aplikacije. Složeni uslovi, specifične autorizacione politike, integracije sa eksternim servisima i napredni algoritmi ostaju izvan početnog obima jezika. Takva odluka je svjesno donesena kako bi jezik ostao jednostavan i stabilan, a generator pouzdan u dijelu koji se može formalno opisati i ponovljivo proizvesti.

## 3.3. Opšta struktura specifikacije

Specifikacija se sastoji od jednog `app` bloka unutar kojeg se definiše jedan ili više `entity` blokova. `app` blok predstavlja korijen specifikacije i sadrži naziv aplikacije. Svaki `entity` blok predstavlja jedan domenski entitet i sadrži polja, relacije i opcione deklaracije koje utiču na generisane ekrane.

Opšti oblik specifikacije može se prikazati na sljedeći način:

```text
app NazivAplikacije {
  entity NazivEntiteta {
    features: index create edit show delete
    display: nazivPolja
    nazivPolja: tipPolja modifikatori
    belongsTo DrugiEntitet
  }
}
```

Naziv aplikacije i naziv entiteta počinju velikim slovom i koriste oblik pogodan za izvođenje PHP klasa. Nazivi polja počinju malim slovom i koriste oblik pogodan za izvođenje atributa modela, kolona u bazi podataka i imena polja u HTML formama. Ovakvo pravilo omogućava da sintaksni analizator već u ranoj fazi odbaci neispravne nazive i spriječi generisanje koda koji ne odgovara Laravel konvencijama.

Svako polje se definiše u zasebnoj liniji. Osnovni oblik definicije polja sastoji se od naziva polja, dvotačke, tipa podatka i opcionog niza modifikatora ili vrijednosti metapodataka. Modifikatori određuju opšta pravila validacije, dok metapodaci omogućavaju preciznije podešavanje polja, kao što su minimalna dužina, maksimalna dužina, dozvoljene opcije, prihvatljivi tipovi fajlova, podrazumijevana vrijednost i pomoćni tekst.

Jezik podržava i komentare u linijama koje počinju znakom `#`. Komentari ne utiču na semantiku specifikacije i uklanjaju se prije sintaksne analize. Time se omogućava dokumentovanje modela bez promjene ulaza koji se koristi za generisanje aplikacije.

## 3.4. Primjer specifikacije

U nastavku je dat primjer DSL specifikacije za jednostavnu aplikaciju za upravljanje proizvodima, kategorijama i oznakama:

```text
app InventorySystem {
  entity Product {
    features: index create edit show delete
    display: name
    name: string required minLength=3 maxLength=120
    sku: string required unique
    description: text nullable
    price: decimal required min=0 step=0.01
    photo: image nullable accept=image/png,image/jpeg max=2048
    active: boolean
    belongsTo Category
    belongsToMany Tag pivot product_tag
  }

  entity Category {
    display: title
    title: string required unique
    description: text nullable
    hasMany Product
  }

  entity Tag {
    name: string required unique
  }
}
```

Iz navedene specifikacije mogu se izvesti tri entiteta: `Product`, `Category` i `Tag`. Entitet `Product` ima tekstualna, numerička, logička i slikovna polja, kao i dvije relacije. Relacija `belongsTo Category` označava da proizvod pripada jednoj kategoriji, zbog čega generator dodaje kolonu `category_id`, Eloquent metodu `category()` i izbor kategorije u formama za kreiranje i izmjenu proizvoda. Relacija `belongsToMany Tag` označava vezu više-prema-više između proizvoda i oznaka, pri čemu se koristi pivot tabela `product_tag`.

Deklaracija `features` određuje koje operacije i ekrani treba da budu generisani za entitet. U primjeru su uključene sve standardne funkcionalnosti: lista zapisa, forma za kreiranje, forma za izmjenu, detaljni prikaz i brisanje. Deklaracija `display` određuje polje koje se koristi za tekstualno predstavljanje zapisa u relacijama, tabelama i detaljnim prikazima. Ako ova deklaracija nije navedena, generator koristi unaprijed definisani redosljed prioriteta, najprije `name`, zatim `title`, zatim `email`, a zatim identifikator zapisa.

## 3.5. Konkretna sintaksa jezika

Konkretna sintaksa predstavlja tekstualni oblik u kojem korisnik ili aplikacioni interfejs zapisuje specifikaciju. Ona definiše ključne riječi, oblik blokova, pravila imenovanja i način navođenja pojedinačnih elemenata. Pojednostavljena gramatika jezika može se prikazati sljedećim pravilima:

```text
Specification = AppDeclaration

AppDeclaration = "app" AppName "{" EntityDeclaration+ "}"

EntityDeclaration = "entity" EntityName "{" EntityMember+ "}"

EntityMember =
    FeatureDeclaration
  | DisplayDeclaration
  | FieldDeclaration
  | RelationDeclaration

FeatureDeclaration = "features" ":" (FeatureName+ | "none")

DisplayDeclaration = "display" ":" FieldName

FieldDeclaration = FieldName ":" FieldType FieldToken*

FieldToken = FieldModifier | MetadataDeclaration

MetadataDeclaration = MetadataKey "=" MetadataValue

RelationDeclaration =
    RelationType EntityName
  | "belongsToMany" EntityName "pivot" PivotTable

AppName = PascalCaseIdentifier
EntityName = PascalCaseIdentifier
FieldName = camelCaseIdentifier
PivotTable = snake_case_identifier
```

U navedenoj gramatici `Specification` predstavlja cijeli dokument. Dokument mora sadržati tačno jedan `app` blok, a taj blok mora sadržati najmanje jedan entitet. Svaki entitet mora sadržati najmanje jedno polje, jer entitet bez atributa nema dovoljno informacija za generisanje korisne migracije, forme ili prikaza.

`FeatureDeclaration` je opciona deklaracija kojom se određuje skup generisanih ekrana. Ako se ova deklaracija izostavi, podrazumijeva se da su omogućene sve standardne funkcionalnosti. Ako se navede `features: none`, za entitet se ne generišu korisnički ekrani za standardne operacije, iako model i migracija i dalje mogu biti dio aplikacione strukture.

`DisplayDeclaration` je opciona deklaracija koja određuje reprezentativno polje entiteta. Ona se koristi kada je potrebno prikazati zapis povezanog entiteta u padajućoj listi, tabeli ili detaljnom prikazu. Vrijednost mora odgovarati jednom od polja definisanih u istom entitetu.

`RelationDeclaration` podržava relacije `belongsTo`, `hasMany`, `hasOne` i `belongsToMany`. Kod relacije `belongsToMany` moguće je navesti naziv pivot tabele. Ako naziv nije naveden, generator ga izvodi automatski na osnovu naziva povezanih entiteta.

## 3.6. Glavni koncepti i naredbe jezika

Jezik je oblikovan oko malog broja glavnih naredbi. Svaka naredba ima jasno značenje i direktno utiče na rezultat generisanja. Ovakav pristup omogućava da se DSL specifikacija lako čita i da korisnik može razumjeti šta će biti generisano bez poznavanja svih detalja Laravel implementacije.

Osnovne naredbe jezika prikazane su u tabeli 3.1.

| Naredba | Primjer | Značenje |
| --- | --- | --- |
| `app` | `app InventorySystem` | Definiše naziv aplikacije koja se generiše |
| `entity` | `entity Product` | Definiše domenski entitet, odnosno model aplikacije |
| definicija polja | `name: string required` | Definiše atribut entiteta, njegov tip i pravila |
| `features` | `features: index create edit show delete` | Određuje koje ekrane i akcije generator pravi |
| `display` | `display: name` | Određuje polje koje predstavlja zapis u interfejsu |
| `belongsTo` | `belongsTo Category` | Definiše da trenutni entitet pripada jednom zapisu drugog entiteta |
| `hasMany` | `hasMany Product` | Definiše da trenutni entitet ima više povezanih zapisa |
| `hasOne` | `hasOne Profile` | Definiše da trenutni entitet ima jedan povezani zapis |
| `belongsToMany` | `belongsToMany Tag pivot product_tag` | Definiše relaciju više-prema-više i opciono naziv pivot tabele |

Primjer jednostavnog ulaza na DSL jeziku prikazan je u nastavku:

```text
app BlogSystem {
  entity Post {
    features: index create edit show delete
    display: title
    title: string required maxLength=150
    body: text required
    published: boolean
    belongsTo User
    belongsToMany Tag
  }

  entity User {
    name: string required
    email: email required unique
    hasMany Post
  }

  entity Tag {
    name: string required unique
  }
}
```

Na osnovu ovog ulaza generator prepoznaje tri entiteta: `Post`, `User` i `Tag`. Za entitet `Post` generišu se ekrani za listu, kreiranje, izmjenu, prikaz i brisanje. Polje `title` postaje tekstualno polje sa ograničenjem maksimalne dužine, polje `body` postaje višeredni tekst, dok `published` postaje checkbox. Relacija `belongsTo User` omogućava izbor autora objave, a relacija `belongsToMany Tag` omogućava povezivanje objave sa više oznaka.

Ovakvi primjeri su značajni jer pokazuju osnovni princip rada jezika: korisnik opisuje šta aplikacija treba da sadrži, dok generator određuje kako će se taj opis prevesti u Laravel strukturu.

## 3.7. Tipovi podataka i modifikatori polja

Tip podatka u DSL specifikaciji određuje više aspekata generisanog koda. Isti tip utiče na kolonu u migraciji baze podataka, pravilo validacije u kontroleru, HTML element u formi, kastovanje vrijednosti u modelu i način prikaza vrijednosti u korisničkom interfejsu. Zbog toga tipovi predstavljaju jedan od najvažnijih semantičkih elemenata jezika.

Podržani tipovi podataka prikazani su u tabeli 3.2.

| DSL tip | Semantičko značenje | Primjena u Laravel komponentama |
| --- | --- | --- |
| `string` | Kraći tekstualni podatak | `string` kolona, tekstualno polje forme, `string` validacija |
| `text` | Duži tekstualni sadržaj | `text` kolona, `textarea` element, tekstualna validacija |
| `integer` | Cjelobrojna vrijednost | `integer` kolona, numerički unos, `integer` validacija |
| `bigInteger` | Veća cjelobrojna vrijednost | `bigInteger` kolona i cjelobrojna validacija |
| `decimal` | Decimalna vrijednost | `decimal(10, 2)` kolona i `numeric` validacija |
| `float` | Decimalna vrijednost sa pokretnim zarezom | `float` kolona i numerička validacija |
| `boolean` | Logička vrijednost | `boolean` kolona, checkbox unos i `boolean` validacija |
| `date` | Datum | `date` kolona, unos datuma i `date` validacija |
| `datetime` | Datum i vrijeme | `dateTime` kolona, `datetime-local` unos i validacija datuma |
| `time` | Vrijeme | `time` kolona i validacija formata vremena |
| `timestamp` | Vremenska oznaka | `timestamp` kolona i validacija datuma |
| `email` | Adresa elektronske pošte | `string` kolona i `email` validacija |
| `url` | Web adresa | `string` kolona i `url` validacija |
| `phone` | Telefonski broj | Tekstualna kolona i tekstualna validacija |
| `password` | Lozinka | Sakriveno polje, hash kastovanje i minimalna dužina |
| `enum` | Vrijednost iz skupa opcija | Tekstualna kolona, izbor opcije i `in` validacija kada su opcije navedene |
| `file` | Putanja do fajla | Tekstualna kolona, upload polje i `file` validacija |
| `image` | Putanja do slike | Tekstualna kolona, upload polje i `image` validacija |
| `json` | Strukturirani podaci | `json` kolona i JSON validacija |
| `foreignId` | Strani ključ | Kolona za identifikator povezanog zapisa |

Modifikatori polja dopunjavaju značenje tipa. Podržani su modifikatori `required`, `nullable` i `unique`. Modifikator `required` označava da vrijednost mora biti unesena. Modifikator `nullable` označava da polje može ostati prazno. Ako nije naveden nijedan od ova dva modifikatora, polje se u generatoru tretira kao opciono. Modifikator `unique` označava da vrijednost mora biti jedinstvena u okviru odgovarajuće tabele.

Semantika modifikatora nije potpuno nezavisna od tipa podatka. Polje ne može istovremeno biti `required` i `nullable`, jer bi takva specifikacija sadržala kontradiktorno pravilo. Takođe, polje mora biti `required` da bi moglo biti `unique`, a jedinstvenost je dozvoljena samo za tipove koji se mogu pouzdano indeksirati u ciljnoj bazi podataka. Time se dio tehničkih ograničenja baze podataka prenosi u semantičku validaciju DSL-a i sprječava generisanje neispravnih migracija.

Pored modifikatora, polja mogu imati metapodatke u obliku `ključ=vrijednost`. Primjeri takvih vrijednosti su:

```text
title: string required minLength=3 maxLength=120 placeholder="Naziv proizvoda"
status: enum required options=draft|published|archived default=draft
photo: image nullable accept=image/png,image/jpeg max=2048
price: decimal required min=0 max=9999 step=0.01
```

Metapodaci proširuju mogućnosti jezika bez uvođenja velikog broja novih ključnih riječi. Vrijednosti `min`, `max` i `step` koriste se kod numeričkih polja. Vrijednosti `minLength` i `maxLength` koriste se kod tekstualnih polja. Vrijednost `options` definiše skup dozvoljenih vrijednosti za izborna polja. Vrijednost `accept` određuje dozvoljene MIME tipove kod upload polja, dok `placeholder`, `default` i `help` utiču na početno ponašanje i opis polja u korisničkom interfejsu.

## 3.8. Relacije između entiteta

Relacije omogućavaju da se u specifikaciji ne opisuju samo izolovani entiteti, već i njihove međusobne veze. U Laravelu su relacije važan dio Eloquent modela, jer povezuju tabele baze podataka i omogućavaju rad sa povezanim zapisima kroz objektni model. Zbog toga DSL uvodi posebne konstrukcije za najčešće relacije.

Relacija `belongsTo` označava da trenutni entitet pripada jednom zapisu ciljnog entiteta. Na primjer:

```text
entity Product {
  name: string required
  belongsTo Category
}
```

Ova deklaracija znači da entitet `Product` dobija strani ključ `category_id`, Eloquent metodu `category()` i select polje u formama za izbor kategorije. Generator takođe priprema odgovarajuće validaciono pravilo kojim se provjerava da izabrana kategorija postoji u tabeli `categories`.

Relacija `hasMany` označava da jedan zapis trenutnog entiteta može imati više povezanih zapisa ciljnog entiteta:

```text
entity Category {
  title: string required
  hasMany Product
}
```

U ovom slučaju generator kreira metodu `products()` u modelu `Category` i prikaz povezanih proizvoda na detaljnoj stranici kategorije. Ako je u specifikaciji navedena samo jedna strana relacije, sintaksni analizator može izvesti inverznu relaciju. Na primjer, iz `hasMany Product` u entitetu `Category` izvodi se `belongsTo Category` u entitetu `Product`, ukoliko ona nije eksplicitno navedena.

Relacija `hasOne` koristi se kada jedan zapis trenutnog entiteta ima najviše jedan povezani zapis ciljnog entiteta. Njena semantika je slična relaciji `hasMany`, ali se u Eloquent modelu generiše metoda koja vraća jedan povezani objekat. I za ovu relaciju se može izvesti inverzna `belongsTo` relacija.

Relacija `belongsToMany` opisuje vezu više-prema-više. Primjer takve relacije je veza između proizvoda i oznaka:

```text
entity Product {
  name: string required
  belongsToMany Tag pivot product_tag
}
```

Ako se naziv pivot tabele ne navede, on se automatski izvodi na osnovu naziva povezanih entiteta, sortiranih po abecednom redosljedu i zapisanih u snake case obliku. Za relaciju `Product` i `Tag` podrazumijevani naziv pivot tabele je `product_tag`. Generator zatim kreira migraciju za pivot tabelu, Eloquent metode na obje strane relacije i elemente korisničkog interfejsa za povezivanje zapisa.

Uvođenje automatskog izvođenja inverznih relacija smanjuje količinu specifikacije koju korisnik mora napisati, ali zadržava konzistentnost internog modela. Ukoliko je relacija već eksplicitno navedena, analizator je ne duplira. Time se omogućava da korisnik definiše samo najprirodniju stranu odnosa, dok sistem obezbjeđuje informacije koje su potrebne generatoru.

## 3.9. Funkcionalnosti entiteta

Pored podataka i relacija, DSL omogućava definisanje funkcionalnosti koje se generišu za svaki entitet. Ova mogućnost se uvodi zato što svaki entitet u aplikaciji ne mora imati isti skup ekrana i akcija. Neki entiteti služe samo kao pomoćni modeli, dok drugi zahtijevaju puni korisnički interfejs za upravljanje zapisima.

Podržane funkcionalnosti su:

- `index`, za generisanje liste zapisa;
- `create`, za generisanje forme za kreiranje i odgovarajuće `store` akcije;
- `edit`, za generisanje forme za izmjenu i odgovarajuće `update` akcije;
- `show`, za generisanje detaljnog prikaza zapisa;
- `delete`, za omogućavanje brisanja i odgovarajuće `destroy` akcije.

Ako se linija `features` izostavi, podrazumijeva se da su uključene sve navedene funkcionalnosti. Ako je potrebno generisati samo dio interfejsa, navodi se podskup funkcionalnosti:

```text
features: index show
```

Na osnovu ovog zapisa generator proizvodi samo listu i detaljni prikaz. U Laravel rutama se u tom slučaju generiše `Route::resource` ograničen na metode `index` i `show`, dok se u kontroleru i Blade prikazima izostavljaju akcije za kreiranje, izmjenu i brisanje. Time se specifikacijom direktno kontroliše obim generisanog korisničkog interfejsa.

Vrijednost `features: none` koristi se kada entitet treba da postoji na nivou modela i baze podataka, ali ne treba da dobije standardne ekrane za upravljanje. Ova opcija je korisna za pomoćne ili sistemske entitete koji učestvuju u relacijama, ali nijesu dio direktnog korisničkog toka.

## 3.10. Apstraktna sintaksa i metamodel

Nakon sintaksne analize tekstualna specifikacija se transformiše u interni model podataka. Taj model predstavlja apstraktnu sintaksu jezika, odnosno strukturu koja sadrži značenje specifikacije nezavisno od njenog konkretnog tekstualnog oblika. Generator ne radi direktno nad sirovim tekstom, već nad ovim modelom.

Korijenski element metamodela je aplikacija. Ona sadrži naziv i listu entiteta. Svaki entitet sadrži naziv, izvedene nazive potrebne za Laravel, skup funkcionalnosti, opciono display polje, listu polja i listu relacija. Svako polje sadrži naziv, labelu, tip, informaciju o obaveznosti, informaciju o jedinstvenosti i metapodatke. Svaka relacija sadrži tip relacije, izvorni entitet, ciljni entitet, naziv Eloquent metode, naziv stranog ključa ako je primjenljivo, naziv ciljne tabele i naziv pivot tabele kod relacija više-prema-više.

Struktura metamodela može se prikazati na sljedeći način:

```text
Application
  name
  entities[]

Entity
  name
  table
  route
  variable
  collection
  features
  displayField
  fields[]
  relations[]

Field
  name
  label
  type
  required
  unique
  metadata

Relation
  type
  source
  target
  method
  foreignKey
  targetTable
  pivotTable
  inferred
```

Izvedeni atributi metamodela imaju važnu ulogu u generisanju koda. Na primjer, iz naziva entiteta `ProductCategory` mogu se izvesti naziv tabele `product_categories`, naziv rute `product-categories`, promjenljiva `productCategory` i kolekcija `productCategories`. Ovi elementi se zatim dosljedno koriste u modelima, kontrolerima, rutama i prikazima.

Posebno je značajno polje `inferred` kod relacija. Ono označava da relacija nije neposredno napisana u DSL specifikaciji, već je izvedena tokom semantičke obrade. Takve relacije se koriste za potrebe generisanja kompletnih Eloquent metoda i korisničkog interfejsa, ali se pri čuvanju korisničke specifikacije mogu razlikovati od relacija koje je korisnik eksplicitno naveo.

## 3.11. Semantička pravila i validacija

Semantička validacija provjerava da li je sintaksno ispravna specifikacija ujedno i smislen opis aplikacije. Ovaj korak je potreban jer tekst može biti gramatički ispravan, ali semantički neupotrebljiv. Na primjer, relacija može pokazivati na entitet koji ne postoji, display polje može referencirati nepostojeći atribut, ili polje može imati kombinaciju modifikatora koja nema smisla.

U dizajnu jezika definisana su sljedeća osnovna pravila validacije:

- specifikacija mora početi `app` blokom;
- naziv aplikacije mora početi velikim slovom;
- aplikacija mora sadržati najmanje jedan entitet;
- naziv entiteta mora početi velikim slovom;
- entitet mora sadržati najmanje jedno polje;
- nazivi polja moraju početi malim slovom;
- tip polja mora pripadati skupu podržanih tipova;
- modifikatori moraju pripadati skupu podržanih modifikatora;
- isti entitet ne smije biti definisan više puta;
- isto polje ne smije biti definisano više puta u okviru jednog entiteta;
- `display` mora pokazivati na postojeće polje u istom entitetu;
- relacija mora pokazivati na postojeći ciljni entitet;
- relacija ne smije pokazivati na isti entitet;
- pivot tabela smije biti navedena samo kod relacije `belongsToMany`;
- polje ne može istovremeno biti `required` i `nullable`;
- `unique` se može koristiti samo nad obaveznim poljima odgovarajućih tipova.

Validacija ima dvije funkcije. Prva je zaštita generatora od neispravnog ulaza. Generator se može osloniti na to da dobija konzistentan interni model i ne mora u svakoj fazi ponavljati iste provjere. Druga funkcija je povratna informacija korisniku. Kada je specifikacija neispravna, sistem vraća poruku koja ukazuje na mjesto i prirodu greške, čime se olakšava ispravka specifikacije.

Semantička validacija je posebno važna kod relacija. Ako bi generator prihvatio relaciju prema nepostojećem entitetu, nastali bi modeli, migracije i validaciona pravila koja referenciraju nepostojeće klase ili tabele. Takav projekat ne bi mogao biti pouzdano pokrenut. Zbog toga se postojanje svih ciljnih entiteta provjerava prije faze generisanja.

## 3.12. Mapiranje jezika na Laravel komponente

Jedan od ključnih elemenata dizajna jezika jeste definisanje pravila mapiranja DSL konstrukcija na Laravel komponente. Mapiranje povezuje apstraktni opis aplikacije sa konkretnim izlaznim fajlovima. Na taj način DSL dobija praktičnu vrijednost, jer se svaka konstrukcija jezika prevodi u dio izvršive aplikacione strukture.

Osnovno mapiranje prikazano je u tabeli 3.3.

| Element jezika | Element metamodela | Generisana Laravel komponenta |
| --- | --- | --- |
| `app Naziv` | Aplikacija | `APP_NAME`, naziv projekta, README, konfiguracija |
| `entity Product` | Entitet | Eloquent model, kontroler, migracija, ruta i direktorijum prikaza |
| `name: string required` | Polje | Kolona u migraciji, validaciono pravilo, unos u formi i prikaz vrijednosti |
| `unique` | Pravilo polja | Jedinstveni indeks i `unique` validacija |
| `display: name` | Reprezentativno polje | `displayName()` metoda i prikaz povezanih zapisa |
| `belongsTo Category` | Relacija | Foreign key kolona, Eloquent metoda, validacija i select polje |
| `hasMany Product` | Relacija | Eloquent metoda i prikaz povezanih zapisa |
| `belongsToMany Tag` | Relacija | Pivot migracija, Eloquent metode i višestruki izbor u formi |
| `features` | Skup funkcionalnosti | Ograničene resource rute, metode kontrolera i Blade prikazi |

Za svaki entitet generator proizvodi Eloquent model. Model sadrži listu popunjivih atributa, kastovanje vrijednosti za odgovarajuće tipove, skrivene atribute za lozinke i metode za definisane relacije. Ako je entitet `User`, generator posebno vodi računa o postojećoj Laravel autentifikacionoj strukturi i proširuje podrazumijevanu tabelu korisnika umjesto da generiše novu tabelu istog imena.

Migracije se generišu na osnovu polja i relacija. Osnovna migracija entiteta kreira tabelu i kolone za definisana polja. Za relacije `belongsTo` generišu se strani ključevi, dok se za relacije `belongsToMany` generišu posebne pivot tabele. Ovakvo razdvajanje omogućava jasniju strukturu migracija i sprječava probleme koji mogu nastati kada se više tabela sa međusobnim vezama kreira u istom trenutku.

Kontroleri se generišu kao resursni kontroleri. Skup metoda zavisi od deklaracije `features`. Ako su uključene sve funkcionalnosti, kontroler sadrži metode `index`, `create`, `store`, `show`, `edit`, `update` i `destroy`. Ako je skup funkcionalnosti ograničen, generišu se samo metode koje su potrebne za odabrane akcije. Kontroler sadrži i validaciona pravila izvedena iz tipova, modifikatora i vrijednosti metapodataka.

Blade prikazi se generišu za liste, forme, detaljne prikaze i povezane zapise. Tip polja utiče na vrstu HTML elementa. Na primjer, `boolean` se prikazuje kao checkbox, `text` kao višeredni unos, `date` kao unos datuma, `datetime` kao unos datuma i vremena, `enum` kao izbor iz ponuđenih opcija, dok `file` i `image` koriste upload polje i odgovarajući `multipart` formular.

Pored osnovnih aplikacionih komponenti, generator proizvodi i pomoćne elemente projekta: početni README, konfiguracione fajlove, osnovne testne fajlove, autentifikacione rute i prikaze, početne seedere i ZIP arhivu generisanog projekta. Time izlaz generatora nije samo skup izolovanih fajlova, već početna Laravel aplikacija spremna za instalaciju i dalji razvoj.

## 3.13. Tok obrade specifikacije

Proces obrade DSL specifikacije može se podijeliti u više faza. Prva faza je unos specifikacije. Specifikacija može nastati direktnim unosom DSL teksta ili kroz korisnički interfejs koji od unesenih entiteta, polja i relacija formira DSL zapis. Time se korisniku može ponuditi jednostavniji način rada, dok se u pozadini i dalje zadržava formalni tekstualni ulaz generatora.

Druga faza je sintaksna analiza. U ovoj fazi uklanjaju se komentari, pronalazi se `app` blok, izdvajaju se `entity` blokovi i svaka linija entiteta se klasifikuje kao deklaracija funkcionalnosti, deklaracija display polja, definicija polja ili definicija relacije. Ako linija ne odgovara nijednom dozvoljenom obliku, analiza se prekida i vraća se greška.

Treća faza je semantička obrada. U ovoj fazi provjeravaju se duplikati, podržani tipovi, dozvoljeni modifikatori, ključevi metapodataka, relacije prema postojećim entitetima i međusobna usklađenost pravila. U istoj fazi izvode se nazivi tabela, ruta, promjenljivih, kolekcija, stranih ključeva i pivot tabela.

Četvrta faza je formiranje metamodela. Rezultat sintaksne i semantičke obrade jeste strukturirani model koji se može čuvati u bazi podataka, zapisati u logove i predati generatoru. U implementiranom sistemu projekat, entiteti, polja i relacije čuvaju se kroz odgovarajuće modele, dok se originalna DSL specifikacija čuva kao ulazni fajl `model.mydsl`.

Peta faza je generisanje koda. Generator prolazi kroz metamodel i proizvodi Laravel projekat. Nakon generisanja, izlazni direktorijum se pakuje u ZIP arhivu koju korisnik može preuzeti. Status generisanja se vodi kroz stanja `queued`, `running`, `succeeded` i `failed`, čime se omogućava praćenje procesa i prikaz grešaka ako generisanje ne uspije.

Opisani tok se može prikazati kao niz transformacija:

```text
DSL tekst
  -> sintaksna analiza
  -> semantička validacija
  -> metamodel aplikacije
  -> generisanje Laravel fajlova
  -> ZIP arhiva projekta
```

Ovakva organizacija razdvaja odgovornosti između komponenti sistema. Sintaksni analizator je zadužen za razumijevanje jezika, metamodel za čuvanje značenja specifikacije, a generator za transformaciju modela u konkretne Laravel fajlove. Razdvajanje ovih faza olakšava testiranje, održavanje i buduće proširivanje sistema.

## 3.14. Ograničenja dizajna jezika

Iako dizajnirani jezik omogućava generisanje velikog dijela početne Laravel aplikacije, njegov obim je namjerno ograničen. Jezik je prvenstveno namijenjen aplikacijama koje se mogu opisati kroz entitete, polja, relacije i standardne operacije nad podacima. Složena poslovna pravila, napredna autorizacija, uloge korisnika, radni tokovi, integracije sa eksternim servisima i specifična ponašanja korisničkog interfejsa nijesu obuhvaćeni osnovnom verzijom jezika.

Ograničenje postoji i kod tipova relacija. Iako su podržane najčešće Eloquent relacije, napredni obrasci kao što su polimorfne relacije, kompleksni uslovi nad relacijama i dodatni atributi na pivot tabelama nijesu dio osnovnog modela. Njihovo uključivanje zahtijevalo bi proširenje sintakse, metamodela i generatora.

Još jedno ograničenje odnosi se na nivo prilagođavanja generisanog interfejsa. DSL omogućava izbor funkcionalnosti i osnovno podešavanje polja, ali ne opisuje detaljan raspored stranica, specifičan vizuelni dizajn ili kompleksne komponente korisničkog interfejsa. Takve izmjene se mogu raditi nakon generisanja projekta, u standardnom Laravel razvojnom toku.

Navedena ograničenja ne umanjuju osnovnu namjenu jezika, već preciziraju njegov domen. Cilj jezika nije generisanje svake moguće Laravel aplikacije, već automatizacija onog dijela razvoja koji je dovoljno struktuiran, ponovljiv i pogodan za formalni opis. Upravo takvo ograničenje predstavlja jednu od prednosti domenski specifičnog pristupa, jer omogućava da jezik ostane razumljiv i praktično primjenjiv.

## 3.15. Mogućnosti proširenja

Dizajn jezika ostavlja prostor za dalji razvoj. Prvi pravac proširenja odnosi se na dodavanje novih tipova podataka i naprednijih validacionih pravila. Na primjer, jezik se može proširiti podrškom za regularne izraze, složene validacione uslove, zavisna polja i posebna pravila za lokalizovane formate podataka.

Drugi pravac odnosi se na proširenje relacija. U budućim verzijama moguće je dodati polimorfne relacije, dodatne atribute na pivot tabelama, opcije za ponašanje pri brisanju povezanih zapisa i preciznije podešavanje stranih ključeva. Time bi se povećala izražajnost jezika, ali bi se istovremeno morala očuvati njegova čitljivost.

Treći pravac odnosi se na autorizaciju i korisničke uloge. DSL bi mogao omogućiti definisanje pravila pristupa po entitetu i funkcionalnosti, na osnovu kojih bi generator proizvodio politike, middleware slojeve ili pravila za prikaz akcija u korisničkom interfejsu.

Četvrti pravac odnosi se na testove. Iako generator proizvodi osnovne testne fajlove, DSL bi se mogao proširiti konstrukcijama za definisanje očekivanog ponašanja, obaveznih scenarija i početnih podataka za testiranje. Time bi se povećao stepen automatizacije i unaprijedila provjera ispravnosti generisanog projekta.

Peti pravac odnosi se na podršku za druge ciljne tehnologije. Pošto je specifikacija odvojena od generisanog koda, moguće je razviti dodatne generatore koji isti ili prošireni metamodel prevode u druge backend ili frontend okvire. Ipak, takvo proširenje zahtijevalo bi pažljivo razdvajanje opštih domenskih pojmova od Laravel specifičnih pravila.

## 3.16. Zaključak poglavlja

U ovom poglavlju definisan je dizajn domenski specifičnog jezika za generisanje Laravel aplikacija. Prikazani su ciljevi dizajna, domen jezika, opšta struktura specifikacije, konkretna sintaksa, tipovi podataka, modifikatori, relacije, funkcionalnosti entiteta, metamodel, semantička validacija i pravila mapiranja na Laravel komponente.

Predloženi jezik omogućava da se aplikacija opiše deklarativno, kroz entitete, polja, relacije i funkcionalnosti, dok se tehnički detalji generisanja prepuštaju generatoru koda. Time se ostvaruje veza između teorijskih principa domenski specifičnih jezika i praktične implementacije Laravel aplikacija. Dizajn jezika predstavlja osnovu za implementaciju sintaksnog analizatora, metamodela i generatora koda, što je predmet narednog poglavlja.
