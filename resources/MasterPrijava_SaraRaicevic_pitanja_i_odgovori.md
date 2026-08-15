# Prijava teme master rada — pitanja i odgovori

> Tekst je izdvojen iz dokumenta od strane 3 do strane 13. Uklonjeni su prelomi redova nastali rasporedom teksta u tabelama, bez sadržajnih izmjena odgovora.

## 1. Naslov rada

### Pitanje

Koji je naslov rada?

*Uputstvo iz obrasca: Tema mora biti aktuelna, nova, a naslov treba precizno da odražava cilj i predmet istraživanja.*

### Odgovor

**Razvoj domenski specifičnog jezika (DSL) za generisanje Laravel aplikacija**

---

# I UVOD

## 2. Obrazloženje naziva rada

### Pitanje

Kako se obrazlažu naziv rada, aktuelnost i primjerenost predložene teme? *(≤ 1200 karaktera)*

*Uputstvo iz obrasca: Argumentovanim naučnim stilom obrazložiti aktuelnost i primjerenost predložene teme.*

### Odgovor

Naziv master rada „Razvoj domenski specifičnog jezika (DSL) za generisanje Laravel aplikacija“ izabran je s ciljem da precizno odrazi fokus istraživanja koje se odnosi na primjenu koncepta domenski specifičnih jezika u kontekstu automatizovanog razvoja web aplikacija. Savremeni razvoj softverskih sistema karakterišu zahtjevi za povećanjem efikasnosti, konzistentnosti i prilagodljivosti, što dovodi do potrebe za formalizovanim pristupima koji omogućavaju generisanje koda na osnovu apstraktnih specifikacija domena. Laravel framework predstavlja jedno od dominantnih rješenja u PHP okruženju, ali proces ručnog definisanja modela, kontrolera i korisničkih interfejsa i dalje zahtjeva značajan nivo tehničkog angažmana. Korišćenjem DSL-a omogućava se definisanje domena putem deklarativnih opisa, koje sistem automatski prevodi u funkcionalnu Laravel aplikaciju spremnu za upotrebu. Time se ostvaruje racionalizacija razvoja, standardizacija arhitekture i smanjenje mogućnosti grešaka, dok tema doprinosi istraživanjima u oblasti metaprogramiranja i automatizovanog generisanja koda.

## 3. Predmet istraživanja

### Pitanje

Šta je predmet istraživanja? *(≤ 1200 karaktera)*

*Uputstvo iz obrasca: Koncizno obrazložiti predmet istraživanja.*

### Odgovor

Predmet istraživanja ovog rada obuhvata dizajn i implementaciju domenski specifičnog jezika (DSL) namijenjenog automatizovanom generisanju Laravel aplikacija. Istraživanje se fokusira na izradu formalne specifikacije jezika koja omogućava korisnicima da, kroz deklarativni opis domena, struktura podataka i odnosa, automatski generišu potpunu aplikaciju. Ključni aspekt predstavlja definisanje sintakse i semantike DSL-a, kao i razvoj generatora koda koji prevodi te specifikacije u funkcionalne komponente Laravel okvira – modele, kontrolere, rute i prikaze. Posebna pažnja posvećena je unapređenju efikasnosti, standardizacije i ponovljivosti procesa razvoja. Istraživanje ima za cilj da prikaže mogućnosti DSL pristupa u domenu web razvoja, te da potvrdi njegovu praktičnu primjenjivost i doprinos automatizaciji procesa izrade aplikacija u savremenom softverskom inženjerstvu.

## 4. Motiv i cilj istraživanja

### Pitanje

Koji su motiv, svrha i glavni ciljevi istraživanja? *(≤ 4000 karaktera)*

*Uputstvo iz obrasca: Jasno i nedvosmisleno definisati razloge, svrhu i glavne ciljeve u procesu istraživanja.*

### Odgovor

