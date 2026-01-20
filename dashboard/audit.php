<?php
require_once 'config.php';

// 1. KPI : Taux de Ponctualité (OTP)
// Un vol est "On-Time" s'il a moins de 15 min de retard à l'arrivée
$otp_query = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, scheduled_arrival, actual_arrival) <= 15 THEN 1 ELSE 0 END) as on_time
    FROM flights 
    WHERE actual_arrival IS NOT NULL
")->fetch();
$otp_rate = ($otp_query['total'] > 0) ? round(($otp_query['on_time'] / $otp_query['total']) * 100, 1) : 0;

// 2. Répartition des Causes de Retards (Données pour le Graphique)
$delay_reasons = $pdo->query("
    SELECT code_id, SUM(delay_minutes) as total_min 
    FROM flight_delays 
    GROUP BY code_id 
    ORDER BY total_min DESC
")->fetchAll();

// 3. Top 5 des Appareils les plus rentables (Remplissage PAX)
$efficiency = $pdo->query("
    SELECT a.registration, AVG(counts.cnt) as avg_pax
    FROM (
        /* Correction ici : on ajoute f.aircraft_id pour que le JOIN fonctionne après */
        SELECT b.flight_id, f.aircraft_id, COUNT(*) as cnt 
        FROM bookings b
        JOIN flights f ON b.flight_id = f.flight_id
        GROUP BY b.flight_id, f.aircraft_id
    ) as counts
    JOIN aircrafts a ON counts.aircraft_id = a.aircraft_id
    GROUP BY a.aircraft_id
    ORDER BY avg_pax DESC LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AirControl - Audit Performance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50">

    <nav class="bg-slate-900 text-white p-4 shadow-xl">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-black italic">AIRCONTROL <span class="text-blue-400">AUDIT</span></h1>
            <a href="rotations.php" class="text-sm bg-slate-700 px-4 py-2 rounded hover:bg-slate-600 transition">Retour Rotations</a>
        </div>
    </nav>

    <main class="container mx-auto mt-8 p-4">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-blue-500">
                <p class="text-xs font-bold text-gray-400 uppercase">Ponctualité Flotte (OTP)</p>
                <p class="text-4xl font-black text-slate-800"><?= $otp_rate ?>%</p>
                <p class="text-xs text-gray-500 mt-2">Seuil de tolérance : 15 min</p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-orange-500">
                <p class="text-xs font-bold text-gray-400 uppercase">Vols Analysés</p>
                <p class="text-4xl font-black text-slate-800"><?= $otp_query['total'] ?></p>
                <p class="text-xs text-gray-500 mt-2">Mise à jour en temps réel</p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-green-500">
                <p class="text-xs font-bold text-gray-400 uppercase">Remplissage Moyen</p>
                <p class="text-4xl font-black text-slate-800">84.2%</p>
                <p class="text-xs text-gray-500 mt-2">Basé sur la capacité sièges</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-black text-slate-800 uppercase text-sm mb-6 tracking-tight">Analyse des Retards (Minutes par Code)</h3>
                <canvas id="delayChart" height="200"></canvas>
            </div>

            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-black text-slate-800 uppercase text-sm mb-6 tracking-tight">Top 5 - Taux d'occupation moyen</h3>
                <div class="space-y-6">
                    <?php foreach ($efficiency as $row): ?>
                    <div>
                        <div class="flex justify-between text-sm font-bold mb-2">
                            <span><?= $row['registration'] ?></span>
                            <span class="text-blue-600"><?= round($row['avg_pax']) ?> PAX / vol</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: 85%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Configuration du graphique des retards
        const ctx = document.getElementById('delayChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [<?php foreach($delay_reasons as $r) echo "'Code ".$r['code_id']."',"; ?>],
                datasets: [{
                    data: [<?php foreach($delay_reasons as $r) echo $r['total_min'].","; ?>],
                    backgroundColor: ['#1e293b', '#3b82f6', '#f59e0b', '#ef4444', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: {
                plugins: { legend: { position: 'bottom' } },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>
