<?php
namespace App\Repositories;

use function PHPSTORM_META\type;

class FilmRepository extends BaseRepository
{
    public string $tableName = 'films';

    public function create(array $data): ?int
    {
        $fields = implode(',', array_keys($data));
        $values = implode(',', array_map(fn($v) => "'$v'", $data));

        $sql = sprintf("INSERT INTO `%s` (%s) VALUES (%s)", $this->tableName, $fields, $values);
        $this->mysqli->query($sql);

        $lastInserted = $this->mysqli->query("SELECT LAST_INSERT_ID() film_id;")->fetch_assoc();
        return $lastInserted['film_id'] ?? null;
    }

    public function find(int $id): array
    {
        $query = $this->select() . "WHERE film_id = $id";
        return $this->mysqli->query($query)->fetch_assoc() ?? [];
    }

    public function getAll($needle = null): array
    {
        $searchString = "";
        
        if ($needle){
            $searchString = "WHERE (f.name LIKE '%{$needle}%' 
                                    OR d.name LIKE '%{$needle}%' 
                                    OR c.name LIKE '%{$needle}%'
                                    OR f.studio LIKE '%{$needle}%'
                                    OR f.age_restr LIKE '%{$needle}%'
                                    OR f.lang LIKE '%{$needle}%'
                                    OR f.text LIKE '%{$needle}%'
                                    OR f.picts LIKE '%{$needle}%'
                                    )";
        }
        $query = "SELECT f.*, c.name as category, d.name as director FROM films f 
        JOIN categories c ON c.category_id = f.category_id
        JOIN directors d ON d.director_id = f.director_id {$searchString} ORDER BY f.name;";

        return $this->mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
    }

    public function update(int $id, array $data)
    {
        $set = implode(', ', array_map(fn($f, $v) => "$f = '$v'", array_keys($data), $data));
        $query = sprintf("UPDATE `%s` SET %s WHERE film_id = %d", $this->tableName, $set, $id);
        $this->mysqli->query($query);

        return $this->find($id);
    }

    public function delete(int $id)
    {
        $query = sprintf("DELETE FROM `%s` WHERE film_id = %d", $this->tableName, $id);
        return $this->mysqli->query($query);
    }

    public function getByFilm(int $actorId): array
    {
        $query = $this->select() . "JOIN film_actor ON films.film_id = film_actor.film_id 
                                    JOIN actors ON film_actor.actor_id = actors.actor_id
                                    WHERE actors.actor_id = $actorId ORDER BY films.name";
        return $this->mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
    }
}