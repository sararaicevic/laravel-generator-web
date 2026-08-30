# 5. EVALUACIJA REZULTATA

U ovom poglavlju izvršena je evaluacija razvijenog DSL-a i generatora koda. Cilj evaluacije jeste da se ispita u kojoj mjeri implementirano rješenje ostvaruje ciljeve definisane u uvodu rada: smanjenje ručnog pisanja ponavljajućeg koda, očuvanje konzistentnosti generisanih komponenti i praktična primjenjivost DSL pristupa u razvoju Laravel aplikacija.

Evaluacija je usmjerena na provjeru funkcionalnosti jezika, ispravnosti sintaksne i semantičke obrade, potpunosti generisanog Laravel projekta i ponašanja sistema u tipičnim i neispravnim slučajevima upotrebe. Posebna pažnja posvećena je tome da li se jedna deklarativna specifikacija može pouzdano transformisati u međusobno usklađene modele, migracije, kontrolere, rute i korisničke prikaze.

## 5.1. Ciljevi evaluacije

Evaluacija je sprovedena sa nekoliko povezanih ciljeva. Prvi cilj je provjera izražajnosti DSL-a, odnosno mogućnosti jezika da opiše tipične strukture Laravel aplikacije zasnovane na entitetima, poljima, validacionim pravilima i relacijama.

Drugi cilj je provjera ispravnosti parsiranja. Parser treba da prihvati ispravne specifikacije i da odbaci neispravne specifikacije uz jasnu poruku greške. Ovo je značajno jer je parser prvi sloj zaštite od generisanja neispravnog koda.

Treći cilj je provjera kvaliteta generisanog projekta. Generisani projekat treba da ima očekivanu Laravel strukturu, pravilno imenovane fajlove i klase, validne migracije, odgovarajuće kontrolere, rute i Blade prikaze.

Četvrti cilj je provjera konzistentnosti između različitih slojeva generisane aplikacije. Polje definisano u DSL-u treba da bude dosljedno zastupljeno u migraciji, modelu, validaciji, formi, listi i detaljnom prikazu. Isto važi i za relacije, koje treba da budu usklađene u modelima, migracijama, kontrolerima i prikazima.

Peti cilj je procjena praktične vrijednosti rješenja. Evaluacija treba da pokaže da DSL i generator mogu smanjiti količinu ručnog rada u odnosu na tradicionalno kreiranje istih komponenti u Laravel aplikaciji.

## 5.2. Metod evaluacije

Evaluacija je sprovedena kombinovanjem analize funkcionalnosti, testnih slučajeva i studije slučaja. Analiza funkcionalnosti obuhvata pregled mogućnosti jezika i poređenje DSL konstrukcija sa generisanim Laravel komponentama. Testni slučajevi obuhvataju provjeru parsera, generatora i korisničkih tokova u aplikaciji. Studija slučaja koristi konkretnu DSL specifikaciju i posmatra izlaz koji generator proizvodi.

Evaluacija obuhvata sljedeće nivoe:

- sintaksni nivo, koji provjerava strukturu DSL specifikacije;
- semantički nivo, koji provjerava smislenost entiteta, polja, pravila i relacija;
- generatorski nivo, koji provjerava izlazne Laravel fajlove;
- aplikacioni nivo, koji provjerava korisnički tok kreiranja, izmjene, pregleda statusa i preuzimanja projekta.

Za potrebe evaluacije korišćeni su testovi koji se nalaze u projektu. Unit testovi provjeravaju ponašanje parsera, dok feature testovi provjeravaju ponašanje generatora i web toka. Pored toga, izvršena je ručna analiza generisanih komponenti u odnosu na definisana pravila mapiranja.

## 5.3. Testna specifikacija

Kao osnovni primjer za evaluaciju koristi se specifikacija aplikacije za upravljanje inventarom. Ova aplikacija sadrži više entiteta, različite tipove polja, validaciona pravila i relacije jedan-prema-više i više-prema-više. Time se obuhvataju najvažnije mogućnosti jezika.

Primjer testne specifikacije:

```text
app InventorySystem {
  entity Category {
    display: title
    title: string required unique
    description: text nullable
    hasMany Product
  }

  entity Product {
    features: index create edit show delete
    display: name
    name: string required minLength=3 maxLength=120
    sku: string required unique
    description: text nullable
    price: decimal required min=0 step=0.01
    active: boolean
    belongsTo Category
    belongsToMany Tag pivot product_tag
  }

  entity Tag {
    name: string required unique
  }
}
```

