# 4. IMPLEMENTACIJA DSL-A I GENERATORA KODA

U ovom poglavlju prikazana je implementacija domenski specifičnog jezika i generatora koda za Laravel aplikacije. Dok je prethodno poglavlje bilo usmjereno na dizajn jezika, njegove koncepte, sintaksu i semantiku, ovo poglavlje opisuje način na koji su ti elementi realizovani u konkretnom softverskom prototipu.

Implementirano rješenje predstavlja Laravel web aplikaciju koja korisniku omogućava definisanje aplikacionog modela, parsiranje DSL specifikacije, čuvanje obrađenih elemenata i generisanje novog Laravel projekta. Sistem objedinjuje tri osnovne cjeline: korisnički interfejs za unos specifikacije, sintaksni analizator koji prevodi tekstualni DSL u interni model i generator koji taj model transformiše u fajlove Laravel aplikacije.

Implementacija je realizovana u programskom jeziku PHP, u okviru Laravel razvojnog okvira. Izbor Laravel okruženja opravdan je time što se i sam generator i generisani projekti oslanjaju na Laravel konvencije, kao što su struktura direktorijuma, Eloquent modeli, migracije, kontroleri, rute i Blade šabloni. Laravel dokumentacija ističe predvidljivu organizaciju aplikacije, Eloquent modele, migracije i kontrolere kao centralne elemente razvoja aplikacija, što je neposredno iskorišćeno pri oblikovanju generatora.¹⁴

U skladu sa ciljem rada, u ovom poglavlju se ne prikazuje izvorni kod implementacije u detaljnom obliku. Umjesto toga, prikazana je struktura rješenja, uloga pojedinačnih komponenti, tok obrade specifikacije i rezultat koji korisnik dobija korišćenjem aplikacije. Na taj način se naglasak stavlja na koncept DSL-a i generatora, a ne na pojedinačne programske iskaze.

## 4.1. Arhitektura implementiranog sistema

Implementirani sistem organizovan je kao standardna Laravel aplikacija sa jasno razdvojenim odgovornostima. Kontroler prima korisnički unos i pokreće proces generisanja. Sintaksni analizator obrađuje DSL specifikaciju i vraća strukturirani model. Modeli baze podataka čuvaju projekat, entitete, polja i relacije. Queue job pokreće generator, priprema radni direktorijum i pakuje rezultat u ZIP arhivu. Generator koda proizvodi fajlove nove Laravel aplikacije.

Osnovne komponente sistema su:

- `GeneratedProjectController`, koji upravlja unosom, izmjenom, prikazom statusa i preuzimanjem generisanog projekta;
- `StoreGeneratedProjectRequest`, koji provjerava osnovne HTTP ulazne podatke;
- `DslParser`, koji implementira sintaksnu i semantičku obradu DSL specifikacije;
- modeli `GeneratedProject`, `GeneratedEntity`, `GeneratedField` i `GeneratedRelation`, koji predstavljaju trajno sačuvani metamodel;
- `GenerateLaravelProject`, koji izvršava proces generisanja i pakovanja projekta;
- `LaravelProjectGenerator`, koji proizvodi izlazne Laravel fajlove na osnovu metamodela.

Ovakva podjela omogućava da se obrada jezika odvoji od generisanja koda. Sintaksni analizator ne proizvodi fajlove, već samo provjerava i strukturira specifikaciju. Generator ne tumači sirovi DSL tekst, već radi nad već validiranim modelom. Time se smanjuje složenost pojedinačnih komponenti i olakšava testiranje.

Tok rada sistema može se prikazati na sljedeći način:

```text
Korisnički unos
  -> DSL specifikacija
  -> sintaksna i semantička analiza
  -> čuvanje metamodela
  -> pokretanje posla generisanja
  -> kreiranje Laravel projekta
  -> ZIP arhiva za preuzimanje
```

## 4.2. Korisnički interfejs za definisanje specifikacije

Korisnički interfejs omogućava da se DSL specifikacija formira kroz grafički unos modela, polja, pravila i relacija. Korisnik ne mora ručno pisati cijeli DSL tekst, već kroz formu dodaje modele, definiše polja, bira tipove podataka, označava validaciona pravila i podešava relacije. Aplikacija zatim na osnovu tog stanja formira DSL tekst koji se šalje serveru.

