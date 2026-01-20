// Petit script rapide à lancer une fois pour remplir les vides
$bookings = $pdo->query("SELECT booking_id FROM bookings WHERE pnr IS NULL")->fetchAll();
foreach ($bookings as $b) {
    $pnr = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    $pdo->prepare("UPDATE bookings SET pnr = ? WHERE booking_id = ?")->execute([$pnr, $b['booking_id']]);
}