Motiv istraživanja proističe iz sve veće potrebe za automatizacijom i optimizacijom procesa razvoja web aplikacija, naročito u kontekstu ubrzanog tehnološkog napretka i rastućih zahtjeva tržišta za bržom isporukom softverskih rješenja. Tradicionalni pristup razvoju aplikacija često podrazumijeva ponavljanje sličnih struktura i funkcionalnosti, što produžava razvojni ciklus i povećava mogućnost grešaka. Laravel, kao jedan od najzastupljenijih PHP frameworka, omogućava modularan i skalabilan pristup izradi aplikacija, ali i dalje zahtijeva značajno vrijeme i stručno znanje za definisanje modela, kontrolera, ruta i prikaza. Ova činjenica ukazuje na potrebu za alatima koji omogućavaju automatizovano generisanje takvih struktura uz zadržavanje fleksibilnosti i kvaliteta koda. Dodatnu motivaciju predstavlja porast interesovanja za primjenu domenski specifičnih jezika (DSL) u razvoju softvera, koji omogućavaju viši nivo apstrakcije i formalizacije procesa definisanja poslovne logike. Primjenom DSL pristupa moguće je omogućiti korisnicima da kroz jednostavne deklarativne opise definišu zahtjeve aplikacije, dok sistem automatski generiše funkcionalan kod u skladu sa Laravel arhitekturom. Na taj način se značajno smanjuje vrijeme razvoja, povećava konzistentnost koda i unapređuje dostupnost alata i korisnicima bez naprednog tehničkog znanja.

Glavni cilj istraživanja jeste dizajn i implementacija domenski specifičnog jezika koji omogućava automatizovano generisanje Laravel aplikacija, pri čemu se DSL koristi kao interfejs između apstraktne specifikacije domena i izvršnog koda. U skladu sa tim, definišu se sljedeći posebni ciljevi istraživanja:

1. **Analiza postojećih rješenja**  
   Istražiti postojeće alate i pristupe generisanju web aplikacija, kao i srodna istraživanja iz oblasti DSL-a i automatizovanog razvoja softvera, s posebnim fokusom na njihovu primjenjivost u Laravel okruženju.

2. **Definisanje formalne specifikacije DSL-a**  
   Jasno definisati sintaksu, semantiku i strukturu jezika, uz poštovanje principa čitljivosti, proširivosti i konzistentnosti s Laravel standardima.

3. **Implementacija generatora koda**  
   Razviti mehanizam koji prevodi DSL specifikacije u potpunu Laravel aplikaciju, uključujući generisanje modela, kontrolera, ruta, prikaza i pripadajućih konfiguracija.

4. **Izrada web interfejsa za definisanje specifikacija**  
   Kreirati intuitivno korisničko okruženje koje omogućava korisnicima da kroz interaktivni unos definišu entitete, odnose i funkcionalnosti, te preuzmu generisani projekat.

5. **Evaluacija efikasnosti i upotrebljivosti rješenja**  
   Uporediti performanse, vrijeme razvoja i kvalitet koda između tradicionalnog pristupa i onog zasnovanog na DSL-u, kako bi se potvrdila opravdanost i praktična vrijednost predloženog sistema.

Svrha istraživanja ogleda se u razvoju inovativnog alata koji povezuje teorijske principe domenski specifičnih jezika sa praktičnim aspektima razvoja Laravel aplikacija. Očekuje se da rezultati istraživanja doprinesu unapređenju pristupa automatizovanom generisanju softvera, racionalizaciji procesa razvoja i povećanju dostupnosti kvalitetnih web rješenja u različitim domenima primjene.

---

# II PREGLED DOSADAŠNJIH ISTRAŽIVANJA IZ NAVEDENE OBLASTI

## 5. Pregled dosadašnjih istraživanja

### Pitanje

Kakvo je stanje dosadašnjih istraživanja u oblasti koja je povezana sa predmetom rada? *(≤ 6000 karaktera)*

*Uputstvo iz obrasca: Pozvati se na najmanje 10 primarnih referenci na kojima se istraživanje bazira, od toga minimum 5 iz posljednjih 10 godina. Pregled dosadašnjih istraživanja je narativan. Prikazati stanje u oblasti nauke u vezi sa predmetom istraživanja.*

### Odgovor

Savremena istraživanja potvrđuju da domenski specifični jezici (DSL) doprinose automatizaciji razvoja i boljoj standardizaciji softverskih sistema.

