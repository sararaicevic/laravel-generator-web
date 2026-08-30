# 6. DISKUSIJA I ZAKLJUČAK

U ovom poglavlju razmatraju se rezultati ostvareni razvojem domenski specifičnog jezika i generatora koda za Laravel aplikacije. Nakon prikaza dizajna, implementacije i evaluacije, potrebno je sagledati širi značaj razvijenog rješenja, njegova ograničenja i moguće pravce budućeg razvoja.

Rezultati rada pokazuju da je primjena DSL pristupa u kontekstu Laravel aplikacija opravdana kada se radi o sistemima zasnovanim na entitetima, poljima, relacijama i standardnim operacijama nad podacima. Takvi sistemi sadrže veliki broj ponavljajućih komponenti, zbog čega su pogodni za formalizaciju i automatsko generisanje.

## 6.1. Diskusija rezultata

Razvijeni sistem pokazuje da se značajan dio početne Laravel aplikacije može opisati deklarativno. Umjesto ručnog pisanja modela, migracija, kontrolera, ruta i prikaza, korisnik definiše osnovne domenske elemente kroz DSL specifikaciju. Parser zatim provjerava i strukturira specifikaciju, a generator proizvodi Laravel projekat koji poštuje definisana pravila.

Najvažniji rezultat rada jeste uspostavljanje jasne veze između domenskog opisa i programskog koda. Svaka DSL konstrukcija ima određeno značenje u generisanom projektu. Entitet se mapira na model, migraciju, kontroler, rutu i direktorijum prikaza. Polje se mapira na kolonu baze podataka, validaciono pravilo i element korisničke forme. Relacija se mapira na Eloquent metode, strane ključeve, pivot tabele i odgovarajuće elemente korisničkog interfejsa.

Ovakav pristup pokazuje prednost domenski specifičnih jezika u oblastima u kojima se mogu prepoznati stabilni obrasci. Laravel aplikacije često imaju predvidljivu strukturu, što omogućava da generator preuzme veliki dio ponavljajućeg rada. Time se programer može usmjeriti na specifičnu poslovnu logiku i prilagođavanje aplikacije, umjesto na ponavljanje osnovnih tehničkih koraka.

Posebno je značajna činjenica da DSL specifikacija postaje centralni izvor istine za početnu strukturu aplikacije. U ručnom pristupu isti domenski element mora biti usklađen na više mjesta: u migraciji, modelu, kontroleru, validaciji, formi i prikazu. Kod DSL pristupa taj element se definiše jednom, a generator ga zatim prenosi kroz sve relevantne slojeve. Time se smanjuje mogućnost da različiti djelovi aplikacije postanu neusklađeni.

## 6.2. Odnos prema postojećim pristupima

Pregled literature pokazuje da se automatizovano generisanje softvera može realizovati kroz različite pristupe: domenski specifične jezike, modelom vođeni razvoj, transformacije modela, generatorske alate i novije pristupe zasnovane na prirodnom jeziku. Rješenje razvijeno u ovom radu pripada grupi DSL pristupa, ali je neposredno prilagođeno Laravel okruženju.

U odnosu na opšte pristupe razvoju DSL-ova, kao što su Xtext i MontiCore, ovaj rad ne razvija univerzalni jezički okvir, već konkretan jezik i generator za jedan jasno određen domen. Takav pristup je uži, ali praktičniji za cilj rada, jer omogućava direktnu vezu između specifikacije i Laravel komponenti.

U odnosu na opšte DSL-ove za web aplikacije, razvijeni jezik je manje opšti, ali bolje usklađen sa Laravel konvencijama. On ne nastoji da bude nezavisan od ciljne platforme, već koristi predvidljivu strukturu Laravel projekata kao osnovu za generisanje. Time se dobija generator koji proizvodi kod bliži stvarnoj ciljnoj tehnologiji.

U odnosu na modelom vođene pristupe za Laravel, razvijeno rješenje koristi tekstualnu DSL specifikaciju i web interfejs umjesto složenijih modelarskih alata. Ovakav pristup smanjuje potrebu za dodatnim alatima i olakšava eksperimentalnu primjenu. S druge strane, modelom vođeni pristupi mogu biti pogodniji za kompleksne sisteme sa većim brojem dijagrama, transformacija i formalnih modela.

## 6.3. Praktični značaj rada

Praktični značaj rada ogleda se u mogućnosti bržeg formiranja početne strukture Laravel aplikacije. Generator može biti koristan u fazi prototipovanja, izrade administrativnih interfejsa, razvoja jednostavnijih poslovnih aplikacija i demonstracije domenskog modela.

U nastavnom kontekstu, ovakav alat može pomoći studentima i početnicima da razumiju vezu između domenskog modela i Laravel komponenti. Kada se iz jedne specifikacije generišu modeli, migracije, kontroleri i prikazi, jasnije se vidi kako se isti koncept pojavljuje u različitim slojevima aplikacije.

