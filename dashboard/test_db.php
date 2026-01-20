<?php
require_once 'config.php';

echo "<h2>Diagnostic de la base :</h2>";
$tables = ['flights', 'bookings', 'baggage', 'passengers'];

foreach ($tables as $t) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo "Table <b>$t</b> : $count lignes.<br>";
    } catch (Exception $e) {
        echo "Table <b>$t</b> : <span style='color:red'>Erreur (Table introuvable ?)</span><br>";
    }
}
?>
