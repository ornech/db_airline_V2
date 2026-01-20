<?php 
require_once 'config.php'; 

$aircraft_id = $_GET['id'] ?? null;
if (!$aircraft_id) { die("ID de l'aéronef manquant."); }

// 1. Infos techniques avec vos noms de colonnes
$stmt = $pdo->prepare("
    SELECT a.*, m.model_name, m.manufacturer, m.capacity_eco, m.capacity_bus, 
           m.fuel_burn_per_hour, m.maintenance_interval_hours
    FROM aircrafts a
    JOIN aircraft_models m ON a.model_id = m.model_id
    WHERE a.aircraft_id = ?
");
$stmt->execute([$aircraft_id]);
$plane = $stmt->fetch();

if (!$plane) { die("Appareil introuvable."); }

// 2. Statistiques d'utilisation
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_flights,
        SUM(r.distance_km) as total_distance,
        SUM(r.avg_flight_duration_minutes) as total_minutes
    FROM flights f
    JOIN routes r ON f.route_id = r.route_id
    WHERE f.aircraft_id = ? AND f.status = 'ARRIVED'
");
$stmt_stats->execute([$aircraft_id]);
$stats = $stmt_stats->fetch();

$total_min = $stats['total_minutes'] ?? 0;
$hours = floor($total_min / 60);
$minutes = $total_min % 60;

// Calcul conso : (total minutes / 60) * fuel par heure
$total_fuel = ($total_min / 60) * $plane['fuel_burn_per_hour'];

// 3. Historique des vols
$stmt_history = $pdo->prepare("
    SELECT f.*, r.departure_airport, r.arrival_airport, r.distance_km
    FROM flights f
    JOIN routes r ON f.route_id = r.route_id
    WHERE f.aircraft_id = ?
    ORDER BY f.scheduled_departure DESC
");
$stmt_history->execute([$aircraft_id]);
$history = $stmt_history->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Log Technique - <?= $plane['registration'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900">

    <nav class="bg-blue-900 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <a href="rotations.php" class="text-sm font-bold bg-blue-800 px-4 py-2 rounded hover:bg-blue-700 transition">← Retour Rotations</a>
            <h1 class="font-black tracking-tighter italic text-xl">TECH LOG : <?= $plane['registration'] ?></h1>
        </div>
    </nav>

    <main class="container mx-auto mt-8 p-4">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-2xl mb-4">✈</div>
                <h2 class="text-4xl font-black text-slate-800 tracking-tighter"><?= $plane['registration'] ?></h2>
                <p class="text-blue-600 font-bold uppercase tracking-widest text-xs mb-6"><?= $plane['manufacturer'] ?> <?= $plane['model_name'] ?></p>
                
                <div class="grid grid-cols-2 gap-4 w-full border-t pt-6 text-left">
                    <div class="bg-slate-50 p-3 rounded-xl">
                        <p class="text-[9px] font-bold text-slate-400 uppercase">Eco Class</p>
                        <p class="font-black text-slate-700"><?= $plane['capacity_eco'] ?> seats</p>
                    </div>
                    <div class="bg-purple-50 p-3 rounded-xl">
                        <p class="text-[9px] font-bold text-purple-400 uppercase">Business</p>
                        <p class="font-black text-purple-700"><?= $plane['capacity_bus'] ?> seats</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 bg-slate-900 text-white p-8 rounded-3xl shadow-xl flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10 text-8xl font-black italic">DATA</div>
                
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mb-4">Indicateurs de Maintenance</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative z-10">
                    <div>
                        <p class="text-4xl font-black text-blue-400"><?= $hours ?>h <?= $minutes ?>m</p>
                        <p class="text-xs text-slate-400 uppercase font-bold mt-1">Heures de vol</p>
                    </div>
                    <div>
                        <p class="text-4xl font-black text-green-400"><?= number_format($stats['total_distance'], 0, ',', ' ') ?> km</p>
                        <p class="text-xs text-slate-400 uppercase font-bold mt-1">Total Distance</p>
                    </div>
                    <div>
                        <p class="text-4xl font-black text-orange-400"><?= number_format($total_fuel, 0, ',', ' ') ?> L</p>
                        <p class="text-xs text-slate-400 uppercase font-bold mt-1">Carburant brûlé</p>
                    </div>
                </div>

                <div class="mt-8 bg-slate-800 p-5 rounded-2xl border border-slate-700 relative z-10">
                    <div class="flex justify-between text-xs mb-3">
                        <span class="font-bold uppercase tracking-widest text-slate-400">Prochaine révision (Intervalle: <?= $plane['maintenance_interval_hours'] ?>h)</span>
                        <span class="font-black text-blue-400"><?= round(($hours / $plane['maintenance_interval_hours']) * 100, 1) ?>%</span>
                    </div>
                    <div class="w-full h-3 bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 shadow-[0_0_15px_rgba(59,130,246,0.5)]" style="width: <?= ($hours / $plane['maintenance_interval_hours']) * 100 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-black text-xl uppercase tracking-tighter italic">Journal des rotations</h3>
                <span class="bg-blue-600 text-white px-4 py-1 rounded-full text-[10px] font-black uppercase"><?= $stats['total_flights'] ?> Atterrissages</span>
            </div>
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-[10px] font-bold text-slate-400 uppercase border-b border-slate-100">
                    <tr>
                        <th class="p-5">Date Départ</th>
                        <th class="p-5">N° Vol</th>
                        <th class="p-5">Itinéraire</th>
                        <th class="p-5">Distance</th>
                        <th class="p-5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($history as $h): ?>
                    <tr class="hover:bg-blue-50/50 transition group">
                        <td class="p-5 text-sm font-bold text-slate-600">
                            <?= date('d/m/Y H:i', strtotime($h['scheduled_departure'])) ?>
                        </td>
                        <td class="p-5 font-black text-blue-600 italic"><?= $h['flight_number'] ?></td>
                        <td class="p-5 font-bold">
                            <?= $h['departure_airport'] ?> <span class="text-slate-300 mx-2">→</span> <span class="text-blue-600"><?= $h['arrival_airport'] ?></span>
                        </td>
                        <td class="p-5 text-xs font-mono text-slate-400"><?= $h['distance_km'] ?> km</td>
                        <td class="p-5 text-right">
                            <a href="vol_details.php?id=<?= $h['flight_id'] ?>" class="inline-block bg-slate-900 text-white px-4 py-2 rounded-lg text-[10px] font-black uppercase hover:bg-blue-600 transition shadow-sm">Audit Vol</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
