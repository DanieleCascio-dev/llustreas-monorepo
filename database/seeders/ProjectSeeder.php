<?php

namespace Database\Seeders;

use App\Models\GalleryImage;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedProjects();
        $this->seedGallery();
        $this->seedGalleryPreview();
    }

    private function seedProjects(): void
    {
        $projects = $this->getProjectsData();

        foreach ($projects as $projectData) {
            $project = Project::create([
                'title' => $projectData['title'],
                'slug' => $projectData['slug'],
                'hero_url' => $projectData['hero_url'],
                'gif_url' => $projectData['gif_url'] ?? null,
                'layout' => $projectData['layout'],
                'order' => $projectData['order'],
                'description' => $projectData['description'] ?? null,
                'info' => $projectData['info'] ?? null,
                'is_published' => true,
            ]);

            foreach ($projectData['images'] as $i => $imageData) {
                if (isset($imageData['type']) && $imageData['type'] === 'textImageBlock') {
                    $image = $project->images()->create([
                        'type' => 'text_image_block',
                        'src' => $imageData['image']['src'],
                        'order' => $i,
                    ]);

                    $textBlock = $image->textBlock()->create([
                        'subtitle' => $imageData['text']['subtitle'] ?? '',
                        'title' => $imageData['text']['title'],
                        'text_color' => $imageData['text']['textColor'] ?? '#000000',
                        'background_color' => $imageData['text']['backgroundColor'] ?? '#ffffff',
                        'layout' => $imageData['layout'] ?? 'text-left',
                        'image_position' => $imageData['imagePosition'] ?? null,
                    ]);

                    foreach (($imageData['text']['paragraphs'] ?? []) as $pi => $para) {
                        $textBlock->paragraphs()->create([
                            'title' => $para['title'] ?? '',
                            'text' => $para['text'],
                            'text_html' => $para['textHtml'] ?? null,
                            'order' => $pi,
                        ]);
                    }
                } else {
                    $project->images()->create([
                        'type' => 'image',
                        'src' => $imageData['src'],
                        'order' => $i,
                    ]);
                }
            }
        }
    }

    private function seedGallery(): void
    {
        $columns = $this->getGalleryColumns();

        foreach ($columns as $colIndex => $images) {
            foreach ($images as $order => $img) {
                GalleryImage::create([
                    'src' => $img['src'],
                    'title' => $img['title'] ?? null,
                    'column_index' => $colIndex,
                    'order' => $order,
                    'is_preview' => false,
                ]);
            }
        }
    }

    private function seedGalleryPreview(): void
    {
        $previews = [
            ['url' => 'https://images2.imgbox.com/db/2e/ghhgIFY1_o.jpg', 'title' => 'Adventure Time'],
            ['url' => 'https://images2.imgbox.com/da/b0/PdwqMOOm_o.jpg', 'title' => 'Dog'],
            ['url' => 'https://images2.imgbox.com/8d/1e/1y3FI8gw_o.gif', 'title' => 'Gina'],
            ['url' => 'https://images2.imgbox.com/a2/2b/Xq6HiB7i_o.jpg', 'title' => 'Oasi Pumbino evento'],
            ['url' => 'https://images2.imgbox.com/f4/37/nJMzvGAK_o.gif', 'title' => 'Pumbino Animali'],
            ['url' => 'https://images2.imgbox.com/3b/f7/ytOcZcGV_o.jpg', 'title' => 'Passiflora'],
            ['url' => 'https://images2.imgbox.com/0c/c6/nx4ymC1P_o.jpg', 'title' => 'Rana'],
            ['url' => 'https://images2.imgbox.com/b3/3d/yzj3Veal_o.jpg', 'title' => 'Witchtober Phone'],
            ['url' => 'https://images2.imgbox.com/8c/ba/79TFKvBO_o.jpg', 'title' => 'Witchtober Clouds'],
        ];

        foreach ($previews as $order => $preview) {
            GalleryImage::create([
                'src' => $preview['url'],
                'title' => $preview['title'],
                'column_index' => 0,
                'order' => $order,
                'is_preview' => true,
            ]);
        }
    }

    private function getProjectsData(): array
    {
        return [
            [
                'title' => 'Logofolio',
                'slug' => 'logofolio',
                'hero_url' => 'https://images2.imgbox.com/placeholder/logofolio.jpg',
                'layout' => 'column',
                'order' => 3,
                'images' => [
                    ['src' => 'https://images2.imgbox.com/b1/db/bqoM6wV9_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/21/aa/6IAP3agF_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/6c/bd/sEbE0Dzd_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/1c/5a/cKRw6mHG_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/1c/48/BALtmTSR_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/78/2a/UMTKCO2W_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/15/95/k2gPJztf_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/85/ae/Ze6OyeFk_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/23/26/YMPmLiFa_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/ad/d7/jj4wON2d_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/72/5b/CkJ9pV4M_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/55/df/4Ggh3rFL_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/b7/6e/4qQS5g12_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/d7/0f/0U3AjpGY_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/69/c3/sJ43loFM_o.jpg'],
                ],
            ],
            [
                'title' => 'Pianeta B12',
                'slug' => 'pianeta-b12',
                'hero_url' => 'https://images2.imgbox.com/placeholder/pianetab12.jpg',
                'gif_url' => 'https://images2.imgbox.com/94/46/4xhn2tt1_o.jpg',
                'layout' => 'column',
                'order' => 2,
                'description' => "Pianeta B12 è un progetto di branding e packaging per un'azienda immaginaria che produce prodotti ecologici e sostenibili. Il design si concentra su elementi naturali e colori terrosi per riflettere l'impegno dell'azienda verso l'ambiente.",
                'info' => 'Branding - Graphic design - Art direction',
                'images' => [
                    ['src' => 'https://images2.imgbox.com/57/cf/4bt6Xtot_o.jpg'],
                    [
                        'type' => 'textImageBlock',
                        'text' => [
                            'subtitle' => 'IL PODCAST',
                            'title' => 'PIANETA B12',
                            'paragraphs' => [
                                ['title' => 'OBIETTIVO:', 'text' => "Dare forma e identità a un podcast innovativo e accessibile, capace di parlare di sostenibilità, etica e cultura attraverso un linguaggio creativo e coinvolgente."],
                                ['title' => 'COSA HO REALIZZATO:', 'text' => "Ideazione e sviluppo della brand identity, direzione artistica, progettazione grafica e aiuto nella costruzione del set. Ho dato vita a un immaginario visivo coerente: un viaggio spaziale in uno scenario post-apocalittico, tra natura e tecnologia.", 'textHtml' => "<strong>Ideazione e sviluppo della brand identity, direzione artistica, progettazione grafica e aiuto nella costruzione del set.</strong> <br> Ho dato vita a un immaginario visivo coerente: un viaggio spaziale in uno scenario post-apocalittico, tra natura e tecnologia."],
                                ['title' => 'RUOLO E COMPETENZE:', 'text' => "Direzione creativa, graphic design, art direction, progettazione del set e visual storytelling."],
                                ['title' => 'RISULTATI OTTENUTI:', 'text' => "Un'identità visiva forte e riconoscibile che ha contribuito alla crescita del progetto e alla costruzione di una community appassionata. Ospiti di rilievo e collaborazioni in espansione."],
                                ['title' => 'PERIODO:', 'text' => 'Da marzo 2024 - in corso'],
                            ],
                            'textColor' => '#ffffff',
                            'backgroundColor' => '#7a5ac7',
                        ],
                        'layout' => 'text-left',
                        'image' => ['src' => 'https://images2.imgbox.com/68/f0/E1DjNRiD_o.png'],
                    ],
                    [
                        'type' => 'textImageBlock',
                        'text' => [
                            'subtitle' => 'STORYTELLING',
                            'title' => 'PIANETA B12',
                            'paragraphs' => [
                                ['title' => '', 'text' => "In un futuro molto lontano, l'uomo ha lasciato la Terra, ormai devastata e priva di risorse utili."],
                                ['title' => '', 'text' => "Un gruppo di esploratori ha deciso di mettersi in viaggio. Dopo centinaia di anni sono tornati sulla Terra in cerca di risposte.", 'textHtml' => "<strong>Un gruppo di esploratori ha deciso di mettersi in viaggio. Dopo centinaia di anni sono tornati sulla Terra in cerca di risposte.</strong>"],
                                ['title' => '', 'text' => "Perché quel pianeta, un tempo ricco e rigoglioso, è stato abbandonato? Cosa lo ha portato alla rovina? Qualcuno ha cercato di salvarlo?"],
                                ['title' => '', 'text' => "La Terra è tornata ad uno stato naturale. Tra le macerie e i resti di quell'antica civiltà, la natura ha cercato di riprendere il suo posto."],
                                ['title' => '', 'text' => "Gli esploratori sono sempre più convinti che, dopo tutto questo tempo, la Terra potrebbe tornare ad essere ripopolata. Ma per farlo, bisogna prima capire come. Per non ripetere quegli errori che l'hanno portata alla rovina.", 'textHtml' => "Gli esploratori sono sempre più convinti che, dopo tutto questo tempo, la Terra potrebbe tornare ad essere ripopolata. Ma per farlo, bisogna prima capire come. <strong>Per non ripetere quegli errori che l'hanno portata alla rovina.</strong>"],
                            ],
                            'textColor' => '#ffffff',
                            'backgroundColor' => '#7a5ac7',
                        ],
                        'layout' => 'text-right',
                        'image' => ['src' => 'https://images2.imgbox.com/73/04/0ekfqu7E_o.png'],
                    ],
                    ['src' => 'https://images2.imgbox.com/74/ce/BvJbzGb0_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/f0/ce/C5M1SJfJ_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/ba/41/QRTge4Zu_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/b8/4a/EtW2SlCF_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/5e/de/gLAySC3B_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/ce/3d/mgIsOtXx_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/05/18/BZ6JA80D_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/1b/f1/ObI0oxj2_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/2e/0c/I5eR1tGc_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/3f/32/QtN2kkLf_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/40/12/Vk0LaS5e_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/e4/f3/TcXlEBbi_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/96/65/6lHpP21H_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/24/74/OJEm0Cc9_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/0d/8a/f9QUQqxQ_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/1b/25/7vEgDh4p_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/68/ac/1Q7m2Ens_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/52/3e/CXrXhyFX_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/5f/06/bIthtRHp_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/a8/a0/WaWCkuDH_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/f4/36/9PoT2kiD_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/3b/04/2ZgEXUth_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/34/34/4D6XHwN0_o.jpg'],
                ],
            ],
            [
                'title' => 'Oasi Pumbino',
                'slug' => 'oasi-pumbino',
                'hero_url' => 'https://images2.imgbox.com/placeholder/oasi-pumbino.jpg',
                'gif_url' => 'https://images2.imgbox.com/6b/40/4ZUla30s_o.jpg',
                'layout' => 'column',
                'order' => 5,
                'info' => 'Branding - Graphic design - Art direction',
                'images' => [
                    ['src' => 'https://images2.imgbox.com/7f/d7/KaP8EQn5_o.jpg'],
                    [
                        'type' => 'textImageBlock',
                        'text' => [
                            'subtitle' => 'IL RIFUGIO PER ANIMALI', 'title' => 'OASI PUMBINO',
                            'paragraphs' => [
                                ['title' => 'OBIETTIVO:', 'text' => "Dare un'identità visiva riconoscibile e autentica a Oasi Pumbino, rifugio per animali liberi, valorizzandone i valori e creando le basi per una comunicazione coerente e immediatamente riconoscibile."],
                                ['title' => 'COSA HO REALIZZATO:', 'text' => "Logo design e sviluppo della prima brand identity: definizione della color palette, scelta dei font, mood e linee guida grafiche. Ho realizzato artigianalmente i primi prodotti di merchandising (illustrazioni per card, sticker e materiali promozionali), oltre a una locandina per un evento.", 'textHtml' => "<strong>Logo design e sviluppo della prima brand identity:</strong> definizione della color palette, scelta dei font, mood e linee guida grafiche.<br>Ho realizzato artigianalmente i <strong>primi prodotti di merchandising</strong> (illustrazioni per card, sticker e materiali promozionali), oltre a una locandina per un evento."],
                                ['title' => '', 'text' => "Il progetto ha poi dato vita a una brand guideline che ha permesso al team grafico successivo di portare avanti la comunicazione visiva del santuario."],
                                ['title' => 'RUOLO E COMPETENZE:', 'text' => "Logo design, brand identity, illustrazione, graphic design per stampa e merchandising."],
                                ['title' => 'PERIODO:', 'text' => 'Settembre 2023 - Settembre 2024'],
                            ],
                            'textColor' => '#aa6a7f', 'backgroundColor' => '#f4f1ef',
                        ],
                        'layout' => 'text-left',
                        'image' => ['src' => 'https://images2.imgbox.com/02/8f/r5F4HYcv_o.png'],
                    ],
                    ['src' => 'https://images2.imgbox.com/f6/f2/QBm48smH_o.jpg'],
                    [
                        'type' => 'textImageBlock',
                        'text' => [
                            'subtitle' => '', 'title' => 'IL MUSETTO DIETRO LA MASCOTTE',
                            'paragraphs' => [
                                ['title' => '', 'text' => 'Pumbino, per i follower Pumba, era un maialino vietnamita nato con una rara malattia genetica. Charley, insieme alla madre Maria Alda, lo ha salvato e accolto nell\'agriturismo di famiglia, diventando per tutti "il suo badante". Pumbino si sdraiava sulla pancia della mamma, proprio sul luogo dove stava combattendo la malattia.', 'textHtml' => '<strong>Pumbino, per i follower Pumba, era un maialino vietnamita nato con una rara malattia genetica</strong>. Charley, insieme alla madre Maria Alda, lo ha salvato e accolto nell\'agriturismo di famiglia, diventando per tutti "il suo badante". Pumbino si sdraiava sulla pancia della mamma, proprio sul luogo dove stava combattendo la malattia.'],
                                ['title' => '', 'text' => "Quel piccolo essere ha portato luce in un momento buio, regalando sorrisi e speranza non solo a chi lo circondava, ma anche alla sua comunità online, radunando centinaia di migliaia di follower che si affezionarono alla sua dolcezza.", 'textHtml' => "Quel piccolo essere ha portato luce in un momento buio, <strong>regalando sorrisi e speranza</strong> <strong>non solo a chi lo circondava, ma anche alla sua comunità online</strong>, radunando centinaia di migliaia di follower che si affezionarono alla sua dolcezza."],
                                ['title' => '', 'text' => "Quando Pumbino è venuto a mancare, dal dolore è nata una missione: Charley e sua padre hanno dato vita all'Oasi Pumbino, un rifugio dedicato agli animali in difficoltà, luogo tangibile in cui restituire tutto il bene che quel piccolo maialino aveva donato.", 'textHtml' => "Quando Pumbino è venuto a mancare, dal dolore è nata una missione: <strong>Charley e sua padre hanno dato vita all'Oasi Pumbino, un rifugio dedicato agli animali in difficoltà, luogo tangibile in cui restituire tutto il bene che quel piccolo maialino aveva donato</strong>."],
                                ['title' => '', 'text' => "È così che la memoria di Pumbino continua a vivere, attraverso ogni vita salvata e raccontata con cura e autenticità."],
                            ],
                            'textColor' => '#aa6a7f', 'backgroundColor' => '#f4f1ef',
                        ],
                        'layout' => 'text-right',
                        'image' => ['src' => 'https://images2.imgbox.com/9c/21/VzFAUG07_o.png'],
                    ],
                    ['src' => 'https://images2.imgbox.com/ce/75/MHufoSJr_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/89/1c/E2ifFuOO_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/08/92/lR5Rtntk_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/35/d0/6gkZm7Qz_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/52/af/tERtKj19_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/a4/2d/TUxxoLhP_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/91/0a/i9SvXili_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/66/c1/GcGgsp81_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/26/3e/2knNsE2i_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/34/4b/boyXTVuY_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/3a/d2/msWknbUv_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/0d/e3/7IhqVvep_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/10/be/cwZRcj7v_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/a0/e8/4Tt5p1JU_o.jpg'],
                ],
            ],
            [
                'title' => 'Angolo di Paradiso',
                'slug' => 'angolo-di-paradiso',
                'hero_url' => 'https://images2.imgbox.com/placeholder/angolo.jpg',
                'gif_url' => 'https://images2.imgbox.com/e7/ab/SdYZH4v7_o.gif',
                'layout' => 'column',
                'order' => 1,
                'info' => 'Branding - Graphic design - Packaging',
                'images' => [
                    ['src' => 'https://images2.imgbox.com/1f/13/QYduIqZT_o.jpg'],
                    [
                        'type' => 'textImageBlock',
                        'text' => [
                            'subtitle' => 'FESTIVAL ARTISTICO DI VICENZA', 'title' => 'SPAZINSOLITI',
                            'paragraphs' => [
                                ['title' => 'OBIETTIVO:', 'text' => "creare un'identità visiva distintiva per l'edizione 2025 di Spazinsoliti, festival artistico e multidisciplinare che trasforma Vicenza in un percorso di scoperta attraverso performance, esposizioni e incontri con artisti emergenti."],
                                ['title' => 'RUOLO E COMPETENZE:', 'text' => 'graphic design, brand identity, visual design per stampa e digitale.'],
                                ['title' => 'PERIODO:', 'text' => 'Luglio - settembre 2025'],
                                ['title' => 'COSA HO REALIZZATO:', 'text' => "Curato l'identità visiva completa, partendo dalla locandina e sviluppando l'intero sistema di comunicazione: grafiche per i social, poster, banner e merchandise.", 'textHtml' => "<strong>Curato l'identità visiva completa,</strong> partendo dalla locandina e sviluppando l'intero sistema di comunicazione: <strong>grafiche per i social, poster, banner e merchandise.</strong>"],
                            ],
                            'textColor' => '#ffffff', 'backgroundColor' => '#95a684',
                        ],
                        'layout' => 'text-left',
                        'image' => ['src' => 'https://images2.imgbox.com/3e/00/2PMUtCsR_o.png'],
                    ],
                    ['src' => 'https://images2.imgbox.com/79/b4/U4QEPsWW_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/92/bc/a2KL3X5Z_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/db/ad/pK3EeQCp_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/42/a9/NXMTLVuM_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/78/73/qhJNPJPm_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/59/11/1xbbz0Zt_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/90/51/HXYRBYMA_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/52/0f/nLEQH6Wq_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/91/be/InINMJ1d_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/ba/38/TncpY9uu_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/3b/a0/oEROOCne_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/fa/be/f4vYjbgA_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/1e/3c/LaI8qnrY_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/69/05/nIUIUdjl_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/3f/f1/0Ispyi9p_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/47/7d/YZk7i6pZ_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/6b/ce/jjOwwOmN_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/e9/a3/gIcXgWIC_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/e8/d3/lj9E1ANJ_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/ab/5b/v1kUyuCK_o.jpg'],
                ],
            ],
            [
                'title' => 'Spazinsoliti',
                'slug' => 'spazinsoliti',
                'hero_url' => 'https://images2.imgbox.com/placeholder/spazinsoliti.jpg',
                'gif_url' => 'https://images2.imgbox.com/57/f1/zYJNLcRC_o.jpg',
                'layout' => 'column',
                'order' => 4,
                'info' => 'Graphic design - Branding',
                'images' => [
                    ['src' => 'https://images2.imgbox.com/f3/04/iMLhr3z3_o.jpg'],
                    [
                        'type' => 'textImageBlock',
                        'text' => [
                            'subtitle' => 'FESTIVAL ARTISTICO DI VICENZA', 'title' => 'SPAZINSOLITI',
                            'paragraphs' => [
                                ['title' => 'OBIETTIVO:', 'text' => "creare un'identità visiva distintiva per l'edizione 2025 di Spazinsoliti, festival artistico e multidisciplinare che trasforma Vicenza in un percorso di scoperta attraverso performance, esposizioni e incontri con artisti emergenti."],
                                ['title' => 'COSA HO REALIZZATO:', 'text' => "Curato l'identità visiva completa, partendo dalla locandina e sviluppando l'intero sistema di comunicazione: grafiche per i social, poster, banner e merchandise.", 'textHtml' => "<strong>Curato l'identità visiva completa,</strong> partendo dalla locandina e sviluppando l'intero sistema di comunicazione: <strong>grafiche per i social, poster, banner e merchandise.</strong>"],
                                ['title' => 'RUOLO E COMPETENZE:', 'text' => 'Graphic design, brand identity, visual design per stampa e digitale.'],
                                ['title' => 'PERIODO:', 'text' => 'Luglio - settembre 2025'],
                            ],
                            'textColor' => '#ffffff', 'backgroundColor' => '#fe316e',
                        ],
                        'layout' => 'text-left',
                        'imagePosition' => 'bottom-right',
                        'image' => ['src' => 'https://images2.imgbox.com/e5/1e/04kaEQTT_o.png'],
                    ],
                    ['src' => 'https://images2.imgbox.com/db/a6/iv7RDBRl_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/cc/42/wybwYyvr_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/fa/0a/WE8KBGPF_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/84/fe/YWS24epc_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/e8/70/SkuuldoQ_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/f0/7f/Dzf7C6N1_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/ec/97/UU6EB6Yc_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/31/fa/lqurA97t_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/5e/df/cQWVpR0O_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/68/4f/50MTFfQ0_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/ff/11/pRUmVm51_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/d7/65/xAtFhzK9_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/a8/2b/WZHo2pAk_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/69/90/g9RzlYxR_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/5d/b0/LDJoFRZ9_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/0e/10/T1NyEgKu_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/7f/e0/VyrbIyiW_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/c9/6d/Uofdg6ui_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/32/3d/rBrFViO0_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/17/b6/lvpKlSd9_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/81/2f/Wg5tYjYr_o.jpg'],
                ],
            ],
            [
                'title' => 'Una zampa dal cielo',
                'slug' => 'una-zampa-dal-cielo',
                'hero_url' => 'https://images2.imgbox.com/placeholder/zampa.jpg',
                'layout' => 'column',
                'order' => 6,
                'info' => 'Graphic design - Branding',
                'images' => [
                    ['src' => 'https://images2.imgbox.com/a0/12/a4y23pOz_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/6d/31/SMTemMEY_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/7a/de/u51cPKjV_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/3f/8c/qzWHSM8Q_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/bc/2c/KiC1JcRS_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/65/d3/hnu5ni4t_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/ac/cf/tlMyisdD_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/82/96/AtsX6M0L_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/6a/77/2LXAw1Qs_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/79/8a/AyIRyz6R_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/e3/15/2JqAeyIR_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/c4/be/bl2ll6KF_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/eb/01/sc0yG1jg_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/d0/e7/JbQd8AV9_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/73/fa/E84wP0Pt_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/97/f7/014pAPf6_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/f7/0f/g5NC9CP2_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/9f/86/SxRyzodC_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/eb/6b/sm4ujn7z_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/17/d9/ZbS8Jt8J_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/cb/c2/dU74Lmtg_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/be/13/GTQy6i4b_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/01/43/B2lnLaWK_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/ea/1c/bXbFNXHG_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/f9/94/99Fib6lH_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/8e/aa/le0kCXik_o.jpg'],
                    ['src' => 'https://images2.imgbox.com/f4/2f/YiDMLuXV_o.jpg'],
                ],
            ],
        ];
    }

    private function getGalleryColumns(): array
    {
        return [
            [
                ['src' => 'https://images2.imgbox.com/35/7a/qMV6xRTF_o.jpg', 'title' => 'Coca-Cola'],
                ['src' => 'https://images2.imgbox.com/a3/ad/dt7S8Eiv_o.jpg', 'title' => 'Dharma Vision'],
                ['src' => 'https://images2.imgbox.com/db/93/Folb1WQW_o.jpg', 'title' => 'Dharma Vision'],
                ['src' => 'https://images2.imgbox.com/3b/6d/ja0lKkoz_o.jpg', 'title' => 'Dharma Vision'],
                ['src' => 'https://images2.imgbox.com/20/0b/X5TqIWt1_o.jpg', 'title' => 'Erbario'],
                ['src' => 'https://images2.imgbox.com/b6/e3/3Vq7nwqC_o.jpg', 'title' => 'Farfalle 01'],
                ['src' => 'https://images2.imgbox.com/c8/20/e4f1vS39_o.jpg', 'title' => 'Witchtober Undead'],
            ],
            [
                ['src' => 'https://images2.imgbox.com/47/34/WIrjbBkd_o.jpg', 'title' => 'Farfalle 03'],
                ['src' => 'https://images2.imgbox.com/49/07/7RaYchq2_o.jpg', 'title' => 'Farfalle 04'],
                ['src' => 'https://images2.imgbox.com/dd/21/EpdXwzB3_o.jpg', 'title' => 'Magic Burgers'],
                ['src' => 'https://images2.imgbox.com/84/15/65gH0FfC_o.jpg', 'title' => 'Magicats'],
                ['src' => 'https://images2.imgbox.com/c2/73/tn9ClnLo_o.jpg', 'title' => 'Magicats Mockup'],
                ['src' => 'https://images2.imgbox.com/9e/41/j0bQmmeE_o.png', 'title' => 'Naturale 01'],
                ['src' => 'https://images2.imgbox.com/6c/56/HVTCu8In_o.jpg', 'title' => 'Naturale 02'],
            ],
            [
                ['src' => 'https://images2.imgbox.com/ab/25/DzrCFScZ_o.jpg', 'title' => "fico d'india"],
                ['src' => 'https://images2.imgbox.com/3c/b0/AA1NDGRF_o.jpg', 'title' => 'Passiflora'],
                ['src' => 'https://images2.imgbox.com/f9/78/O8XGMFex_o.jpg', 'title' => 'Piccioni Paraolimpici'],
                ['src' => 'https://images2.imgbox.com/1e/6e/7tKSHOqZ_o.jpg', 'title' => 'Queer Witches 01'],
                ['src' => 'https://images2.imgbox.com/a0/05/LEw2wtl2_o.jpg', 'title' => 'Queer Witches 02'],
                ['src' => 'https://images2.imgbox.com/34/92/ZKgQXAbZ_o.jpg', 'title' => 'Queer Witches 03'],
                ['src' => 'https://images2.imgbox.com/12/b8/PiX3XSfr_o.jpg', 'title' => 'Rana'],
            ],
            [
                ['src' => 'https://images2.imgbox.com/87/9a/Qm9O9awD_o.jpg', 'title' => 'Studio Ghibli Howl'],
                ['src' => 'https://images2.imgbox.com/09/59/w9dAz8Q3_o.jpg', 'title' => 'Studio Ghibli Howl negativo'],
                ['src' => 'https://images2.imgbox.com/b2/bb/YBarPPNG_o.jpg', 'title' => 'Studio Ghibli Mononoke'],
                ['src' => 'https://images2.imgbox.com/7c/54/V9VOzLhm_o.jpg', 'title' => 'Studio Ghibli Ponyo'],
                ['src' => 'https://images2.imgbox.com/0e/73/q2k0elZm_o.jpg', 'title' => 'Witchtober Retro'],
                ['src' => 'https://images2.imgbox.com/95/09/gPRyaNjW_o.jpg', 'title' => 'Witchtober Moon'],
                ['src' => 'https://images2.imgbox.com/b7/51/ooavLz6l_o.jpg', 'title' => 'Farfalle 02'],
            ],
        ];
    }
}
