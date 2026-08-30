# 2. PREGLED DOSADAŠNJIH ISTRAŽIVANJA

Automatizovano generisanje softverskih sistema predstavlja oblast koja objedinjuje principe domenski specifičnih jezika, modelom vođenog razvoja i transformacije modela u izvršni programski kod. U okviru ove oblasti razvijeni su različiti pristupi kojima se nastoji smanjiti količina ručnog programiranja i omogućiti opis sistema na višem nivou apstrakcije. Posebno mjesto zauzimaju domenski specifični jezici, čija je osnovna namjena da omoguće izražavanje strukture i pravila određenog domena pomoću ograničenog skupa jezičkih konstrukcija.

Za potrebe ovog rada analizirana su istraživanja koja se odnose na dizajn i implementaciju domenski specifičnih jezika, njihov životni ciklus, primjenu u razvoju web aplikacija i pristupe generisanju aplikacionog koda. Posebna pažnja posvećena je radovima koji se odnose na generisanje web aplikacija i Laravel komponenti, jer predstavljaju neposrednu osnovu za poređenje sa rješenjem koje se predlaže u ovom radu.

## 2.1. Domenski specifični jezici i pristupi njihovom razvoju

Domenski specifični jezici razvijaju se sa ciljem da omoguće izražavanje problema i rješenja korišćenjem pojmova karakterističnih za određenu oblast. Za razliku od programskih jezika opšte namjene, koji podržavaju širok skup različitih problema, DSL je usmjeren na ograničeni domen i sadrži konstrukcije prilagođene njegovim potrebama.

Bettini prikazuje razvoj domenski specifičnih jezika korišćenjem alata Xtext i Xtend i detaljno razmatra definisanje gramatike, sintaksnu analizu, validaciju, generisanje koda i podršku razvojnog okruženja.¹ Poseban značaj ovog pristupa ogleda se u povezivanju formalne definicije jezika sa generatorima koji na osnovu korisničke specifikacije proizvode programski kod. Takav proces predstavlja jednu od osnovnih ideja primijenjenih i u ovom radu, gdje se DSL specifikacija obrađuje i transformiše u strukturu Laravel aplikacije.

Drugačiji pristup razvoju domenski specifičnih jezika predstavljen je kroz MontiCore. Krahn, Rumpe i Völkel opisuju MontiCore kao okvir koji podržava kompozicioni razvoj jezika i omogućava kombinovanje i proširivanje postojećih jezičkih elemenata.² Ovakav pristup ukazuje na značaj modularnosti prilikom razvoja jezika, posebno kada se očekuje njegovo dalje proširivanje novim pravilima i konstrukcijama.

Poseban značaj za ovaj rad imaju pristupi u kojima se formalna specifikacija ne koristi samo za opis sistema, već i kao ulaz za automatsko generisanje aplikacionih komponenti. Takav pristup povezuje teorijske principe razvoja jezika sa praktičnim zahtjevima softverskog inženjerstva, jer omogućava da se ponavljajuće strukture sistema proizvode na osnovu unaprijed definisanih pravila.

Negm, Makady i Salah analiziraju različite načine implementacije domenski specifičnih jezika i razmatraju njihove osnovne karakteristike, prednosti i ograničenja.⁴ U njihovom istraživanju naglašava se da izbor načina implementacije zavisi od karakteristika ciljnog domena, očekivanog nivoa apstrakcije i načina na koji će se jezik koristiti. U kontekstu ovog rada posebno je značajan pristup kod kojeg se korisnička specifikacija parsira, transformiše u interni model i zatim koristi kao ulaz generatora koda.

Voelter razmatra praktične razloge za primjenu domenski specifičnih jezika i ukazuje na mogućnost smanjenja ponavljanja, povećanja nivoa apstrakcije i formalizovanja znanja o određenom domenu.⁵ Ove karakteristike posebno su značajne u razvoju aplikacija koje sadrže veliki broj sličnih komponenti, jer se zajednička pravila mogu definisati na nivou generatora umjesto da se ponavljaju u svakom projektu.

