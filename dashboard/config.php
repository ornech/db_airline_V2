<?php
// config.php - Connexion centrale à la base de données

$host = 'localhost';
$db   = 'db_airline_V4';
$user = 'admin';       // L'utilisateur que vous utilisez dans Python
$pass = 'admin';       // Le mot de passe associé
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Pour voir les erreurs SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Pour manipuler des tableaux propres
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Établissement de la connexion
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // On force la session en UTC pour être raccord avec la simulation Python
    $pdo->exec("SET time_zone = '+00:00'");

} catch (\PDOException $e) {
    // Si la connexion échoue, on arrête tout et on affiche pourquoi
    die("❌ Erreur de connexion : " . $e->getMessage());
}