Interfejs podržava unos naziva aplikacije, dodavanje i uklanjanje entiteta, izbor display polja, izbor generisanih ekrana, definisanje polja i njihovih tipova, izbor modifikatora `required`, `nullable` i `unique`, kao i unos dodatnih metapodataka za pojedina polja. Za relacije se mogu definisati tip relacije, ciljni entitet i naziv pivot tabele kod relacija više-prema-više.

U implementaciji je korišćen Blade za serversko renderovanje stranice i Alpine.js za interaktivno ponašanje forme. Stanje forme se čuva u skrivenom polju `builder_state`, dok se formalna DSL specifikacija čuva u skrivenom polju `dsl`. Time se omogućava da korisnik radi kroz pregledan interfejs, a da server i dalje dobija tekstualnu DSL specifikaciju kao jedinstveni ulaz generatora.

Ovakav pristup ima dvije prednosti. Prva prednost je upotrebljivost, jer korisnik ne mora pamtiti sva sintaksna pravila jezika. Druga prednost je očuvanje formalnosti, jer se bez obzira na grafički unos u pozadini dobija DSL tekst koji se može sačuvati, ponovo parsirati i koristiti kao nezavisan opis aplikacije.

Na ovom mjestu u radu potrebno je prikazati ekrane implementirane aplikacije za generisanje. Predloženi prikazi su:

**Slika 4.1. Interfejs za kreiranje nove Laravel aplikacije**  
Na slici treba prikazati ekran na kojem korisnik unosi naziv aplikacije i započinje definisanje modela. Ova slika ilustruje početni korak u kojem korisnik ne piše Laravel kod, već opisuje aplikaciju kroz domenske pojmove.

**Slika 4.2. Definisanje entiteta, polja i validacionih pravila**  
Na slici treba prikazati formu u kojoj su uneseni naziv modela, polja, tipovi podataka i pravila kao što su `required`, `nullable` i `unique`. Ova slika pokazuje kako se grafički unos prevodi u DSL specifikaciju.

**Slika 4.3. Definisanje relacija između entiteta**  
Na slici treba prikazati dio interfejsa u kojem se bira tip relacije i ciljni entitet. Ova slika je značajna jer pokazuje kako korisnik opisuje odnose kao što su `belongsTo`, `hasMany` i `belongsToMany`.

**Slika 4.4. Status generisanja i preuzimanje ZIP arhive**  
Na slici treba prikazati ekran nakon pokretanja generisanja, gdje korisnik vidi status procesa i dugme za preuzimanje generisanog projekta.

## 4.3. Sintaksni analizator

Sintaksni analizator implementiran je u klasi `DslParser`. Njegov zadatak je da primi tekstualnu specifikaciju, ukloni komentare, pronađe `app` blok, izdvoji `entity` blokove i obradi sve članove entiteta. Rezultat parsiranja je asocijativna struktura koja sadrži naziv aplikacije i listu entiteta sa njihovim poljima, relacijama i dodatnim izvedenim atributima.

Parser prvo provjerava da li specifikacija počinje ispravnom deklaracijom aplikacije:

```text
app NazivAplikacije {
  ...
}
```

Naziv aplikacije mora početi velikim slovom. Nakon pronalaska otvorene vitičaste zagrade, parser traži odgovarajuću zatvorenu zagradu. Za pronalazak odgovarajućeg para koristi se brojanje dubine blokova, što omogućava pravilno izdvajanje sadržaja i kada specifikacija sadrži više entiteta.

Nakon obrade `app` bloka, parser pronalazi sve `entity` blokove. Svaki entitet mora imati jedinstven naziv i najmanje jedno polje. Tijelo entiteta obrađuje se liniju po liniju. Svaka neprazna linija može predstavljati:

- deklaraciju funkcionalnosti, na primjer `features: index show`;
- deklaraciju display polja, na primjer `display: name`;
- definiciju polja, na primjer `price: decimal required min=0`;
- definiciju relacije, na primjer `belongsTo Category`.