Na osnovu analiziranih pristupa može se zaključiti da uspješna primjena DSL-a ne zavisi samo od sintakse jezika. Potrebno je obezbijediti odgovarajući model podataka, pravila validacije, sintaksnu i semantičku obradu, kao i pouzdanu transformaciju korisničke specifikacije u ciljni programski kod.

## 2.2. Životni ciklus i evolucija domenski specifičnih jezika

Domenski specifični jezik ne predstavlja statičan proizvod, već se razvija zajedno sa domenom za koji je namijenjen. Promjene zahtjeva, uvođenje novih funkcionalnosti i izmjene ciljnih tehnologija mogu zahtijevati promjene sintakse, semantike i generatora koda.

Borum i Seidl analiziraju ustanovljene prakse tokom životnog ciklusa domenski specifičnih jezika.⁶ Razmatrane su različite faze, od početne analize domena i dizajna jezika, preko implementacije i testiranja, do održavanja i evolucije. Njihovo istraživanje pokazuje značaj planskog pristupa razvoju DSL-a, posebno kada se jezik koristi tokom dužeg vremenskog perioda ili kada ga je potrebno prilagođavati novim zahtjevima.

Evolucija jezika zasnovanih na Xtext-u dodatno je analizirana u empirijskom istraživanju koje su sproveli Zhang, Strüber i Hebig.⁷ Analizom projekata dostupnih na platformi GitHub razmatrane su promjene DSL-ova tokom njihovog razvoja. Rezultati ovakvih istraživanja ukazuju da su izmjene jezika očekivani dio njegovog životnog ciklusa i da arhitektura DSL sistema treba da omogući proširivanje bez potrebe za potpunom izmjenom postojećeg rješenja.

Ovaj aspekt je značajan i za DSL koji se razmatra u okviru ovog rada. Početna verzija jezika obuhvata osnovne elemente potrebne za generisanje Laravel aplikacija, ali je struktura sistema oblikovana tako da omogući naknadno dodavanje novih tipova podataka, modifikatora, validacionih pravila, relacija i drugih aplikacionih karakteristika.

## 2.3. Primjena domenski specifičnih jezika u razvoju web aplikacija

Primjena DSL-a u razvoju web aplikacija zasniva se na pretpostavci da je veliki dio aplikacione strukture moguće predstaviti kroz formalni model. Entiteti, atributi, odnosi, navigacija i osnovne operacije nad podacima mogu se opisati deklarativno, a zatim transformisati u odgovarajuće komponente ciljnog sistema.

WebDSL predstavlja jedan od značajnijih primjera primjene ovog principa.³ Umjesto neposrednog programiranja svih tehničkih slojeva aplikacije, omogućeno je definisanje aplikacionih koncepata na višem nivou apstrakcije. Generator preuzima dio odgovornosti za njihovo prevođenje u izvršnu aplikaciju. Takav pristup smanjuje zavisnost korisnika DSL-a od pojedinačnih tehničkih detalja ciljne platforme.

Cadavid, Lopez, Hincapié i Quintero takođe razmatraju upotrebu domenski specifičnog jezika za generisanje web aplikacija.¹¹ Predloženi pristup zasniva se na opisivanju aplikacionih elemenata korišćenjem posebnog jezika i naknadnom generisanju potrebnih komponenti. Ovaj rad dodatno potvrđuje mogućnost korišćenja DSL-a kao posrednog sloja između domenskog modela i implementacije web sistema.

Zajednička karakteristika ovih pristupa jeste odvajanje opisa aplikacije od tehničkih detalja konkretne implementacije. Korisnik definiše šta aplikacija treba da sadrži, dok generator određuje način na koji će ta struktura biti realizovana u ciljnom programskom okruženju. Na tom principu zasniva se i pristup koji se dalje razvija u ovom radu.