U radu [1] detaljno su opisani praktični koraci izgradnje jezika, od definisanja gramatike i semantike do implementacije generatora koda i alata za razvoj. Ovaj rad uspostavlja čvrst metodološki okvir koji se može primijeniti i na dizajn DSL-a za web aplikacije.

Rad [2] prikazuje okvir za kompozicioni razvoj jezika i modularni inženjering, dok rad [3], kroz studiju slučaja WebDSL pokazuje da dobro definisana apstraktna sintaksa i integrisani alatni lanci omogućavaju razvoj kompleksnih web aplikacija iz modela visokog nivoa apstrakcije.

U radu [4] analizirani su ključni aspekti implementacije DSL-ova, s naglaskom na balansiranje između izražajne moći jezika i jednostavnosti njegove upotrebe. Rad [5] dodatno argumentuje da uspjeh DSL-a u praksi zavisi od troškova njegove izgradnje i održavanja, te da je isplativost ostvariva kroz automatizaciju alata i generatora koda.

Novija istraživanja rada [6] i [7] ističu da održavanje, verzionisanje i integracija sa postojećim sistemima predstavljaju ključne izazove za dugoročni uspjeh. Posebno, analiza više od hiljadu javnih projekata zasnovanih na Xtext-u [7] pokazala je da se DSL-ovi najčešće koriste u domenima koji zahtijevaju visoku automatizaciju i pouzdano generisanje koda, što potvrđuje njihovu relevantnost za web okruženja.

U oblasti web razvoja i PHP ekosistema, značajan doprinos daju radovi koji povezuju model-vođeni inženjering (MDA) i generisanje Laravel aplikacija. Rad [8] prikazuje MDA pristup u kojem se UML dijagrami koriste kao ulazni modeli, a proces transformacije PIM-PSM omogućava automatsko generisanje Laravel modela, kontrolera, ruta i prikaza. Ovakav metod smanjuje potrebu za manuelnim kodiranjem i povećava konzistentnost sistema.

Rad [9] predlaže sličan pristup zasnovan na definisanju Ecore metamodela iz kojeg se putem ATL transformacija generiše kod za Laravel aplikacije u domenu e-commerce rješenja. Iako je fokus njihovog rada specifičan, metodološki okvir modeliranja i generisanja može se univerzalno primijeniti i u drugim web sistemima. Rad [10] dodatno unapređuje ovaj pristup kroz deklarativne YAML specifikacije prevedene u Laravel kod pomoću blueprint mehanizma, što potvrđuje značaj DSL-ova i deklarativnih definicija sistema u automatizaciji web razvoja.

Dodatna istraživanja u oblasti DSL-ova i automatizovanog razvoja web aplikacija dodatno proširuju postojeće pristupe. Rad [11] predstavlja domenski specifičan jezik za generisanje web aplikacija kroz višeslojno modelovanje, čime se potvrđuje da DSL pristupi omogućavaju bržu izradu i lakšu prilagodljivost aplikacija različitim domenima.

Rad [12] fokusira se na upotrebu velikih jezičkih modela (LLM) u model-vođenom inženjeringu i predlažu način kako prirodni jezik može biti integrisan u procese generisanja modela i koda, što predstavlja savremeni trend u razvoju DSL alata.

U radu [13] predlaže se DSL-bazirani pristup za generisanje i upravljanje procesom navigacije jednostraničnih aplikacija (SPA), naglašavajući prednosti modularnosti i održivosti u procesu razvoja web sistema.

---

# III HIPOTEZA/ISTRAŽIVAČKO PITANJE

## 6. Hipoteze istraživanja

### Pitanje

Koje su hipoteze i/ili istraživačka pitanja sa obrazloženjem? *(≤ 2400 karaktera)*

*Uputstvo iz obrasca: Jasno definisati hipotezu/e i/ili istraživačka pitanja. Hipoteza treba da sadrži ključne riječi iz naslova, odnosno predmeta istraživanja.*

### Odgovor

**Hipoteze:**

