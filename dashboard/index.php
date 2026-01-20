<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AirControl - Gestion et Audit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 text-gray-900">

<?php include 'nav.php'; ?>

<main class="container mx-auto mt-8 p-4">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
        <?php
        $stats = [
            'Total des Vols' => ['val' => $pdo->query("SELECT COUNT(*) FROM flights")->fetchColumn(), 'icon' => 'fa-plane', 'color' => 'blue'],
            'Passagers Transportés' => ['val' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(), 'icon' => 'fa-users', 'color' => 'indigo'],
            'Bagages Enregistrés' => ['val' => $pdo->query("SELECT COUNT(*) FROM baggage")->fetchColumn(), 'icon' => 'fa-suitcase', 'color' => 'purple'],
            'Appareils en Flotte' => ['val' => $pdo->query("SELECT COUNT(*) FROM aircrafts")->fetchColumn(), 'icon' => 'fa-shuttle-van', 'color' => 'green']
        ];

        foreach ($stats as $label => $data): ?>
            <div class="bg-white p-6 rounded-xl shadow-sm border-t-4 border-<?= $data['color'] ?>-500">
                <div class="flex items-center gap-4">
                    <div class="text-2xl text-<?= $data['color'] ?>-500 w-10 text-center"><i class="fas <?= $data['icon'] ?>"></i></div>
                    <div>
                        <p class="text-xs text-gray-400 font-bold uppercase"><?= $label ?></p>
                        <p class="text-2xl font-black"><?= number_format($data['val'], 0, ',', ' ') ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 gap-8">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-blue-600 p-4">
                <h3 class="text-white font-bold flex items-center gap-2 uppercase tracking-wide">
                    <i class="fa-solid fa-clipboard-check"></i>
                    Demandes de la Direction Opérationnelle
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <div class="bg-gray-50 p-5 rounded-lg border-l-4 border-blue-600">
                    <h4 class="font-bold text-blue-800 mb-2">1. Analyse de la Ponctualité Globale</h4>
                    <p class="text-sm text-gray-600 mb-4">La direction souhaite connaître le pourcentage exact de vols arrivés à l'heure (sans aucun retard enregistré) sur l'année.</p>
                    <div class="bg-white p-3 rounded border border-gray-200 font-mono text-xs">
                        <span class="text-red-600 font-bold">Objectif SQL :</span> Calculer le ratio entre le nombre de vols sans retard dans la table <code>flight_delays</code> et le total de la table <code>flights</code>.
                    </div>
                </div>

                <div class="bg-gray-50 p-5 rounded-lg border-l-4 border-blue-600">
                    <h4 class="font-bold text-blue-800 mb-2">2. Impact des Retards en Cascade</h4>
                    <p class="text-sm text-gray-600 mb-4">Quel est le cumul total (en minutes) des retards dus uniquement à la répercussion d'un vol précédent (Code IATA 89) ?</p>
                    <div class="bg-white p-3 rounded border border-gray-200 font-mono text-xs">
                        <span class="text-red-600 font-bold">Objectif SQL :</span> Effectuer la somme de la colonne <code>delay_minutes</code> pour le code_id 89.
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-green-700 p-4">
                <h3 class="text-white font-bold flex items-center gap-2 uppercase tracking-wide">
                    <i class="fa-solid fa-sack-dollar"></i>
                    Rapport de Rentabilité Commerciale
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="bg-gray-50 p-5 rounded-lg border-t-4 border-green-600">
                    <h4 class="font-bold text-gray-800 mb-2">Top 5 des Destinations</h4>
                    <p class="text-sm text-gray-600 mb-4 italic">Quelles sont les 5 routes qui ont généré le plus de chiffre d'affaires (somme des prix payés) ?</p>
                    <p class="text-[11px] text-gray-400 font-bold uppercase">Tables : bookings, flights, routes</p>
                </div>

                <div class="bg-gray-50 p-5 rounded-lg border-t-4 border-green-600">
                    <h4 class="font-bold text-gray-800 mb-2">Analyse par Appareil</h4>
                    <p class="text-sm text-gray-600 mb-4 italic">Pour l'avion immatriculé 'F-HNET', quel est son taux de remplissage moyen (passagers réels vs sièges disponibles) ?</p>
                    <p class="text-[11px] text-gray-400 font-bold uppercase">Tables : aircrafts, seats, bookings</p>
                </div>

                <div class="bg-gray-50 p-5 rounded-lg border-t-4 border-green-600">
                    <h4 class="font-bold text-gray-800 mb-2">Panier Moyen Passager</h4>
                    <p class="text-sm text-gray-600 mb-4 italic">Calculez le prix moyen payé pour un billet d'avion, toutes classes confondues.</p>
                    <p class="text-[11px] text-gray-400 font-bold uppercase">Tables : bookings</p>
                </div>

            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-10">
            <div class="bg-red-700 p-4 text-white flex justify-between">
                <h3 class="font-bold uppercase tracking-wide">Audit de Maintenance Flotte</h3>
                <span class="text-xs font-bold bg-red-800 px-2 py-1 rounded">Prioritaire</span>
            </div>
            <div class="p-6 italic font-medium">
                <p class="text-sm text-gray-700 mb-4">Le service technique a besoin d'identifier les appareils posant des problèmes récurrents :</p>
                <div class="bg-red-50 p-4 border border-red-100 rounded text-sm text-red-900">
                    "Veuillez lister les immatriculations des appareils (aircraft_id) ayant cumulé plus de 10 incidents de type <strong>Technique</strong> (Codes IATA compris entre 41 et 48) sur la période."
                </div>
            </div>
        </div>

    </div>
</main>

</body>
</html>