Ova specifikacija je pogodna za evaluaciju jer uključuje više aspekata jezika. Entitet `Product` koristi tekstualna, numerička i logička polja, metapodatke, jedinstvenost, obaveznost, display polje, relaciju prema kategoriji i relaciju prema oznakama. Entitet `Category` pokazuje relaciju jedan-prema-više, dok entitet `Tag` učestvuje u relaciji više-prema-više.

Na osnovu ove specifikacije očekuje se generisanje modela `Product`, `Category` i `Tag`, migracija za njihove tabele, migracije za strane ključeve i pivot tabelu, kontrolera, resursnih ruta i korisničkih prikaza za upravljanje zapisima.

## 5.4. Prikaz rezultata generisanja

Da bi se rezultat DSL-a prikazao jasno, u evaluaciji je potrebno ilustrovati šta korisnik dobija nakon pokretanja generatora. Pošto cilj rada nije prikazivanje izvornog koda liniju po liniju, rezultat se prikazuje kroz strukturu generisanog projekta, ekrane generisane aplikacije i opis veze između DSL ulaza i dobijenih komponenti.

Na osnovu testne specifikacije iz prethodnog potpoglavlja generator proizvodi Laravel aplikaciju sa entitetima `Product`, `Category` i `Tag`. Rezultat obuhvata modele, migracije, kontrolere, rute, Blade prikaze, autentifikaciju, početne podatke, osnovne testne fajlove i README dokument. Time se pokazuje da se jedna DSL specifikacija transformiše u kompletnu početnu aplikacionu strukturu.

Predloženi prikazi rezultata su:

**Slika 5.1. Struktura generisanog Laravel projekta**  
Na slici treba prikazati direktorijume i fajlove koji nastaju nakon raspakivanja ZIP arhive. Dovoljno je prikazati ključne djelove, kao što su `app/Models`, `app/Http/Controllers`, `database/migrations`, `routes` i `resources/views`.

**Slika 5.2. Početni ekran generisane aplikacije nakon prijave**  
Na slici treba prikazati dashboard generisane aplikacije, gdje se vidi da aplikacija ima osnovnu navigaciju i pristup generisanim entitetima.

**Slika 5.3. Lista generisanih zapisa za entitet Product**  
Na slici treba prikazati tabelarni prikaz proizvoda. Ova slika ilustruje rezultat `index` funkcionalnosti iz DSL specifikacije.

**Slika 5.4. Forma za kreiranje ili izmjenu proizvoda**  
Na slici treba prikazati generisanu formu sa različitim tipovima polja: tekstualno polje, numeričko polje, checkbox, upload polje i izbor povezane kategorije. Ova slika pokazuje kako se tipovi i pravila iz DSL-a prenose u korisnički interfejs.

**Slika 5.5. Detaljni prikaz proizvoda i povezani zapisi**  
Na slici treba prikazati `show` ekran, uključujući vrijednosti polja, pripadajuću kategoriju i povezane oznake. Ova slika ilustruje rezultat relacija definisanih u DSL-u.

Pored slika, rezultat se može prikazati i tabelom koja povezuje DSL ulaz sa vidljivim izlazom:

| DSL element | Vidljivi rezultat u generisanoj aplikaciji |
| --- | --- |
| `entity Product` | Navigaciona stavka, model proizvoda, rute i ekrani za proizvode |
| `name: string required` | Obavezno tekstualno polje u formi i kolona u tabeli |
| `price: decimal required min=0` | Numeričko polje sa validacijom i prikazom decimalne vrijednosti |
| `active: boolean` | Checkbox u formi i čitljiva oznaka u prikazu |
| `belongsTo Category` | Select polje za izbor kategorije i prikaz povezane kategorije |
| `belongsToMany Tag` | Izbor više oznaka i prikaz povezanih oznaka |
| `features: index create edit show delete` | Lista, forma za kreiranje, forma za izmjenu, detaljni prikaz i akcija brisanja |

Ovakav način prikaza rezultata omogućava da se evaluacija zasniva na stvarnom ponašanju sistema, bez opterećivanja rada izvornim kodom. Čitalac može vidjeti ulaz na DSL jeziku, razumjeti osnovne koncepte jezika i zatim vidjeti kako se taj ulaz manifestuje u generisanoj Laravel aplikaciji.

## 5.5. Evaluacija sintaksne obrade

