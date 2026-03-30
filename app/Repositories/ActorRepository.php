<?php
namespace App\Repositories;

class ActorRepository extends BaseRepository
{
    public string $tableName = 'actors';

    public function create(array $data): ?int
    {
        $fields = implode(',', array_keys($data));
        $values = implode(',', array_map(fn($v) => "'$v'", $data));

        $sql = sprintf("INSERT INTO `%s` (%s) VALUES (%s)", $this->tableName, $fields, $values);
        $this->mysqli->query($sql);

        $lastInserted = $this->mysqli->query("SELECT LAST_INSERT_ID() actor_id;")->fetch_assoc();
        return $lastInserted['actor_id'] ?? null;
    }

    public function find(int $id): array
    {
        $query = $this->select() . "WHERE actor_id = $id";
        return $this->mysqli->query($query)->fetch_assoc() ?? [];
    }

    public function getAll(): array
    {
        $query = $this->select() . "ORDER BY name";
        return $this->mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
    }

    public function update(int $id, array $data)
    {
        $set = implode(', ', array_map(fn($f, $v) => "$f = '$v'", array_keys($data), $data));
        $query = sprintf("UPDATE `%s` SET %s WHERE actor_id = %d", $this->tableName, $set, $id);
        $this->mysqli->query($query);

        return $this->find($id);
    }

    public function delete(int $id)
    {
        $query = sprintf("DELETE FROM `%s` WHERE actor_id = %d", $this->tableName, $id);
        return $this->mysqli->query($query);
    }

    public function getByActor(int $filmId): array
    {
        $query = $this->select() . " JOIN film_actor ON actors.actor_id = film_actor.film_id 
                                    JOIN films ON film_actor.actor_id = films.film_id WHERE 
                                    films.film_id = $filmId ORDER BY actors.name";
        return $this->mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
    }
}