Ako linija ne odgovara nijednom podržanom obliku, parser prekida obradu i vraća grešku. Na taj način se neispravna specifikacija zaustavlja prije faze generisanja koda.

## 4.4. Obrada polja i metapodataka

Definicija polja sastoji se od naziva, tipa i opcionih tokena. Token može biti modifikator ili metapodatak. Modifikatori su `required`, `nullable` i `unique`, dok se metapodaci zapisuju u obliku `ključ=vrijednost`.

Parser podržava i vrijednosti pod navodnicima, što je značajno za tekstualne opise, pomoćni tekst i opcije koje sadrže razmake. Na primjer:

```text
name: string required placeholder="Naziv proizvoda"
status: enum required options="u pripremi|objavljeno|arhivirano"
```

Kod obrade metapodataka vrši se normalizacija pojedinih naziva. Na primjer, `maximum` se prevodi u `max`, `minimum` u `min`, `maximumLength` u `maxLength`, a `minimumLength` u `minLength`. Time se omogućava fleksibilniji unos, dok interni model koristi jedinstvene nazive.

Vrijednost `options` se razdvaja po znaku `|` i pretvara u listu dozvoljenih opcija. Numeričke vrijednosti za `min`, `max` i `step` pretvaraju se u cijeli ili decimalni broj kada je to moguće. Vrijednosti `minLength` i `maxLength` pretvaraju se u cijele brojeve. Ostale vrijednosti ostaju tekstualne.

Ova faza je važna jer generator kasnije ne mora ponovo tumačiti tekstualne vrijednosti. On dobija već normalizovane podatke i koristi ih za generisanje validacionih pravila, HTML atributa i početnih vrijednosti.

## 4.5. Semantička validacija

Nakon osnovne sintaksne obrade parser sprovodi semantičku validaciju. Cilj validacije je da se provjeri da li specifikacija predstavlja smislen i generabilan model aplikacije. Validacija obuhvata provjeru naziva, tipova, modifikatora, duplikata, relacija i međusobne usklađenosti pravila.

Za polja se provjerava da li je tip dio podržanog skupa tipova. Ako se navede nepodržan tip, na primjer `money`, parser vraća grešku i ne dozvoljava nastavak procesa. Takođe se provjerava da polje ne bude istovremeno `required` i `nullable`, jer bi to predstavljalo kontradiktorno pravilo.

Posebna pravila važe za `unique` modifikator. Polje mora biti obavezno da bi moglo biti jedinstveno, a tip polja mora biti pogodan za indeksiranje. Time se sprječava generisanje migracija sa neprimjenljivim jedinstvenim indeksima nad tipovima kao što su duži tekstualni sadržaj ili lozinka.

Za relacije se provjerava da ciljni entitet postoji i da relacija ne pokazuje na isti entitet. Takođe se provjerava da se pivot tabela može navesti samo kod relacije `belongsToMany`. Ako se pivot tabela navede kod druge vrste relacije, specifikacija se odbacuje kao neispravna.

Display polje mora biti jedno od polja definisanih u istom entitetu. Ovo pravilo je važno jer generator koristi display polje za metodu `displayName()` i za prikaz povezanih zapisa. Neispravno display polje dovelo bi do generisanja koda koji pristupa nepostojećem atributu modela.

## 4.6. Formiranje internog modela

Rezultat parsiranja nije samo lista elemenata koji su neposredno pročitani iz DSL teksta. Parser formira prošireni interni model koji sadrži i izvedene atribute potrebne generatoru. Za svaki entitet izvode se:

- naziv tabele;
- naziv rute;
- naziv promjenljive za jedan zapis;
- naziv promjenljive za kolekciju zapisa;
- nazivi metoda za relacije;
- nazivi stranih ključeva;
- nazivi pivot tabela.

Na primjer, iz entiteta `ProductCategory` izvodi se tabela `product_categories`, ruta `product-categories`, promjenljiva `productCategory` i kolekcija `productCategories`. Ovi izvedeni oblici se čuvaju u internom modelu kako bi se dosljedno koristili u različitim izlaznim fajlovima.