Sintaksna obrada provjerava da li parser pravilno prepoznaje strukturu DSL dokumenta. U ispravnim slučajevima parser treba da prepozna naziv aplikacije, entitete, polja, relacije, funkcionalnosti i display polja. U neispravnim slučajevima parser treba da vrati grešku i zaustavi obradu.

Testovima su obuhvaćeni sljedeći pozitivni slučajevi:

- parsiranje aplikacije sa jednim entitetom;
- parsiranje više entiteta;
- parsiranje polja sa tipovima i modifikatorima;
- parsiranje `features` deklaracije;
- parsiranje `display` deklaracije;
- parsiranje metapodataka sa vrijednostima koje sadrže razmake;
- parsiranje relacija `belongsTo`, `hasMany`, `hasOne` i `belongsToMany`;
- parsiranje relacije više-prema-više sa podrazumijevanom i eksplicitnom pivot tabelom.

Rezultat ove provjere pokazuje da sintaksni analizator može obraditi osnovne konstrukcije jezika i pretvoriti ih u strukturirani model. Posebno je značajno to što parser ne zavisi od redosljeda svih elemenata unutar entiteta, već svaku liniju klasifikuje prema obliku. Time se korisniku ostavlja određena sloboda pri organizaciji specifikacije.

Sintaksna obrada je jednostavna i pogodna za trenutni obim jezika. Pošto jezik koristi blokove i linijski orijentisane deklaracije, implementacija parsera može ostati relativno pregledna. Ovo predstavlja prednost u prototipskoj fazi, jer omogućava lakše održavanje i brže proširivanje jezika.

## 5.6. Evaluacija semantičke validacije

Semantička validacija provjerava da li je specifikacija smislen opis aplikacije. Ovaj dio evaluacije posebno je značajan jer sintaksno ispravan tekst ne mora biti dovoljno dobar za generisanje ispravnog projekta.

Testovima i analizom obuhvaćeni su sljedeći negativni slučajevi:

- relacija prema nepostojećem entitetu;
- nepodržan tip polja;
- display polje koje nije definisano u entitetu;
- pivot tabela navedena kod relacije koja nije `belongsToMany`;
- polje koje je istovremeno `required` i `nullable`;
- `unique` modifikator nad opcionim poljem;
- `unique` modifikator nad tipom koji nije pogodan za indeksiranje.

U svim navedenim slučajevima očekivano ponašanje sistema je odbacivanje specifikacije prije faze generisanja. Time se izbjegava kreiranje projekta koji bi sadržao nepostojeće klase, neispravne migracije, pogrešna validaciona pravila ili kontradiktorne zahtjeve.

Semantička validacija doprinosi pouzdanosti sistema jer uspostavlja granicu između korisničkog unosa i generatora. Generator može da pretpostavi da je ulazni model konzistentan, dok parser preuzima odgovornost za provjeru pravila jezika. Ovakvo razdvajanje olakšava održavanje implementacije i smanjuje mogućnost grešaka u izlaznom kodu.

## 5.7. Evaluacija generisanih modela

Generisani Eloquent modeli predstavljaju osnovu aplikacionog sloja za rad sa podacima. Evaluacijom je provjereno da li generator za svaki entitet proizvodi odgovarajuću PHP klasu, da li uključuje popunjive atribute, kastovanja i relacione metode.

Za entitet `Product` očekuje se model `Product.php`, dok se za entitete `Category` i `Tag` očekuju modeli `Category.php` i `Tag.php`. Nazivi klasa se direktno izvode iz naziva entiteta, što omogućava dosljedno mapiranje između DSL-a i Laravel strukture.

Polja entiteta ulaze u listu popunjivih atributa, osim sistemskih polja kojima upravlja Laravel. Kod polja posebnih tipova generišu se kastovanja. Na primjer, logička polja se kastuju u `boolean`, datumska polja u `date` ili `datetime`, decimalna polja u decimalni format, JSON polja u niz, a lozinke dobijaju hash obradu.

Relacije se generišu kao Eloquent metode. Za `belongsTo Category` u modelu `Product` očekuje se metoda `category()`. Za `hasMany Product` u modelu `Category` očekuje se metoda `products()`. Za `belongsToMany Tag` očekuju se metode na obje strane relacije i veza preko pivot tabele.

Rezultat evaluacije pokazuje da se domenski odnosi iz DSL specifikacije prenose u objektni model generisane aplikacije. Time se potvrđuje da generator ne proizvodi samo tabele i forme, već i semantičku povezanost modela.