## 2.4. Modelom vođeno generisanje Laravel aplikacija

Posebno značajnu grupu istraživanja za ovaj rad čine pristupi usmjereni na automatizovano generisanje Laravel aplikacija. Za razliku od opštih DSL pristupa, ova istraživanja koriste karakteristike Laravel okvira kao ciljnu platformu za generisanje programskog koda.

Ražinskas razmatra pristup generisanju Laravel koda zasnovan na modelom vođenoj arhitekturi.⁸ Osnovna ideja zasniva se na definisanju modela sistema na višem nivou apstrakcije, nakon čega se primjenjuju transformacije kojima se proizvode Laravel komponente. Ovakav pristup pokazuje da se struktura aplikacije može formalizovati prije same implementacije i zatim automatski prevesti u ciljni okvir.

Rahmouni, Bouzaidi i Mbarki primjenjuju modelovanje na generisanje koda aplikacije elektronske trgovine zasnovane na Laravelu.⁹ Njihov pristup povezuje opis sistema sa automatskim generisanjem odgovarajućih web komponenti. Posebno je značajno to što je generisanje usmjereno na konkretnu ciljnu tehnologiju, što omogućava korišćenje njenih pravila i arhitektonskih konvencija.

Navedeni radovi pokazuju da postoji osnova za automatizovano generisanje Laravel aplikacija na osnovu modela višeg nivoa. Ipak, u ovom radu polazište predstavlja posebno dizajniran domenski specifičan jezik kojim se eksplicitno definišu elementi aplikacije. Specifikacija se zatim parsira i čuva u obliku metamodela, nakon čega se primjenjuje generator Laravel projekta. Time se DSL koristi kao neposredan interfejs između opisa domena i generisanog programskog sistema.

## 2.5. Savremeni pristupi automatizovanom generisanju web aplikacija

Istraživanja iz oblasti generisanja web aplikacija nijesu ograničena isključivo na klasične DSL i modelom vođene pristupe. Razvijaju se i rješenja usmjerena na modularnost, automatsku transformaciju specifikacija i upotrebu novih tehnologija za generisanje programskog koda.

Mensah, Mensah i Dzahene-Quarshie razmatraju reaktivno generisanje koda u okviru modularnog web inženjerstva.¹⁰ Njihov pristup ukazuje na mogućnost povezivanja modularne strukture sistema i automatskog generisanja potrebnih aplikacionih elemenata. Modularnost je značajna jer omogućava da se pojedinačne funkcionalnosti sistema razvijaju i proširuju bez potrebe za izmjenom cjelokupne arhitekture.

Noviji pristupi istražuju i povezivanje modelom vođenog razvoja sa modelima velikih jezika. Netz, Michael i Rumpe razmatraju mogućnost transformacije zahtjeva iskazanih prirodnim jezikom u modele na osnovu kojih se dalje mogu razvijati web aplikacije.¹² Ovakav pristup predstavlja dodatno podizanje nivoa apstrakcije, jer se početna specifikacija približava prirodnom načinu izražavanja korisničkih zahtjeva. Ipak, ovakvi sistemi uvode i dodatna pitanja u vezi sa preciznošću, jednoznačnošću i ponovljivošću generisanih rezultata.

Naimi primjenjuje DSL pristup na specifičan dio razvoja jednostraničnih aplikacija, odnosno na generisanje i upravljanje procesom navigacije.¹³ Ovo istraživanje pokazuje da domenski specifični jezici ne moraju biti namijenjeni generisanju cjelokupnog sistema, već mogu biti usmjereni na jasno ograničen segment aplikacione funkcionalnosti.

Različitost analiziranih rješenja pokazuje da automatizovano generisanje web aplikacija može biti realizovano na više nivoa. Generisanje može obuhvatiti pojedinačne aplikacione funkcionalnosti, određene slojeve sistema ili kompletnu početnu strukturu projekta.

## 2.6. Kritički osvrt na postojeća rješenja