Parser takođe dodaje inverzne relacije kada je to potrebno. Ako korisnik definiše `hasMany Product` u entitetu `Category`, parser može dodati izvedenu `belongsTo Category` relaciju u entitetu `Product`. Ako korisnik definiše `belongsToMany Tag` u entitetu `Product`, parser dodaje odgovarajuću inverznu relaciju prema `Product` u entitetu `Tag`. Izvedene relacije se označavaju poljem `inferred`, kako bi se razlikovale od relacija koje su eksplicitno navedene u DSL specifikaciji.

Ovakav interni model predstavlja metamodel aplikacije i ulaz za generator koda. Njegova prednost je u tome što generator ne mora znati detalje konkretne tekstualne sintakse, već dobija strukturiran i validiran opis aplikacije.

## 4.7. Perzistencija specifikacije

Nakon uspješnog parsiranja, sistem čuva projekat i elemente specifikacije u bazi podataka. Za to se koriste modeli `GeneratedProject`, `GeneratedEntity`, `GeneratedField` i `GeneratedRelation`. Model `GeneratedProject` predstavlja jedan korisnički projekat i sadrži naziv, jedinstveni identifikator, putanje do DSL ulaza, izlaznog direktorijuma i ZIP arhive, kao i status generisanja.

Model `GeneratedEntity` čuva entitete koji pripadaju projektu. Pored naziva entiteta, čuvaju se informacije o uključenim funkcionalnostima, kao što su `has_index`, `has_create`, `has_edit`, `has_show` i `allows_delete`, kao i opciono display polje.

Model `GeneratedField` čuva polja entiteta. Za svako polje čuvaju se naziv, tip, informacija o obaveznosti, informacija o jedinstvenosti i metapodaci u JSON obliku. Model `GeneratedRelation` čuva direktno navedene relacije, njihov tip, ciljni entitet i naziv pivot tabele ako postoji.

Originalna DSL specifikacija čuva se kao fajl `model.mydsl` u radnom direktorijumu projekta. Pored toga, sistem upisuje i log projekta koji sadrži vrijeme obrade, osnovne informacije o projektu, obrađene entitete i originalni DSL tekst. Ovakav pristup omogućava naknadnu analizu, ponovno pokretanje generisanja i provjeru ulaza na osnovu kojeg je projekat nastao.

Važno je da se u bazi čuvaju samo direktno navedene relacije, dok se izvedene relacije mogu ponovo dobiti parsiranjem DSL-a. Time se čuva razlika između korisničkog unosa i strukture koja je potrebna generatoru.

## 4.8. Pokretanje procesa generisanja

Generisanje projekta pokreće se kroz klasu `GenerateLaravelProject`. Ova klasa predstavlja posao koji može biti izvršen kroz Laravel queue mehanizam. U implementiranom prototipu posao se pokreće sinhrono, što pojednostavljuje tok rada u eksperimentalnom okruženju, ali sama struktura klase omogućava prelazak na asinhrono izvršavanje.

Prilikom pokretanja posla projekat prelazi u status `running`. Zatim se provjerava postojanje jedinstvenog identifikatora projekta i ulaznog DSL fajla. Ako neki od potrebnih elemenata nedostaje, projekat prelazi u status `failed` i čuva se odgovarajuća poruka greške.

Ako je ulazni fajl dostupan, posao ponovo parsira DSL specifikaciju. Ovo je važan korak jer se generator ne oslanja samo na prethodno sačuvani model, već potvrđuje da ulazni fajl i dalje predstavlja ispravnu specifikaciju. Nakon toga se poziva generator koda, koji kreira izlazni Laravel projekat u radnom direktorijumu.

Po završetku generisanja izlazni direktorijum se pakuje u ZIP arhivu. Ako je arhiva uspješno kreirana, projekat prelazi u status `succeeded` i čuvaju se putanje do izlaza i arhive. Ako dođe do greške tokom parsiranja, generisanja ili pakovanja, projekat prelazi u status `failed`, a poruka greške se prikazuje korisniku.

