<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Database\CreateDB;

// 1️⃣ connect WITHOUT database
$db = new CreateDB([
    "host" => "localhost",
    "user" => "root",
    "password" => ""
]);

if (!$db->Exists()) {
    echo "Creating database...\n";
    $db->Create();
}

// 2️⃣ reconnect WITH database
$db = new CreateDB([
    "host" => "localhost",
    "user" => "root",
    "password" => "",
    "database" => "films"
]);

$db->getPdo()->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);
$db->getPdo()->exec(file_get_contents(__DIR__ . "\db.sql"));
$db->getPdo()->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, false);

$db->Fill();

echo "✅ Database fully initialized\n";