1. Moguće je implementirati domenski specifičan jezik (DSL) i generator koda koji će automatski generisati osnovnu strukturu Laravel aplikacije, uključujući modele, kontrolere, rute i prikaze, na osnovu formalno definisanih specifikacija domena.

2. Moguće je, na osnovu unaprijed definisanog DSL-a, generisati potpuno funkcionalnu Laravel aplikaciju koja obezbjeđuje konzistentnost između definisanih podataka, odnosa i pravila poslovne logike, čime se smanjuje potreba za manuelnim kodiranjem i povećava efikasnost razvoja.

---

# IV METODE

## 7. Naučne metode

### Pitanje

Koje će naučne metode biti primijenjene u istraživanju i kako će se njima testirati hipoteze i/ili istraživačka pitanja? *(≤ 3000 karaktera)*

*Uputstvo iz obrasca: Detaljno navesti i obrazložiti koje će se metode koristiti kako bi se testirale hipoteza/e i/ili istraživačka pitanja.*

### Odgovor

### 1. Analiza postojećih rješenja i teorijska analiza

Metodom teorijske analize i pregleda relevantne literature biće detaljno proučena postojeća rješenja iz oblasti razvoja domenski specifičnih jezika (DSL), sa posebnim fokusom na njihovu primjenu u kontekstu web razvoja i automatizovanog generisanja PHP i Laravel aplikacija. Analiza će obuhvatiti postojeće pristupe model-vođenog inženjeringa, alate za generisanje koda i formalne metode za definisanje sintakse i semantike DSL-a. Na osnovu dobijenih saznanja identifikovaće se ograničenja postojećih pristupa i definisati zahtjevi za razvoj sopstvenog DSL-a prilagođenog Laravel okruženju.

### 2. Metoda modelovanja

Metodom modelovanja biće definisan formalni meta-model koji opisuje ključne elemente domena Laravel aplikacije — podatke, relacije i funkcionalne komponente. Ova metoda omogućiće precizno definisanje apstraktne i konkretne sintakse DSL-a, kao i odnosa između komponenti generatora koda. U tu svrhu koristiće se UML dijagrami, dijagrami toka i odgovarajući alati za definisanje gramatike i parsiranje jezika (npr. Xtext).

### 3. Eksperimentalna metoda – razvoj prototipa

Praktična implementacija prototipa DSL-a i generatora koda predstavlja ključnu fazu istraživanja. Ova metoda omogućava provjeru primjenjivosti teorijskog koncepta DSL-a u realnim uslovima razvoja web aplikacija. Generator koda biće implementiran tako da na osnovu specifikacije definisane DSL-om automatski generiše funkcionalni Laravel kod, uključujući modele, kontrolere, rute i prikaze.

### 4. Studija slučaja

Validacija razvijenog DSL-a biće sprovedena kroz studiju slučaja izrade konkretne web aplikacije u Laravel okruženju. U okviru studije biće analizirana tačnost, konzistentnost i vrijeme potrebno za razvoj aplikacije generisane pomoću DSL-a u poređenju sa ručno razvijenom aplikacijom. Rezultati studije biće kvantitativno i kvalitativno analizirani kako bi se potvrdila hipoteza o povećanju efikasnosti i standardizacije procesa automatizovanog razvoja putem DSL pristupa.

---

# V OČEKIVANI REZULTATI ISTRAŽIVANJA I NAUČNI DOPRINOS

## 8. Očekivani rezultati, primjena i naučni doprinos

### Pitanje

Koji su očekivani rezultati istraživanja, njihova praktična primjena i naučni doprinos u odnosu na postojeća istraživanja? *(≤ 3000 karaktera)*

*Uputstvo iz obrasca: Koncizno navesti važnije očekivane rezultate. Ukazati na eventualnu praktičnu primjenu rezultata istraživanja. Sažeto navesti očekivani doprinos rada u odnosu na postojeća istraživanja.*

### Odgovor

