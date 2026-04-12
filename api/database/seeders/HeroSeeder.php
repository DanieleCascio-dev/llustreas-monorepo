<?php

namespace Database\Seeders;

use App\Models\HeroLayer;
use App\Models\HeroLayerImage;
use Illuminate\Database\Seeder;

/**
 * Crea i 3 layer hero iniziali con placeholder.
 * Le immagini devono essere caricate su Cloudinary (folder illustreas/hero/)
 * e i src aggiornati dal backoffice.
 */
class HeroSeeder extends Seeder
{
    public function run(): void
    {
        $cloudBase = 'https://res.cloudinary.com/djcp3ixbw/image/upload/illustreas/hero';

        // Layer 0: Sfondo (z:0, speed lento)
        $bg = HeroLayer::create([
            'name'           => 'Sfondo',
            'type'           => 'image',
            'z_index'        => 0,
            'parallax_speed' => 0.5,
            'breakpoint'     => 450,
            'order'          => 0,
        ]);

        HeroLayerImage::create([
            'hero_layer_id' => $bg->id,
            'mobile_src'    => "{$cloudBase}/IMMAGINE_SFONDO_MOBILE.png",
            'desktop_src'   => "{$cloudBase}/IMMAGINE_SFONDO_DESKTOP.png",
            'alt'           => 'Sfondo hero',
            'order'         => 0,
        ]);

        // Layer 1: Scritte titolo (z:1, speed medio, slideshow)
        $titles = HeroLayer::create([
            'name'           => 'Scritte',
            'type'           => 'slideshow',
            'z_index'        => 1,
            'parallax_speed' => 0.25,
            'breakpoint'     => 576,
            'order'          => 1,
        ]);

        $titleSlides = [
            ['5_SCRITTA_01.svg', '5_SCRITTA_01_DESKTOP.svg', 'Scritta titolo 1'],
            ['5_SCRITTA_02.svg', '5_SCRITTA_02_DESKTOP.svg', 'Scritta titolo 2'],
            ['5_SCRITTA_03.svg', '5_SCRITTA_03_DESKTOP.svg', 'Scritta titolo 3'],
        ];

        foreach ($titleSlides as $i => [$mob, $desk, $alt]) {
            HeroLayerImage::create([
                'hero_layer_id' => $titles->id,
                'mobile_src'    => "{$cloudBase}/{$mob}",
                'desktop_src'   => "{$cloudBase}/{$desk}",
                'alt'           => $alt,
                'order'         => $i,
            ]);
        }

        // Layer 2: Fiori primo piano (z:2, speed naturale = 0)
        $flowers = HeroLayer::create([
            'name'           => 'Fiori',
            'type'           => 'image',
            'z_index'        => 2,
            'parallax_speed' => 0,
            'breakpoint'     => 450,
            'order'          => 2,
        ]);

        HeroLayerImage::create([
            'hero_layer_id' => $flowers->id,
            'mobile_src'    => "{$cloudBase}/1_FIORI.png",
            'desktop_src'   => "{$cloudBase}/1_FIORI_DESKTOP.png",
            'alt'           => 'Fiori primo piano',
            'order'         => 0,
        ]);
    }
}