Pregled dosadašnjih istraživanja pokazuje da su domenski specifični jezici i modelom vođeni pristupi široko primjenjivi u automatizaciji razvoja softvera. Postojeći radovi potvrđuju mogućnost definisanja sistema na višem nivou apstrakcije i automatskog generisanja programskog koda na osnovu takvih opisa. Posebno su značajni pristupi koji se odnose na web aplikacije i Laravel, jer pokazuju da se veliki dio standardne aplikacione strukture može formalno opisati i automatizovano proizvesti.

Ipak, analizirani pristupi razlikuju se prema nivou apstrakcije, obimu generisanih komponenti i ciljnoj tehnologiji. Pojedina rješenja usmjerena su na razvoj samog jezika, druga na generisanje određenog dijela web aplikacije, dok modelom vođeni pristupi često zahtijevaju korišćenje posebnih modela i razvojnih alata. Postoje i rješenja koja su usmjerena na Laravel, ali se razlikuju prema načinu na koji se definiše ulazna specifikacija i obimu generisanog projekta.

Radi jasnijeg sagledavanja odnosa između analiziranih pristupa, u tabeli 2.1 prikazane su njihove ključne karakteristike u odnosu na ulaznu specifikaciju, ciljnu tehnologiju, obim generisanja i ograničenja.

| Pristup / rad | Ulazna specifikacija | Ciljna tehnologija | Obim generisanja | Osnovno ograničenje |
| --- | --- | --- | --- | --- |
| Bettini | DSL gramatika i modeli jezika | Opšti pristup | Zavisi od definisanog generatora | Metodološki okvir, bez vezivanja za Laravel |
| MontiCore | Kompozicione definicije jezika | Opšti pristup | Zavisi od ciljnog jezika i generatora | Fokus na razvoj jezika, ne na konkretne web aplikacije |
| WebDSL | DSL za web aplikacije | Web okruženje | Podaci, interfejs i aplikaciona logika | Nije prilagođen Laravel arhitekturi |
| Cadavid et al. | DSL specifikacija web aplikacije | Web aplikacije | Aplikacione komponente | Opšti web pristup, bez Laravel konvencija |
| Ražinskas | Modeli višeg nivoa apstrakcije | Laravel | Laravel komponente | Zavisnost od modelom vođenog pristupa i pratećih alata |
| Rahmouni et al. | Modeli i transformacije | Laravel | Komponente e-commerce aplikacije | Usmjerenost na specifičan domen primjene |
| Mensah et al. | Modularne specifikacije | Modularni web sistemi | Modularne aplikacione strukture | Manje direktno vezano za Laravel generator |
| Netz et al. | Prirodni jezik i modeli | Modelom vođeni razvoj | Modeli kao osnova za dalji razvoj | Pitanja preciznosti, jednoznačnosti i ponovljivosti |
| Naimi | DSL za navigaciju | Jednostranične aplikacije | Navigacioni proces | Generisanje ograničeno na jedan segment aplikacije |

U nastavku rada predlaže se rješenje koje nastoji da objedini jednostavan deklarativni opis domena i generisanje većeg broja međusobno povezanih Laravel komponenti. Korisniku se omogućava definisanje modela, njihovih polja, tipova podataka, modifikatora, validacionih pravila i relacija, dok se obrada specifikacije, formiranje metamodela i generisanje konačnog projekta izvršavaju automatski. Generisani projekat obuhvata modele, migracije, kontrolere, rute, korisničke prikaze, autentifikaciju, početne podatke, testne fajlove i prateću dokumentaciju.

Na osnovu pregleda literature utvrđeno je da postoji prostor za razvoj rješenja koje je neposredno prilagođeno Laravel okruženju, omogućava jednostavno definisanje strukture aplikacije i povezuje DSL specifikaciju sa generisanjem funkcionalnog početnog projekta. Navedene karakteristike predstavljaju polazište za dizajn jezika opisan u narednom poglavlju.