## 5.8. Evaluacija generisanih migracija

Migracije su provjerene kroz odnos između DSL tipova i kolona baze podataka. Svako polje iz specifikacije treba da bude prevedeno u odgovarajući tip kolone. Na primjer, `string` se prevodi u tekstualnu kolonu kraće dužine, `text` u kolonu za duži sadržaj, `decimal` u decimalnu kolonu, `boolean` u logičku kolonu, a `json` u JSON kolonu.

Evaluacijom je utvrđeno da modifikatori utiču na migracije na očekivan način. Opciono polje dobija mogućnost `nullable`, dok obavezno polje ne dobija taj modifikator. Polja označena kao `unique` dobijaju jedinstveni indeks kada je tip polja pogodan za takvo ograničenje.

Kod relacije `belongsTo` generiše se kolona stranog ključa i posebna migracija za dodavanje ograničenja. Na primjer, veza proizvoda prema kategoriji proizvodi kolonu `category_id` u tabeli `products` i ograničenje stranog ključa prema tabeli `categories`.

Kod relacije `belongsToMany` generiše se pivot tabela. U primjeru veze između proizvoda i oznaka generiše se tabela `product_tag`, sa kolonama `product_id` i `tag_id`, stranim ključevima i vremenskim oznakama.

Ovakvi rezultati pokazuju da se struktura baze podataka može izvesti iz DSL specifikacije bez ručnog pisanja migracija. Posebno je značajno što se relacije i ograničenja baze izvode konzistentno sa Eloquent metodama.

## 5.9. Evaluacija kontrolera, ruta i validacije

Kontroleri su evaluirani kroz provjeru generisanih metoda, validacionih pravila i obrade relacija. Za entitete sa svim uključenim funkcionalnostima generator proizvodi standardni skup metoda: `index`, `create`, `store`, `show`, `edit`, `update` i `destroy`. Ako se kroz `features` definiše samo dio funkcionalnosti, generišu se samo potrebne metode.

Rute se generišu kao resursne rute. Ako entitet ima samo `index` i `show`, ruta se ograničava na te metode. Time se izbjegava izlaganje akcija koje nijesu predviđene specifikacijom. Ovo pokazuje da `features` deklaracija utiče na više slojeva aplikacije: rute, kontroler i prikaze.

Validacija se izvodi iz tipova, modifikatora i metapodataka. Na primjer, `required` postaje pravilo obaveznosti, `unique` postaje pravilo jedinstvenosti, `minLength` i `maxLength` postaju ograničenja dužine, a `options` kod `enum` polja postaje skup dozvoljenih vrijednosti. Kod upload polja koriste se pravila za fajlove i slike.

Relacije se obrađuju kroz kontroler. Za `belongsTo` se validira identifikator povezanog zapisa i čuva strani ključ. Za `belongsToMany` se validira niz povezanih identifikatora i sinhronizuje pivot tabela. Time se potvrđuje da generator povezuje definiciju relacije sa stvarnim ponašanjem aplikacije.

## 5.10. Evaluacija korisničkih prikaza

Blade prikazi su evaluirani kroz provjeru generisanih ekrana i elemenata forme. Za svaki entitet, u zavisnosti od izabranih funkcionalnosti, generator proizvodi prikaze za listu, kreiranje, izmjenu i detaljni prikaz.

U prikazu liste generišu se kolone na osnovu polja entiteta, akcije za dostupne operacije i paginacija. Ako je za entitet isključeno brisanje, u listi nema akcije za brisanje. Ako je isključena izmjena, nema linka ka edit formi. Ovim se potvrđuje konzistentnost između deklaracije `features` i korisničkog interfejsa.

Forme koriste odgovarajuće HTML elemente prema tipu polja. `string`, `email`, `url`, `phone` i slični tipovi koriste tekstualni unos, `text` koristi višeredni unos, `boolean` koristi checkbox, `date` i `datetime` koriste specijalizovane unose za datum i vrijeme, `enum` koristi izbor ponuđene vrijednosti, a `file` i `image` koriste upload polje.

Relacija `belongsTo` u formama se prikazuje kroz select element, dok se relacija `belongsToMany` prikazuje kroz izbor više povezanih zapisa. Time se relacije iz DSL-a prenose i u korisnički interfejs, ne samo u modele i migracije.