U profesionalnom razvoju, generator može smanjiti vrijeme potrebno za inicijalno postavljanje aplikacije. Iako generisani projekat najčešće zahtijeva dalju doradu, početna struktura može biti kreirana brže i konzistentnije nego ručnim pisanjem svih komponenti.

Praktična vrijednost nije samo u brzini generisanja, već i u standardizaciji. Generator uvijek primjenjuje ista pravila imenovanja, validacije i organizacije fajlova. To može biti korisno u timovima ili projektima u kojima je važno održati ujednačen stil osnovnih aplikacionih komponenti.

## 6.4. Naučni i stručni doprinos

Doprinos rada može se posmatrati na nekoliko nivoa. Prvi doprinos je definisanje domenski specifičnog jezika za opis Laravel aplikacija. Jezik obuhvata aplikacije, entitete, polja, tipove podataka, modifikatore, metapodatke, relacije i funkcionalnosti. Time se formira konkretan jezički model za domen Laravel CRUD aplikacija.

Drugi doprinos je implementacija sintaksnog analizatora koji prevodi DSL tekst u strukturirani metamodel. Parser ne obavlja samo sintaksnu obradu, već i semantičku validaciju, izvođenje naziva, provjeru relacija i dodavanje inverznih odnosa. Time se demonstrira praktična realizacija jezičkog procesora u okviru web aplikacije.

Treći doprinos je implementacija generatora koda koji metamodel transformiše u Laravel projekat. Generator proizvodi više povezanih slojeva aplikacije: modele, migracije, kontrolere, rute, prikaze, autentifikaciju, seedere, testove i dokumentaciju. Time se potvrđuje mogućnost primjene DSL-a kao ulaza za generisanje funkcionalne aplikacione strukture.

Četvrti doprinos je evaluacija rješenja kroz testne slučajeve i studiju slučaja. Evaluacija pokazuje da se ključni elementi specifikacije mogu dosljedno prenijeti u generisani Laravel projekat i da se dio tipičnih grešaka može otkriti prije generisanja koda.

## 6.5. Ograničenja razvijenog rješenja

Razvijeno rješenje ima jasno određena ograničenja. Najvažnije ograničenje odnosi se na obim jezika. DSL je namijenjen aplikacijama zasnovanim na podacima i standardnim CRUD operacijama. Složena poslovna pravila, radni tokovi, napredna autorizacija i integracije sa eksternim servisima nijesu obuhvaćeni osnovnom verzijom.

Drugo ograničenje odnosi se na korisnički interfejs generisane aplikacije. Generator proizvodi funkcionalne Blade prikaze, ali DSL ne omogućava detaljno opisivanje rasporeda stranica, dizajn sistema, kompleksnih UI komponenti ili specifičnih interakcija. Takve izmjene zahtijevaju ručnu doradu nakon generisanja projekta.

Treće ograničenje odnosi se na relacije. Podržane su najčešće Eloquent relacije, ali nijesu podržani svi mogući obrasci, kao što su polimorfne relacije, dodatni atributi na pivot tabelama, kompleksni uslovi nad relacijama i napredne strategije brisanja povezanih zapisa.

Četvrto ograničenje odnosi se na zavisnost od Laravel konvencija i verzije okvira. Generator je projektovan za Laravel strukturu i nije neposredno prenosiv na druge razvojne okvire. Promjene u Laravelu mogu zahtijevati prilagođavanje generatora, posebno u dijelu autentifikacije, strukture fajlova i preporučenih obrazaca implementacije.

Peto ograničenje odnosi se na evaluaciju. Rješenje je evaluirano kroz prototip i ograničen skup testnih slučajeva. Potpuna procjena primjenjivosti zahtijevala bi korišćenje sistema u više realnih projekata, sa različitim korisnicima i različitim tipovima aplikacija.

## 6.6. Pravci budućeg razvoja

Prvi pravac budućeg razvoja odnosi se na proširenje jezika novim pravilima i tipovima podataka. Moguće je dodati složenija validaciona pravila, regularne izraze, uslovnu validaciju, lokalizovane formate podataka i posebne tipove za valute, adrese, statuse i druge često korišćene domenske vrijednosti.

Drugi pravac odnosi se na proširenje relacija. Buduća verzija može podržati polimorfne relacije, dodatna polja na pivot tabelama, preciznije definisanje stranih ključeva i pravila ponašanja pri brisanju. Time bi se povećala izražajnost DSL-a i obuhvatio veći broj realnih Laravel scenarija.

Treći pravac odnosi se na autorizaciju. DSL bi mogao omogućiti definisanje korisničkih uloga, dozvola i pravila pristupa po entitetu ili funkcionalnosti. Generator bi na osnovu toga mogao proizvoditi Laravel politike, middleware slojeve i uslove prikaza pojedinih akcija u interfejsu.