Očekuje se da će rezultat istraživanja biti potpuno definisan i implementiran domenski specifični jezik (DSL) za generisanje Laravel aplikacija, sposoban da na osnovu formalnih specifikacija domena automatski generiše modele, kontrolere, rute i prikaze. Razvijeni DSL treba da pojednostavi i automatizuje proces izgradnje web aplikacija, eliminišući potrebu za ručnim pisanjem ponavljajućeg koda i omogućavajući dosljedno poštovanje arhitektonskih principa unutar Laravel frameworka.

Rezultati istraživanja imaju direktnu primjenu u oblasti web razvoja, posebno u razvoju poslovnih i informacionih sistema koji se zasnivaju na Laravel tehnologiji. Praktična vrijednost ogleda se u mogućnosti brzog generisanja prototipova i gotovih aplikacionih struktura na osnovu jednostavnog deklarativnog opisa domena, što olakšava integraciju sa drugim sistemima i omogućava brže reagovanje na promjene zahtjeva korisnika.

Naučni doprinos istraživanja ogleda se u razvoju i validaciji novog pristupa automatizovanom generisanju PHP aplikacija putem DSL-a, što predstavlja značajan korak ka primjeni principa model-vođenog inženjeringa u okviru Laravel sistema. Rad pruža osnovu za dalja istraživanja u oblasti metaprogramiranja, alata za generisanje koda i integracije DSL-ova u savremene razvojne procese.

Predloženi DSL model može poslužiti kao temelj za buduća istraživanja koja se bave generisanjem aplikacija u drugim domenima ili za druge frameworke čime se potvrđuje njegov naučni i praktični značaj u unapređenju procesa automatizovanog razvoja softverskih sistema.

---

# VI DISKUSIJA I ZAKLJUČAK

## 9. Ograničenja i dalji pravci u istraživanju

### Pitanje

Koja su potencijalna ograničenja istraživanja i koji su opravdani prijedlozi za buduća istraživanja u ovoj oblasti? *(≤ 1800 karaktera)*

*Uputstvo iz obrasca: Diskusija o mogućim prijedlozima za buduća istraživanja u ovoj oblasti i njihovoj opravdanosti, putem rezultata istraživanja ili literature. Identifikovati i opisati potencijalna ograničenja istraživanja. Rezultate i doprinose istraživanja potrebno je razmotriti u svjetlu ograničenja – npr. teorijski i konceptualni problemi, problemi metodoloških ograničenja, nemogućnost odgovora na istraživačka pitanja i tome slično.*

### Odgovor

Iako se očekuje da će razvijeni domenski specifični jezik (DSL) značajno unaprijediti proces automatizovanog razvoja Laravel aplikacija, istraživanje posjeduje određena ograničenja. Najvažnija od njih odnose se na konceptualna i tehnička ograničenja DSL-a u pokrivanju svih mogućih slučajeva upotrebe unutar Laravel sistema, naročito onih koji zahtijevaju kompleksnu logiku, integraciju sa eksternim servisima ili napredne sigurnosne mehanizme.

Metodološka ograničenja proizilaze iz činjenice da se validacija prototipa obavlja kroz ograničen broj studija slučaja i test aplikacija. Iako će rezultati pružiti uvid u efikasnost i primjenjivost DSL pristupa, potpuna evaluacija zahtijevala bi širu upotrebu u realnim projektima i u različitim timskim okruženjima.

Takođe, razvoj DSL-a usko je povezan sa verzijama Laravel frameworka, što može uticati na dugoročnu održivost i zahtijevati povremene adaptacije jezika i generatora koda.

Dalji pravci istraživanja obuhvataju proširenje DSL-a na podršku drugim PHP okvirima ili frontend tehnologijama (npr. Vue.js, React), čime bi se povećala interoperabilnost i fleksibilnost sistema. Preporučuje se i proučavanje mogućnosti integracije razvijenog DSL-a sa alatima za kontinuiranu isporuku (CI/CD) i generisanje testova, kako bi se dodatno unaprijedila automatizacija i kvalitativna kontrola softverskog razvoja.

---

# VII STRUKTURA RADA

## 10. Struktura rada po poglavljima

### Pitanje

Kako će master rad biti organizovan po poglavljima?

*Uputstvo iz obrasca: Voditi računa da naslovi poglavlja budu jasno formulisani.*

### Odgovor