Detaljni prikazi prikazuju vrijednosti polja i povezane zapise. Za lozinke se ne prikazuje stvarna vrijednost, za slike se prikazuje umanjeni prikaz, a za logičke vrijednosti se koristi čitljiva oznaka. Ovakvo ponašanje doprinosi upotrebljivosti generisane aplikacije.

## 5.11. Evaluacija korisničkog toka generatora

Pored generisanog projekta, evaluiran je i sam tok korišćenja generatora. Prijavljeni korisnik može otvoriti formu za kreiranje projekta, unijeti naziv aplikacije, definisati entitete i pokrenuti generisanje. Nakon toga se prikazuje stranica statusa, a po završetku procesa korisnik može preuzeti ZIP arhivu.

Sistem ograničava pristup generatoru na prijavljene korisnike. Neprijavljeni korisnici se preusmjeravaju na stranicu za prijavu. Pored toga, korisnik može vidjeti samo svoje projekte, što je značajno za osnovnu izolaciju korisničkih podataka.

Korisnik može otvoriti postojeći projekat za izmjenu. Tada se iz sačuvanih entiteta, polja i relacija formira početno stanje builder interfejsa. Nakon izmjene specifikacije prethodni izlaz se briše iz zapisa projekta, status se vraća na `queued` i generisanje se pokreće ponovo.

Ako generisanje ne uspije, korisniku se prikazuje poruka greške. Ako ulazni DSL fajl postoji, moguće je ponovo pokrenuti generisanje. Ovaj tok je važan jer podržava iterativni rad nad specifikacijom.

## 5.12. Stepen automatizacije

Jedan od najvažnijih rezultata evaluacije jeste stepen automatizacije koji DSL i generator ostvaruju. Na osnovu jedne specifikacije generiše se veliki broj međusobno povezanih fajlova. Za svaki entitet ručno bi bilo potrebno kreirati model, migraciju, kontroler, rute, forme, listu, detaljni prikaz i dodatnu obradu relacija.

Kod aplikacije sa tri entiteta, kao što su `Product`, `Category` i `Tag`, ručni rad bi obuhvatio najmanje tri modela, više migracija, tri kontrolera, više Blade prikaza, resursne rute, validaciona pravila i ručnu provjeru usklađenosti odnosa. DSL pristup omogućava da se sve ove komponente izvedu iz jednog centralnog opisa.

Automatizacija je posebno značajna kod promjena. Ako se promijeni tip polja, doda relacija ili ograniči skup funkcionalnosti, izmjena se unosi u DSL specifikaciju, a generator proizvodi usklađene izlaze. U ručnom pristupu ista promjena često zahtijeva izmjene na više mjesta, što povećava mogućnost greške.

Tabela 5.1 prikazuje poređenje ručnog i DSL pristupa na nivou zadataka.

| Zadatak | Ručni Laravel pristup | DSL pristup |
| --- | --- | --- |
| Definisanje entiteta | Pisanje modela i migracije | Jedna `entity` deklaracija |
| Definisanje polja | Izmjena migracije, modela, forme i validacije | Jedna linija polja u DSL-u |
| Definisanje relacije | Izmjena više modela, migracija, kontrolera i prikaza | Jedna relacijska deklaracija |
| Ograničavanje ekrana | Ručno uklanjanje ruta, metoda i linkova | `features` deklaracija |
| Validacija | Ručno pisanje pravila | Izvođenje iz tipa, modifikatora i metapodataka |
| Dokumentacija projekta | Ručno pisanje osnovnih uputstava | Automatski generisan README |

Ovo poređenje pokazuje da DSL pristup ne eliminiše potrebu za daljim razvojem aplikacije, ali značajno smanjuje količinu početnog i ponavljajućeg rada.

## 5.13. Kvalitativna analiza rezultata

Kvalitativna analiza pokazuje nekoliko prednosti razvijenog rješenja. Prva prednost je centralizacija opisa aplikacije. DSL specifikacija predstavlja jedno mjesto na kojem se definišu ključni elementi domena. Zbog toga se smanjuje rizik da model, migracija, forma i validacija opisuju različite verzije istog entiteta.

Druga prednost je dosljednost imenovanja. Pošto se nazivi tabela, ruta, promjenljivih i kolekcija izvode automatski, generator može održati konzistentan stil kroz cijeli projekat. Ovo je posebno korisno kod većeg broja entiteta.

Treća prednost je ponovljivost. Ista specifikacija može se ponovo parsirati i generisati isti tip izlaza. Time se DSL može koristiti kao stabilan ulaz za eksperimentisanje, testiranje i iterativni razvoj.

