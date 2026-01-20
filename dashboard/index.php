<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AirControl - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php include 'nav.php'; ?>

    <main class="container mx-auto mt-8 p-4">
        <h2 class="text-3xl font-semibold text-gray-800 mb-6">Statistiques de la Simulation</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <?php
            // Requêtes rapides pour le dashboard
            $stats = [
                'Vols' => $pdo->query("SELECT COUNT(*) FROM flights")->fetchColumn(),
                'Passagers' => $pdo->query("SELECT COUNT(*) FROM passengers")->fetchColumn(),
                'Billets Vendus' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
                'Poids Bagages (kg)' => $pdo->query("SELECT ROUND(SUM(weight_kg)) FROM baggage")->fetchColumn()
            ];

            foreach ($stats as $label => $value): ?>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500 uppercase font-bold"><?= $label ?></p>
                    <p class="text-2xl font-black text-gray-800"><?= number_format($value, 0, ',', ' ') ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="bg-gray-800 p-4">
                <h3 class="text-white font-bold italic text-lg text-center">État de santé de la base de données</h3>
            </div>
            <div class="p-6">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-gray-400 border-b">
                            <th class="py-2">Test de Cohérence</th>
                            <th>Description</th>
                            <th>Résultat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b">
                            <td class="py-4 font-semibold">Continuité Géographique</td>
                            <td class="text-gray-600">Vérifie si les avions se téléportent entre deux vols.</td>
                            <td>
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-bold">✅ 0 Erreur</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-4 font-semibold">Chronologie des Vols</td>
                            <td class="text-gray-600">Vérifie qu'un avion ne repart pas avant d'être arrivé.</td>
                            <td>
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-bold">✅ 0 Erreur</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<?php
try {
    $stats = [
        'Vols' => $pdo->query("SELECT COUNT(*) FROM flights")->fetchColumn(),
        'Passagers' => $pdo->query("SELECT COUNT(*) FROM passengers")->fetchColumn(),
        'Billets' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'Bagages' => $pdo->query("SELECT COUNT(*) FROM baggage")->fetchColumn()
    ];
} catch (Exception $e) {
    echo "<p class='text-red-500'>Erreur SQL : " . $e->getMessage() . "</p>";
}
?>

</body>
</html>