Master rad će biti organizovan na sljedeći način:

1. Uvod
2. Pregled dosadašnjih istraživanja
3. Dizajn domeski specifičnog jezika
4. Implementacija DSL-a i generatora koda
5. Evaluacija rezultata
6. Diskusija i zaključak
7. Literatura

---

# VIII LITERATURA

## 11. Korišćena literatura

### Pitanje

Koja literatura je navedena kao osnova istraživanja?

*Uputstvo iz obrasca: Literaturu citirati u APA, MLA, Harvard, Čikago, Vankuver ili nekom drugom stilu, primjenjivijem za određenu oblast nauke, pritom voditi računa da navođenje literature bude dosljedno. Sve navedene reference moraju biti citirane u tekstu prijave.*

### Odgovor

[1] L. Bettini, *Implementing Domain-Specific Languages with Xtext and Xtend*, Packt Publishing, 2016.

[2] H. Krahn, B. Rumpe, and S. Völkel, “MontiCore: A framework for compositional development of domain-specific languages,” *International Journal on Software Tools for Technology Transfer*, 2014.

[3] E. Visser, “WebDSL: A case study in domain-specific language engineering,” *Generative and Transformational Techniques in Software Engineering II*, Springer, 2007.

[4] E. Negm, S. Makady, and A. Salah, “Survey on domain specific languages implementation aspects,” *International Journal of Advanced Computer Science and Applications*, 2019.

[5] M. Voelter, “Why DSLs? A collection of anecdotes,” *InfoQ Articles*, 2018.

[6] J. Borum and C. Seidl, “Survey of established practices in the life cycle of domain-specific languages,” *ACM/IEEE International Conference on Model Driven Engineering Languages and Systems (MODELS)*, 2022.

[7] W. Zhang, D. Strüber, and R. Hebig, “Development and evolution of Xtext-based DSLs on GitHub: An empirical investigation,” *arXiv preprint arXiv:2501.19222*, 2025.

[8] M. Ražinskas, “MDA approach for Laravel framework code generation,” *Conference on Information Systems Development (CEUR-WS)*, 2020.

[9] S. Rahmouni, I. Bouzaidi, and M. Mbarki, “Approach by modeling to generate an e-commerce web code from Laravel,” *International Journal of Electrical and Computer Engineering (IJECE)*, 2023.

[10] F. Mensah, C. Mensah, and R. Dzahene-Quarshie, “Reactive code generation for modular web engineering (MWE) framework,” *ResearchGate preprint*, 2023.

[11] J. J. Cadavid, D. Esteban Lopez, J. A. Hincapié, and J. B. Quintero, “A Domain Specific Language to Generate Web Applications,” *ResearchGate Preprint*, 2014.

[12] L. Netz, J. Michael, and B. Rumpe, “From Natural Language to Web Applications: Using Large Language Models for Model-Driven Software Engineering,” *Modellierung 2024, LNI, Gesellschaft für Informatik (GI)*, 2024.

[13] L. Naimi, “A DSL-based Approach for Code Generation and Management of SPA Navigation Process,” *Procedia Computer Science*, 2024.

---

# PRIJEDLOG ZA MENTORA

## 12. Prijedlog mentora i prijava teme

### Pitanje

Ko se predlaže za mentora i pod kojim nazivom se prijavljuje tema master rada?

### Odgovor

U skladu sa članom 21 stav 1 i članom 22 stav 1 Pravila studiranja na postdiplomskim studijama, predlažem **Prof. dr Aleksandar Popović** za mentora i podnosim prijavu teme master rada pod nazivom:

**Razvoj domenski specifičnog jezika (DSL) za generisanje Laravel aplikacija**

**Potpis studenta:** ____________________________________  
Sara Raičević

## 13. Saglasnost mentora

### Pitanje

Ko daje saglasnost za prihvatanje mentorstva i prijave teme master rada?

### Odgovor

**SAGLASNOST MENTORA ZA PRIHVATANJE MENTORSTVA I PRIJAVE TEME MASTER RADA**

**Potpis mentora:** ____________________________________  
Prof. dr Aleksandar Popović