Statusi `queued`, `running`, `succeeded` i `failed` omogućavaju da korisnički interfejs prikaže trenutno stanje procesa. Stranica statusa periodično šalje AJAX zahtjeve i ažurira prikaz dok se generisanje ne završi.

## 4.9. Generator Laravel projekta

Generator koda implementiran je u klasi `LaravelProjectGenerator`. Njegov zadatak je da na osnovu validiranog metamodela napravi novi Laravel projekat. Generator prvo priprema čist izlazni direktorijum, zatim kopira osnovne fajlove iz postojeće Laravel aplikacije i potom generiše fajlove specifične za opisane entitete.

Osnovni izlaz generatora obuhvata:

- konfiguracione fajlove;
- `composer.json` i `package.json`;
- `.env.example` i `phpunit.xml`;
- osnovne direktorijume aplikacije;
- rute;
- layout fajlove;
- autentifikacione kontrolere i prikaze;
- dashboard prikaz;
- modele, kontrolere, migracije i Blade prikaze za svaki entitet;
- seedere;
- testne fajlove;
- README dokument.

Generator ne proizvodi samo djelimične isječke koda, već kompletnu početnu strukturu Laravel aplikacije. Korisnik nakon preuzimanja ZIP arhive dobija projekat koji može instalirati, konfigurisati i dalje razvijati standardnim Laravel postupkom.

## 4.10. Generisanje modela

Za svaki entitet generator kreira Eloquent model u direktorijumu `app/Models`. Model sadrži naziv klase, listu popunjivih atributa, kastovanje vrijednosti i metode relacija. Laravel Eloquent modeli predstavljaju centralni sloj za rad sa tabelama baze podataka, pa generator koristi entitete iz DSL-a kao osnovu za njihovo kreiranje.¹⁴

Lista popunjivih atributa izvodi se iz polja entiteta i stranih ključeva koji nastaju iz relacija `belongsTo`. Polja kojima upravlja Laravel, kao što su `id`, `created_at` i `updated_at`, ne uključuju se u listu popunjivih atributa. Na taj način se generisani model usklađuje sa očekivanim obrascem masovnog popunjavanja podataka.

Kastovanje se generiše na osnovu tipova polja. Na primjer, `boolean` se kastuje u logičku vrijednost, `date` u datum, `datetime` u datum i vrijeme, `decimal` u decimalnu vrijednost sa dvije decimale, `json` u niz, a `password` dobija hash obradu. Kod lozinki se dodatno generiše lista sakrivenih atributa, kako se vrijednost lozinke ne bi direktno prikazivala.

Za relacije se generišu metode `belongsTo`, `hasMany`, `hasOne` i `belongsToMany`. Kod relacije više-prema-više generator koristi podrazumijevanu ili eksplicitno navedenu pivot tabelu i dodaje vremenske oznake na pivot zapise.

Poseban slučaj predstavlja entitet `User`. Ako DSL definiše korisnički entitet, generator ne kreira novu tabelu `users`, već proširuje postojeću Laravel autentifikacionu strukturu. Time se izbjegava konflikt sa podrazumijevanim korisničkim modelom i omogućava povezivanje DSL-a sa autentifikacijom.

## 4.11. Generisanje migracija

Migracije se generišu na osnovu polja i relacija. Laravel migracije predstavljaju mehanizam za definisanje i verzionisanje šeme baze podataka, pa su prirodan izlaz DSL opisa entiteta.¹⁵

Za svaki entitet generator kreira migraciju koja sadrži tabelu, primarni ključ, kolone za definisana polja i vremenske oznake. Tipovi kolona izvode se iz DSL tipova. Na primjer, `string` se prevodi u `string`, `text` u `text`, `integer` u `integer`, `decimal` u `decimal(10, 2)`, `boolean` u `boolean`, `date` u `date`, `datetime` u `dateTime`, a `json` u `json`.

Modifikator `nullable` ili odsustvo `required` pravila utiče na dodavanje `nullable()` modifikatora u migraciji. Modifikator `unique` dodaje jedinstveni indeks samo za tipove kod kojih je takav indeks dozvoljen.

