<?php 
require_once 'config.php'; 

$flight_id = $_GET['id'] ?? null;
if (!$flight_id) { die("ID du vol manquant."); }

// 1. Données complètes (Vols + Routes + Avion + Retards)
$stmt = $pdo->prepare("
    SELECT f.*, r.*, a.registration, m.model_name, a.aircraft_id,
           fd.delay_minutes, fd.code_id as delay_code
    FROM flights f
    JOIN routes r ON f.route_id = r.route_id
    JOIN aircrafts a ON f.aircraft_id = a.aircraft_id
    JOIN aircraft_models m ON a.model_id = m.model_id
    LEFT JOIN flight_delays fd ON f.flight_id = fd.flight_id
    WHERE f.flight_id = ?
");
$stmt->execute([$flight_id]);
$vol = $stmt->fetch();

// 2. Navigation Chronologique
$stmt_prev = $pdo->prepare("SELECT f.flight_id, f.flight_number, r.arrival_airport FROM flights f JOIN routes r ON f.route_id = r.route_id WHERE aircraft_id = ? AND f.scheduled_departure < ? ORDER BY f.scheduled_departure DESC LIMIT 1");
$stmt_prev->execute([$vol['aircraft_id'], $vol['scheduled_departure']]);
$prev_vol = $stmt_prev->fetch();

$stmt_next = $pdo->prepare("SELECT f.flight_id, f.flight_number, r.arrival_airport FROM flights f JOIN routes r ON f.route_id = r.route_id WHERE aircraft_id = ? AND f.scheduled_departure > ? ORDER BY f.scheduled_departure ASC LIMIT 1");
$stmt_next->execute([$vol['aircraft_id'], $vol['scheduled_departure']]);
$next_vol = $stmt_next->fetch();

// 3. Équipage (Pilotes et PNC)
$stmt_crew = $pdo->prepare("
    SELECT e.first_name, e.last_name, e.role 
    FROM flight_crew fc 
    JOIN employees e ON fc.employee_id = e.employee_id 
    WHERE fc.flight_id = ?
    ORDER BY CASE WHEN e.role LIKE '%PILOT%' OR e.role LIKE '%CAPTAIN%' THEN 1 ELSE 2 END
");
$stmt_crew->execute([$flight_id]);
$crew = $stmt_crew->fetchAll();

// 4. Passagers
$passagers = $pdo->prepare("SELECT b.*, p.prenom, p.nom, bg.tag_number, bg.weight_kg FROM bookings b JOIN passengers p ON b.passenger_id = p.passenger_id LEFT JOIN baggage bg ON b.booking_id = bg.booking_id WHERE b.flight_id = ? ORDER BY b.seat_number");
$passagers->execute([$flight_id]);
$pax_list = $passagers->fetchAll();

// Calcul du retard
$diff = strtotime($vol['actual_arrival']) - strtotime($vol['scheduled_arrival']);
$total_delay_min = round($diff / 60);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vol <?= $vol['flight_number'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">

    <nav class="bg-slate-900 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <a href="rotations.php" class="bg-slate-700 px-4 py-2 rounded text-xs font-bold hover:bg-slate-600 transition tracking-tighter uppercase">← Retour Rotations</a>
            <span class="font-black italic text-xl">AIRCONTROL <span class="text-blue-500 underline decoration-2">V4</span></span>
        </div>
    </nav>

    <main class="container mx-auto mt-6 p-4">
        
        <div class="bg-white rounded-t-2xl shadow-xl overflow-hidden border-b border-gray-100">
            <div class="p-10 flex flex-col md:flex-row justify-between items-center gap-10">
                <div class="text-center">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Origine</p>
                    <div class="text-8xl font-black text-slate-800"><?= $vol['departure_airport'] ?></div>
                </div>

                <div class="flex-1 flex flex-col items-center">
                    <div class="text-4xl font-black text-blue-600 mb-2 italic tracking-tighter uppercase">
                        <?= $vol['flight_number'] ?>
                    </div>
                    <div class="w-full flex items-center gap-4">
                        <div class="flex-1 h-1 bg-gray-100 rounded-full"></div>
                        <div class="text-5xl">✈</div>
                        <div class="flex-1 h-1 bg-gray-100 rounded-full"></div>
                    </div>
                    <div class="mt-4 bg-blue-50 text-blue-700 px-8 py-2 rounded-full font-black text-2xl border-2 border-blue-100">
                        <?= $vol['avg_flight_duration_minutes'] ?> MIN
                    </div>
                </div>

                <div class="text-center">
                    <p class="text-xs font-bold text-blue-500 uppercase tracking-widest mb-2">Destination</p>
                    <div class="text-8xl font-black text-blue-600"><?= $vol['arrival_airport'] ?></div>
                </div>
            </div>
        </div>

        <div class="<?= $total_delay_min > 5 ? 'bg-orange-500' : 'bg-green-600' ?> p-5 text-white flex justify-between items-center px-10 shadow-lg">
            <div class="flex items-center gap-6">
                <span class="text-4xl">⏱</span>
                <div>
                    <p class="text-xs opacity-80 font-bold uppercase tracking-widest leading-none">Statut Arrivée</p>
                    <p class="text-2xl font-black"><?= $total_delay_min > 5 ? 'RETARD DE ' . $total_delay_min . ' MIN' : 'VOL À L\'HEURE (OK)' ?></p>
                </div>
            </div>
            <?php if ($vol['delay_minutes']): ?>
                <div class="text-right border-l border-white/30 pl-8">
                    <p class="text-xs opacity-80 font-bold uppercase">Cause Incident</p>
                    <p class="text-xl font-bold italic">CODE <?= $vol['delay_code'] ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-2 bg-slate-800 text-white shadow-md rounded-b-2xl mb-8">
            <div class="p-4 border-r border-slate-700 hover:bg-slate-700 transition cursor-pointer">
                <?php if ($prev_vol): ?>
                    <a href="vol_details.php?id=<?= $prev_vol['flight_id'] ?>" class="flex items-center gap-4">
                        <span class="text-2xl">⬅</span>
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Vol Précédent</p>
                            <p class="font-bold text-sm text-blue-300"><?= $prev_vol['flight_number'] ?> <span class="text-white font-normal">(Provenance <?= $prev_vol['arrival_airport'] ?>)</span></p>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
            <div class="p-4 text-right hover:bg-slate-700 transition cursor-pointer">
                <?php if ($next_vol): ?>
                    <a href="vol_details.php?id=<?= $next_vol['flight_id'] ?>" class="flex items-center justify-end gap-4">
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Vol Suivant</p>
                            <p class="font-bold text-sm text-blue-300"><?= $next_vol['flight_number'] ?> <span class="text-white font-normal">(Destination <?= $next_vol['arrival_airport'] ?>)</span></p>
                        </div>
                        <span class="text-2xl">➡</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Aéronef Assigné</p>
                    <div class="text-4xl font-black text-slate-800"><?= $vol['registration'] ?></div>
                    <div class="text-sm text-slate-500 font-bold mb-4 uppercase"><?= $vol['model_name'] ?></div>
                    <a href="aircraft_details.php?id=<?= $vol['aircraft_id'] ?>" class="block w-full py-2 bg-slate-100 text-slate-600 text-[10px] font-black rounded-lg hover:bg-slate-200 uppercase transition">Log Technique</a>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4 text-center">Équipage Technique & Cabine</p>
                    <div class="space-y-4">
                        <?php foreach ($crew as $c): ?>
                            <div class="flex items-center gap-3 border-b border-gray-50 pb-3 last:border-0">
                                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold italic">
                                    <?= substr($c['role'], 0, 1) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-slate-800 uppercase leading-none"><?= $c['last_name'] ?> <?= $c['first_name'] ?></p>
                                    <p class="text-[10px] font-bold text-blue-500 uppercase mt-1"><?= str_replace('_', ' ', $c['role']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-black text-xl uppercase tracking-tighter italic">Manifeste Passagers</h3>
                    <div class="flex gap-4 items-center">
                        <span class="text-[10px] font-bold text-gray-400 uppercase">Taux de remplissage :</span>
                        <span class="bg-blue-600 text-white px-4 py-1 rounded-full text-xs font-bold"><?= count($pax_list) ?> PAX</span>
                    </div>
                </div>
                <div class="max-h-[600px] overflow-y-auto">
                    <table class="w-full text-left">
                        <thead class="sticky top-0 bg-white text-[10px] font-bold text-gray-400 uppercase border-b border-gray-100 shadow-sm">
                            <tr>
                                <th class="p-5">Siège</th>
                                <th class="p-5">Passager</th>
                                <th class="p-5 text-center">Bagage</th>
                                <th class="p-5 text-right">Service Client</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($pax_list as $p): ?>
                            <tr class="hover:bg-blue-50/50 transition group">
                                <td class="p-5 font-mono font-black text-2xl text-blue-600"><?= $p['seat_number'] ?></td>
                                <td class="p-5">
                                    <span class="font-black text-slate-800 uppercase block"><?= htmlspecialchars($p['nom']) ?></span>
                                    <span class="text-sm text-slate-500 font-medium"><?= htmlspecialchars($p['prenom']) ?></span>
                                </td>
                                <td class="p-5 text-center">
                                    <?php if ($p['tag_number']): ?>
                                        <div class="text-[10px] font-black text-slate-600 border border-slate-200 rounded px-2 py-1 inline-block">
                                            🏷 <?= $p['tag_number'] ?> (<?= $p['weight_kg'] ?>kg)
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-300 italic text-[10px]">Cabine</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-5 text-right">
                                    <a href="boarding_pass.php?id=<?= $p['booking_id'] ?>" class="inline-block bg-slate-900 text-white px-4 py-2 rounded-lg text-[10px] font-black uppercase hover:bg-blue-600 transition">Carte Embarquement</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
