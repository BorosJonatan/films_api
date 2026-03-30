<?php


namespace App\Database;

use PDO;

class CreateDB extends Database{
    public function Exists(): bool
    {
        $result = $this->execSql(
            "SELECT SCHEMA_NAME 
             FROM INFORMATION_SCHEMA.SCHEMATA 
             WHERE SCHEMA_NAME = ?",
            [self::DEFAULT_CONFIG['database']]
        );
    
        return !empty($result);
    }
    
    public function Create(): void
    {
        $dbName = self::DEFAULT_CONFIG['database'];
    
        $this->getPdo()->exec(
            "CREATE DATABASE `$dbName` 
             CHARACTER SET utf8mb4 
             COLLATE utf8mb4_unicode_ci"
        );
    }
    
    public function Fill(){

    try {
        $this->beginTransaction();

        // --- Categories ---
        $categories = ["Action", "Drama", "Comedy", "Sci-Fi", "Horror", "Fantasy", "Romance", "Thriller"];
        foreach ($categories as $cat) {
            $this->execSql("INSERT INTO categories (name) VALUES (?)", [$cat]);
        }

        // --- Directors ---
        $directors = [
            "Christopher Nolan", "Steven Spielberg", "Quentin Tarantino",
            "James Cameron", "Ridley Scott", "Peter Jackson"
        ];
        foreach ($directors as $dir) {
            $this->execSql("INSERT INTO directors (name) VALUES (?)", [$dir]);
        }
        $titles = [
            "Echoes in the Static", "Paper Suns", "The Last Frequency", "City of Hollow Lights",
            "Midnight on the Third Floor", "Rust and Rain", "The Sparrow's Code",
            "Neon Dust", "Between Two Storms", "The House That Forgot"
        ];
        // --- Actors ---
        $actors = [
            "Tom Hanks", "Scarlett Johansson", "Brad Pitt", "Leonardo DiCaprio",
            "Emma Stone", "Chris Hemsworth", "Jennifer Lawrence",
            "Robert Downey Jr.", "Margot Robbie", "Keanu Reeves"
        ];
        foreach ($actors as $act) {
            $this->execSql("INSERT INTO actors (name) VALUES (?)", [$act]);
        }

        // --- Films ---
        $studios = ["Warner Bros", "Paramount", "Universal", "20th Century Fox", "Lionsgate", "Marvel Studios"];
        $languages = ["English", "Spanish", "French", "German", "Japanese", "Hungarian"];
        $picts = ["https://preview.thenewsmarket.com/Previews/LBOC/StillAssets/1920x1080/685314.jpg",
        'https://substackcdn.com/image/fetch/$s_!AXSu!,f_auto,q_auto:good,fl_progressive:steep/https%3A%2F%2Fsubstack-post-media.s3.amazonaws.com%2Fpublic%2Fimages%2Fadde083b-73d8-4a7a-91a5-253fbffde229_964x789.png', 
        "https://deadline.com/wp-content/uploads/2023/08/billion-gallery.jpg?w=681&h=383&crop=1"];

        for ($i = 0; $i <= 9; $i++) {
            $name = $titles[$i];
            $studio = $studios[array_rand($studios)];
            $director_id = rand(1, count($directors));
            $category_id = rand(1, count($categories));
            $age_restr = [0, 6, 12, 16, 18][array_rand([0, 6, 12, 16, 18])];
            $lang = $languages[array_rand($languages)];
            $subtitle = rand(0, 1);
            $text = "Film ". $i+1 . " — a thrilling $lang-language story produced by $studio.";
            $pict = $picts[array_rand($picts)];

            $film_id = $this->execSql(
                "INSERT INTO films (name, studio, director_id, category_id, age_restr, lang, subtitle, text, picts)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$name, $studio, $director_id, $category_id, $age_restr, $lang, $subtitle, $text, $pict]
            );

            // --- Link random actors to this film ---
            $numActors = rand(2, 4);
            $actorIds = array_rand(array_flip(range(1, count($actors))), $numActors);
            if (!is_array($actorIds)) $actorIds = [$actorIds];
            foreach ($actorIds as $actor_id) {
                $this->execSql("INSERT INTO film_actor (film_id, actor_id) VALUES (?, ?)", [$film_id, $actor_id]);
            }
            // fill the ratings
            
            for($j = 0; $j < 6; $j++){
                $this->execSql("INSERT INTO film_ratings (film_id, rating) VALUES (?, ?)", [$film_id, rand(1,5)]);
            }
        }

        $this->commit();
        echo "✅ Database successfully filled with sample data.";
    } catch (Exception $e) {
        $this->rollback();
        echo "❌ Error while filling database: " . $e->getMessage();
    }
    }
}