Relacije `belongsTo` obrađuju se kroz posebne migracije za strane ključeve. U osnovnoj migraciji se kreira kolona stranog ključa, dok se ograničenje dodaje odvojenom migracijom. Ovakav pristup smanjuje mogućnost problema sa redosljedom kreiranja tabela.

Relacije `belongsToMany` proizvode posebne pivot migracije. Pivot tabela sadrži strane ključeve prema obje povezane tabele i vremenske oznake. Naziv pivot tabele se uzima iz DSL specifikacije ako je naveden, a u suprotnom se izvodi iz naziva entiteta.

## 4.12. Generisanje kontrolera i ruta

Za svaki entitet generator kreira resursni kontroler. Laravel resursni kontroleri i resursne rute predviđeni su za tipične operacije nad resursima, kao što su kreiranje, čitanje, ažuriranje i brisanje.¹⁶ Upravo zbog toga odgovaraju domenu ovog DSL-a.

Ako entitet ima uključene sve funkcionalnosti, kontroler sadrži metode `index`, `create`, `store`, `show`, `edit`, `update` i `destroy`. Ako su funkcionalnosti ograničene kroz `features`, generator proizvodi samo potrebne metode. Time se izbjegava generisanje neupotrebljivih akcija.

Validaciona pravila u kontroleru izvode se iz tipova, modifikatora i metapodataka. Na primjer, polje `email: email required unique` dobija pravila za obaveznost, email format i jedinstvenost u tabeli. Polje `photo: image nullable max=2048 accept=image/png,image/jpeg` dobija pravila za opcionu sliku, maksimalnu veličinu i dozvoljene MIME tipove. Laravel validacioni sistem podržava širok skup pravila za ulazne podatke, što omogućava direktno mapiranje većeg broja DSL ograničenja.¹⁷

Kontroleri takođe obrađuju upload fajlova i slika, sinhronizaciju relacija više-prema-više i preusmjeravanje korisnika nakon uspješnih operacija. Kod izmjene zapisa posebna pažnja se posvećuje lozinkama, tako da prazna vrijednost lozinke ne briše postojeću lozinku.

Rute se generišu u fajlu `routes/web.php`. Za svaki entitet se kreira `Route::resource`, uz ograničenje na dozvoljene akcije kada je definisan podskup funkcionalnosti. Sve generisane rute nalaze se unutar `auth` middleware grupe, čime je pristup CRUD ekranima ograničen na prijavljene korisnike.

## 4.13. Generisanje Blade prikaza

Generator kreira Blade prikaze za korisnički interfejs generisane aplikacije. Za svaki entitet, zavisno od izabranih funkcionalnosti, mogu se generisati prikazi za listu zapisa, kreiranje, izmjenu i detaljni prikaz.

Prikaz liste sadrži tabelu zapisa, akcije za pregled, izmjenu i brisanje, kao i paginaciju. Akcije koje nijesu uključene u `features` ne prikazuju se korisniku. Ako je za entitet isključeno kreiranje, dugme za dodavanje novog zapisa se ne generiše. Ako je isključeno brisanje, forma za brisanje i dijalog za potvrdu se ne generišu.

Forme za kreiranje i izmjenu generišu se na osnovu tipova polja. Tekstualna polja dobijaju standardni unos, `text` dobija višeredni unos, `boolean` dobija checkbox, `date` dobija unos datuma, `datetime` dobija unos datuma i vremena, `enum` dobija select element, a `file` i `image` dobijaju upload polje. Ako forma sadrži upload polje, automatski se dodaje `enctype="multipart/form-data"`.

Relacija `belongsTo` generiše select polje za izbor povezanog zapisa. Relacija `belongsToMany` generiše višestruki izbor ili listu opcija za povezivanje više zapisa. Prikaz povezanih zapisa koristi `displayName()` metodu kako bi se korisniku prikazala razumljiva vrijednost umjesto samog identifikatora.

Detaljni prikaz zapisa prikazuje vrijednosti polja i povezane zapise. Za slike se generiše umanjeni prikaz, za lozinke se ne prikazuje stvarna vrijednost, a za logička polja se prikazuje čitljiva oznaka da li je vrijednost postavljena.

## 4.14. Generisanje autentifikacije i početnih podataka

