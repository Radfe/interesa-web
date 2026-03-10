<?php
declare(strict_types=1);

if (!function_exists('interessa_article_commerce_sections')) {
    function interessa_article_commerce_sections(): array {
        return [
            'najlepsie-proteiny-2025' => [
                'title' => 'Odporúčané produkty',
                'intro' => 'Výber nižšie je pripravený ako nákupný shortcut k článku. Keď budú doplnené finálne Dognet deeplinky, CTA ostanú rovnaké a zmení sa len cieľová URL.',
                'products' => [
                    [
                        'name' => 'Whey Protein / univerzálny WPC',
                        'subtitle' => 'Najlepší pomer cena, chuť a použiteľnosť pre väčšinu ľudí.',
                        'rating' => 4.8,
                        'code' => 'najlepsie-proteiny-2025-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Whey Isolate / čistejší WPI',
                        'subtitle' => 'Vhodný pri redukcii a nižšej tolerancii laktózy.',
                        'rating' => 4.7,
                        'code' => 'najlepsie-proteiny-2025-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Performance isolate blend',
                        'subtitle' => 'Dobrý variant pre tréning a vyšší dôraz na bielkoviny na dávku.',
                        'rating' => 4.6,
                        'code' => 'najlepsie-proteiny-2025-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
            'kreatin-porovnanie' => [
                'title' => 'Top kreatín produkty',
                'intro' => 'Pre väčšinu ľudí dáva zmysel začať kvalitným monohydrátom. HCl má význam skôr ako špecifická alternatíva.',
                'products' => [
                    [
                        'name' => 'Creatine Monohydrate / Creapure',
                        'subtitle' => 'Najistejšia voľba z pohľadu dôkazov a ceny.',
                        'rating' => 4.9,
                        'code' => 'kreatin-porovnanie-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Kreatín monohydrát',
                        'subtitle' => 'Dobrá value voľba pre pravidelné dávkovanie.',
                        'rating' => 4.7,
                        'code' => 'kreatin-porovnanie-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Kreatín HCl',
                        'subtitle' => 'Alternatíva, ak chceš menšie dávky alebo lepšiu rozpustnosť.',
                        'rating' => 4.4,
                        'code' => 'kreatin-monohydrat-vs-hcl-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
            'kolagen-recenzia' => [
                'title' => 'Odporúčané kolagény',
                'intro' => 'Pri kĺboch a koži sleduj typ kolagénu, dávku na porciu a to, či dáva zmysel kombinácia s vitamínom C.',
                'products' => [
                    [
                        'name' => 'Kolagén typ I & III',
                        'subtitle' => 'Rozumný základ pre pokožku, vlasy a univerzálne použitie.',
                        'rating' => 4.7,
                        'code' => 'kolagen-recenzia-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Kolagén + vitamín C',
                        'subtitle' => 'Vhodný variant, ak chceš mať support stack v jednom produkte.',
                        'rating' => 4.6,
                        'code' => 'kolagen-recenzia-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Čistý hydrolyzovaný kolagén',
                        'subtitle' => 'Jednoduchá voľba bez zbytočných prísad.',
                        'rating' => 4.5,
                        'code' => 'kolagen-proteinsk',
                        'url' => 'https://www.protein.sk/',
                        'merchant' => 'Protein.sk',
                    ],
                ],
            ],
            'veganske-proteiny-top-vyber-2025' => [
                'title' => 'Top vegánske proteíny',
                'intro' => 'Najlepší výsledok zvyčajne dávajú blendy hrach + ryža. Pri citlivosti na sóju je to aj praktický kompromis.',
                'products' => [
                    [
                        'name' => 'Blend hrach + ryža',
                        'subtitle' => 'Najvyváženejší aminokyselinový profil pre bežné použitie.',
                        'rating' => 4.8,
                        'code' => 'veganske-proteiny-top-vyber-2025-aktin',
                        'url' => 'https://www.aktin.sk/',
                        'merchant' => 'Aktin',
                    ],
                    [
                        'name' => 'Hrachový izolát',
                        'subtitle' => 'Dobrá voľba, ak chceš čisté zloženie a bez sóje.',
                        'rating' => 4.6,
                        'code' => 'veganske-proteiny-top-vyber-2025-gymbeam',
                        'url' => 'https://gymbeam.sk/',
                        'merchant' => 'GymBeam',
                    ],
                    [
                        'name' => 'Rastlinný protein mix',
                        'subtitle' => 'Praktický variant do kaše, smoothie a pravidelného použitia.',
                        'rating' => 4.5,
                        'code' => 'veganske-proteiny-top-vyber-2025-myprotein',
                        'url' => 'https://www.myprotein.sk/',
                        'merchant' => 'Myprotein',
                    ],
                ],
            ],
        ];
    }
}

if (!function_exists('interessa_article_commerce')) {
    function interessa_article_commerce(string $slug): ?array {
        $sections = interessa_article_commerce_sections();
        return $sections[$slug] ?? null;
    }
}