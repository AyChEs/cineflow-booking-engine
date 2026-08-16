<?php

namespace Database\Seeders;

use App\Models\Pelicula;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PeliculaSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Categorías: 1=Acción, 2=Aventura, 3=CienciaFicción, 4=Comedia,
        //             5=Drama, 6=Terror, 7=Suspense, 8=Romance, 9=Animación,
        //             10=Documental, 11=Fantasía, 12=Musical, 13=Crimen, 14=Histórica, 15=Bélica

        $peliculas = [
            [
                'titulo'             => 'Dune: Parte Dos',
                'sinopsis'           => 'Paul Atreides continúa su viaje épico en Arrakis, uniéndose a los Fremen para vengar la traición que destruyó a su familia y liderar una guerra santa que cambiará el universo para siempre. Con la sombra de su propio destino profetizado sobre él, Paul debe elegir entre el amor y el destino de toda la humanidad.',
                'duracion_min'       => 166,
                'classificacio_edad' => 'PG-13',
                'trailer_url'        => 'https://www.youtube.com/watch?v=Way9Dexny3w',
                'poster_path'        => '/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg',
                'categorias'         => [3, 2, 14], // CienciaFicción, Aventura, Histórica
            ],
            [
                'titulo'             => 'Oppenheimer',
                'sinopsis'           => 'La historia del físico teórico J. Robert Oppenheimer y su papel en el desarrollo de la bomba atómica durante el Proyecto Manhattan. Una exploración íntima del hombre que cambió el mundo, sus triunfos científicos, sus dilemas morales y las consecuencias políticas que lo perseguirían el resto de su vida.',
                'duracion_min'       => 180,
                'classificacio_edad' => 'R',
                'trailer_url'        => 'https://www.youtube.com/watch?v=uYPbbksJxIg',
                'poster_path'        => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg',
                'categorias'         => [14, 5, 15], // Histórica, Drama, Bélica
            ],
            [
                'titulo'             => 'Alien: Romulus',
                'sinopsis'           => 'Un grupo de jóvenes colonizadores del espacio profundo se enfrenta a la forma de vida más aterradora del universo mientras exploran los oscuros rincones de una estación espacial abandonada. Entre las sombras de los corredores metálicos, algo antiguo y letal despierta y los jóvenes deberán luchar por su supervivencia.',
                'duracion_min'       => 119,
                'classificacio_edad' => 'R',
                'trailer_url'        => 'https://www.youtube.com/watch?v=YGMECb-QgDA',
                'poster_path'        => '/b33nnKl1GSFbao4l3fZDDqsMx0F.jpg',
                'categorias'         => [6, 3], // Terror, CienciaFicción
            ],
            [
                'titulo'             => 'Inside Out 2',
                'sinopsis'           => 'Riley entra en la adolescencia y su mente debe hacer espacio para nuevas emociones que llegan de forma inesperada: Ansiedad, Envidia, Aburrimiento y Nostalgia. Alegría y sus amigos deben ahora trabajar junto con esta nueva generación de emociones para ayudar a Riley a afrontar los retos del crecimiento.',
                'duracion_min'       => 100,
                'classificacio_edad' => 'PG',
                'trailer_url'        => 'https://www.youtube.com/watch?v=LEjhY15eCx0',
                'poster_path'        => '/vpnVM9B6NMmQpWeZvzLvDESb2QE.jpg',
                'categorias'         => [9, 4, 5], // Animación, Comedia, Drama
            ],
            [
                'titulo'             => 'Mufasa: El Rey León',
                'sinopsis'           => 'El origen de Mufasa, el legendario rey de las Tierras del Orgullo. Un joven Mufasa, huérfano y solo, conoce a un simpático pero inusual grupo de animales y, junto a la guía de un extraño león llamado Taka (que llegará a ser conocido como Scar), comienza un épico viaje para descubrir su verdadero destino.',
                'duracion_min'       => 118,
                'classificacio_edad' => 'PG',
                'trailer_url'        => 'https://www.youtube.com/watch?v=bEd7F-FLjOU',
                'poster_path'        => '/lurEK87kukWNaHd0zYnsg3yHkVg.jpg',
                'categorias'         => [9, 2, 11], // Animación, Aventura, Fantasía
            ],
            [
                'titulo'             => 'Gladiator II',
                'sinopsis'           => 'Años después de los eventos del film original, Lucius, hijo de Lucilla, es capturado y llevado a Roma como esclavo gladiador. Testigo de la corrupción y la tiranía de los nuevos emperadores, encuentra en la arena el camino hacia la venganza y la libertad, portando el legado del hombre que fue padre de la nación.',
                'duracion_min'       => 148,
                'classificacio_edad' => 'R',
                'trailer_url'        => 'https://www.youtube.com/watch?v=luaBVPHGGwc',
                'poster_path'        => '/2cxhvwyEwRlysAmRH4iodkvo0z5.jpg',
                'categorias'         => [1, 14, 5], // Acción, Histórica, Drama
            ],
            [
                'titulo'             => 'Wicked',
                'sinopsis'           => 'El improbable e inmortal amistado entre Elphaba, una joven incomprendida con un don extraordinario para la magia, y Glinda, una popular joven con ambiciones propias. Juntas descubren la verdad oculta detrás del mágico mundo de Oz en esta adaptación del aclamado musical de Broadway.',
                'duracion_min'       => 160,
                'classificacio_edad' => 'PG',
                'trailer_url'        => 'https://www.youtube.com/watch?v=6COmYeLsz4c',
                'poster_path'        => '/c5Tqxeo1UpBvnAc3csUm7j3hlQl.jpg',
                'categorias'         => [12, 11, 8], // Musical, Fantasía, Romance
            ],
            [
                'titulo'             => 'Nosferatu',
                'sinopsis'           => 'Una nueva y oscura visión del clásico cuento del conde Orlok, una terrible criatura de la noche que obsesiona a una joven mujer con consecuencias devastadoras para todos los que la rodean. Desde las brumosas montañas de Transilvania hasta las calles de Wisborg, el horror acecha en las sombras.',
                'duracion_min'       => 132,
                'classificacio_edad' => 'R',
                'trailer_url'        => 'https://www.youtube.com/watch?v=uk1UDZOoGME',
                'poster_path'        => '/5qGIxdEO841C7m1EYvAiBxXsvY6.jpg',
                'categorias'         => [6, 11], // Terror, Fantasía
            ],
        ];

        foreach ($peliculas as $data) {
            $categorias = $data['categorias'];
            unset($data['categorias']);

            $pelicula = Pelicula::updateOrCreate(['titulo' => $data['titulo']], $data);
            $pelicula->categorias()->syncWithoutDetaching($categorias);
        }
    }
}