Četvrti pravac odnosi se na generisanje naprednijih testova. Trenutno se generišu osnovni testni fajlovi, ali DSL bi se mogao proširiti opisom očekivanog ponašanja, obaveznih scenarija, početnih podataka i pravila za provjeru rezultata. Time bi generator mogao proizvoditi korisnije feature testove za svaki entitet.

Peti pravac odnosi se na kvalitet korisničkog interfejsa. Buduća verzija može uključiti dodatne opcije za raspored polja, grupisanje formi, pretragu, filtriranje, sortiranje, dashboard metrike i prilagođene komponente. Takva proširenja moraju biti pažljivo dizajnirana kako DSL ne bi postao previše složen.

Šesti pravac odnosi se na podršku za druge tehnologije. Pošto je DSL specifikacija odvojena od generatora, moguće je razviti dodatne generatore koji isti metamodel prevode u API aplikaciju, frontend aplikaciju ili drugi backend okvir. Međutim, takvo proširenje zahtijevalo bi jasnije razdvajanje Laravel specifičnih elemenata od opštih domenskih koncepata.

Sedmi pravac odnosi se na integraciju sa razvojnim procesom. Generator bi se mogao povezati sa sistemima za verzionisanje, CI/CD procesima, automatskim pokretanjem testova i validacijom generisanog projekta. Time bi se DSL mogao koristiti ne samo kao alat za prototipovanje, već i kao dio šireg softverskog procesa.

## 6.7. Zaključak

U radu je razvijen domenski specifičan jezik za generisanje Laravel aplikacija i prateći generator koda. Polazni problem odnosio se na veliki stepen ponavljanja pri ručnom kreiranju standardnih Laravel komponenti, kao što su modeli, migracije, kontroleri, rute, validaciona pravila i Blade prikazi. Predloženo rješenje omogućava da se ti elementi definišu na višem nivou apstrakcije, kroz formalnu DSL specifikaciju.

U prvom dijelu rada prikazana je motivacija za primjenu DSL pristupa u razvoju web aplikacija. U drugom dijelu analizirana su postojeća istraživanja iz oblasti domenski specifičnih jezika, modelom vođenog razvoja i automatizovanog generisanja web aplikacija. Na osnovu tog pregleda utvrđeno je da postoji prostor za rješenje koje je neposredno prilagođeno Laravel okruženju i koje omogućava generisanje više međusobno povezanih aplikacionih komponenti iz jedinstvene specifikacije.

U trećem poglavlju definisan je dizajn jezika. Opisani su osnovni koncepti jezika, konkretna sintaksa, tipovi podataka, modifikatori, relacije, funkcionalnosti entiteta, apstraktna sintaksa, metamodel i semantička pravila. Jezik je oblikovan tako da bude dovoljno jednostavan za upotrebu, ali dovoljno izražajan za opis tipičnih Laravel CRUD aplikacija.

U četvrtom poglavlju prikazana je implementacija. Razvijen je parser koji obrađuje DSL specifikaciju, sprovodi validaciju i formira metamodel. Implementiran je generator koji na osnovu metamodela proizvodi Laravel projekat sa modelima, migracijama, kontrolerima, rutama, prikazima, autentifikacijom, početnim podacima, testnim fajlovima i dokumentacijom. Sistem omogućava čuvanje specifikacije, praćenje statusa generisanja i preuzimanje ZIP arhive.

U petom poglavlju sprovedena je evaluacija. Rezultati pokazuju da DSL i generator uspješno pokrivaju osnovni domen rada: entitete, polja, validaciona pravila, relacije i standardne operacije nad podacima. Evaluacija takođe pokazuje da se kroz semantičku validaciju mogu spriječiti određene greške prije generisanja koda i da se jedna specifikacija može dosljedno prenijeti kroz više slojeva Laravel aplikacije.

Na osnovu sprovedenog istraživanja može se zaključiti da je osnovna hipoteza rada potvrđena u okviru definisanog obima. Moguće je implementirati domenski specifičan jezik i generator koda koji na osnovu formalne specifikacije domena generišu funkcionalnu početnu strukturu Laravel aplikacije. Takođe je pokazano da takav pristup može smanjiti količinu ručnog i ponavljajućeg programiranja i povećati konzistentnost generisanih komponenti.

Razvijeni sistem ne predstavlja zamjenu za kompletan razvojni proces, već alat za automatizaciju njegovog početnog i ponavljajućeg dijela. Njegova najveća vrijednost ogleda se u formalizaciji domenskog modela i automatskom prenošenju tog modela kroz standardne Laravel slojeve. Dalji razvoj jezika i generatora može proširiti opseg primjene i omogućiti podršku za složenije aplikacione scenarije.