Generisani projekat uključuje osnovnu autentifikacionu strukturu. Generator kreira rute za registraciju, prijavu i odjavu, odgovarajuće kontrolere i Blade prikaze. Time se omogućava da generisana aplikacija odmah ima osnovni mehanizam pristupa.

Ako DSL specifikacija sadrži entitet `User`, dodatna polja korisnika uključuju se u registracionu formu, validaciju i seeder. Na primjer, ako korisnik definiše polja `username` ili `role`, generator ih dodaje u korisnički model, migraciju za dopunu tabele `users`, registracioni kontroler i početne podatke.

Generator kreira i `DatabaseSeeder` i `UserSeeder`. Početni korisnik omogućava brzu provjeru generisane aplikacije nakon instalacije. README fajl generisanog projekta sadrži osnovne korake za instalaciju, podešavanje baze, migracije, seedovanje i pokretanje aplikacije.

## 4.15. Pakovanje i preuzimanje rezultata

Nakon što generator kreira izlazni Laravel projekat, direktorijum se pakuje u ZIP arhivu. Pakovanje se vrši rekurzivnim prolaskom kroz izlazni direktorijum i dodavanjem svih fajlova i poddirektorijuma u arhivu. Ako pakovanje uspije, putanja do arhive čuva se u zapisu projekta.

Korisnik na stranici statusa vidi kada je generisanje završeno i dobija dugme za preuzimanje ZIP fajla. Preuzimanje je ograničeno na vlasnika projekta, a aplikacija provjerava da projekat ima status `succeeded` i da ZIP fajl zaista postoji. Time se sprječava preuzimanje nepostojećih ili tuđih projekata.

U slučaju greške korisniku se prikazuje poruka i omogućava ponovno pokretanje generisanja ako ulazni DSL fajl postoji. Ova mogućnost je korisna kada je greška nastala zbog privremenog problema u procesu generisanja ili pakovanja.

## 4.16. Testiranje implementacije

Implementacija sadrži unit i feature testove koji provjeravaju ključne djelove sistema. Unit testovi za parser obuhvataju parsiranje aplikacije, entiteta, polja, funkcionalnosti, display polja, metapodataka i relacija. Takođe se provjeravaju negativni slučajevi, kao što su nepodržani tipovi, nepostojeći ciljni entiteti, nepoznata display polja, neispravni pivot zapisi i kontradiktorni modifikatori.

Feature testovi za generator provjeravaju da li se generiše kompletna Laravel struktura, uključujući osnovne fajlove projekta, modele, kontrolere, migracije, rute, Blade prikaze, autentifikaciju, seedere, testove i README. Posebno se provjerava generisanje relacija, pivot tabela, upload polja, validacionih pravila, display imena i ograničenja definisanih kroz `features`.

Testovi kontrolera generatora provjeravaju da prijavljeni korisnik može pokrenuti generisanje, da neprijavljeni korisnik ne može pristupiti formi, da korisnik vidi samo sopstvene projekte, da može otvoriti projekat za izmjenu, ažurirati specifikaciju i ponovo pokrenuti neuspjelo generisanje.

Ovakva podjela testova odgovara arhitekturi sistema. Parser se provjerava izolovano, generator se provjerava kroz izlazne fajlove, dok se korisnički tokovi provjeravaju feature testovima.

## 4.17. Zaključak poglavlja

U ovom poglavlju opisana je implementacija DSL-a i generatora koda. Prikazana je arhitektura sistema, uloga korisničkog interfejsa, rad sintaksnog analizatora, formiranje internog modela, čuvanje specifikacije, pokretanje procesa generisanja i proizvodnja Laravel komponenti.

Implementirani prototip pokazuje kako se domenski opis aplikacije može transformisati u konkretnu Laravel strukturu. DSL specifikacija se koristi kao formalni ulaz, parser je prevodi u validiran metamodel, a generator na osnovu tog modela proizvodi modele, migracije, kontrolere, rute, prikaze, autentifikaciju, seedere, testne fajlove, dokumentaciju i ZIP arhivu projekta. Time je ostvarena praktična veza između dizajna jezika i funkcionalnog generatora koda.