Četvrta prednost je bolja kontrola nad tipičnim greškama. Semantička validacija sprječava dio grešaka prije nego što se uopšte dođe do Laravel koda. Na primjer, relacija prema nepostojećem entitetu ili nedozvoljena jedinstvenost polja odbacuje se u parseru.

Uočena ograničenja odnose se na složenije aplikacione scenarije. DSL trenutno nije namijenjen opisivanju kompleksne poslovne logike, napredne autorizacije, specifičnih UI rasporeda ili integracija sa eksternim servisima. Generisani projekat predstavlja funkcionalnu osnovu, ali se u realnim projektima očekuje dodatni ručni razvoj.

## 5.14. Odnos prema hipotezama rada

Prva hipoteza rada glasi da je moguće implementirati domenski specifičan jezik i generator koda koji automatski generiše osnovnu strukturu Laravel aplikacije na osnovu formalno definisane specifikacije domena. Rezultati evaluacije podržavaju ovu hipotezu, jer implementirani sistem prihvata DSL specifikaciju, parsira je, formira metamodel i generiše Laravel komponente.

Druga hipoteza glasi da je moguće generisati funkcionalnu Laravel aplikaciju koja obezbjeđuje konzistentnost između definisanih podataka, odnosa i pravila, čime se smanjuje potreba za ručnim kodiranjem. Evaluacija pokazuje da se polja, tipovi, validaciona pravila i relacije prenose kroz više slojeva aplikacije. Ista DSL deklaracija utiče na migraciju, model, kontroler, rutu i prikaz, što potvrđuje konzistentnost generisanja.

Hipoteze se mogu smatrati potvrđenim u okviru obima koji je definisan ovim radom. Potvrda se odnosi na početnu strukturu Laravel aplikacije i standardne operacije nad podacima. Ona se ne odnosi na sve moguće Laravel funkcionalnosti niti na složene poslovne sisteme koji zahtijevaju dodatno ručno programiranje.

## 5.15. Ograničenja evaluacije

Evaluacija je sprovedena nad prototipom i ograničenim skupom testnih scenarija. Iako testni slučajevi pokrivaju najvažnije dijelove jezika i generatora, šira validacija zahtijevala bi primjenu sistema na većem broju realnih projekata i u različitim domenima.

Drugo ograničenje odnosi se na obim DSL-a. Pošto je jezik usmjeren na entitete, polja, relacije i osnovne CRUD funkcionalnosti, evaluacija se takođe kreće u tim granicama. Rezultati ne mogu biti direktno preneseni na aplikacije koje zahtijevaju složenu poslovnu logiku, napredne tokove rada ili specifične korisničke interfejse.

Treće ograničenje odnosi se na poređenje sa ručnim razvojem. U ovom radu poređenje je primarno kvalitativno i zasniva se na broju zadataka koje generator automatizuje. Precizno kvantitativno poređenje vremena razvoja zahtijevalo bi kontrolisani eksperiment sa više učesnika i više zadataka.

Četvrto ograničenje odnosi se na ciljnu tehnologiju. Generator je prilagođen Laravel konvencijama i nije evaluiran za druge razvojne okvire. To znači da se rezultati odnose na Laravel okruženje i ne treba ih neposredno generalizovati na druge platforme.

## 5.16. Zaključak poglavlja

Evaluacija pokazuje da razvijeni DSL i generator koda ostvaruju osnovne ciljeve rada u definisanom obimu. DSL omogućava opis aplikacije kroz entitete, polja, tipove, modifikatore, relacije i funkcionalnosti. Parser prihvata ispravne specifikacije i odbacuje neispravne, dok generator proizvodi međusobno usklađene Laravel komponente.

Rezultati potvrđuju da se znatan dio početnog Laravel razvoja može automatizovati na osnovu formalne specifikacije domena. Generisani projekat obuhvata modele, migracije, kontrolere, rute, korisničke prikaze, autentifikaciju, seedere, testne fajlove, dokumentaciju i ZIP arhivu. Time se smanjuje količina ponavljajućeg rada i povećava konzistentnost aplikacione strukture.

Istovremeno, evaluacija pokazuje da razvijeni sistem ima jasno određene granice. On je najpogodniji za aplikacije zasnovane na upravljanju podacima i standardnim operacijama nad zapisima, dok složeniji aplikacioni zahtjevi ostaju predmet daljeg razvoja i ručne nadogradnje.
