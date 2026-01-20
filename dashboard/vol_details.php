<?php
require_once 'config.php';

$flight_id = $_GET['id'] ?? null;
if (!$flight_id) { die("ID du vol manquant."); }

// 1. REQUÊTE SQL : Géo + IATA + Horaires
$stmt = $pdo->prepare("
    SELECT f.*, r.*, a.registration, m.model_name, a.aircraft_id,
           fd.delay_minutes, fd.code_id as delay_code,
           dc.label as delay_label, dc.category as delay_category,
           dep.city AS dep_city, dep.country AS dep_country,
           arr.city AS arr_city, arr.country AS arr_country
    FROM flights f
    JOIN routes r ON f.route_id = r.route_id
    JOIN aircrafts a ON f.aircraft_id = a.aircraft_id
    JOIN aircraft_models m ON a.model_id = m.model_id
    JOIN airports dep ON r.departure_airport = dep.iata_code
    JOIN airports arr ON r.arrival_airport = arr.iata_code
    LEFT JOIN flight_delays fd ON f.flight_id = fd.flight_id
    LEFT JOIN delay_codes dc ON fd.code_id = dc.code_id
    WHERE f.flight_id = ?
");
$stmt->execute([$flight_id]);
$vol = $stmt->fetch();

if (!$vol) { die("Vol introuvable."); }

// 2. Navigation Chronologique
$stmt_prev = $pdo->prepare("SELECT flight_id, flight_number FROM flights WHERE aircraft_id = ? AND scheduled_departure < ? ORDER BY scheduled_departure DESC LIMIT 1");
$stmt_prev->execute([$vol['aircraft_id'], $vol['scheduled_departure']]);
$prev_vol = $stmt_prev->fetch();

$stmt_next = $pdo->prepare("SELECT flight_id, flight_number FROM flights WHERE aircraft_id = ? AND scheduled_departure > ? ORDER BY scheduled_departure ASC LIMIT 1");
$stmt_next->execute([$vol['aircraft_id'], $vol['scheduled_departure']]);
$next_vol = $stmt_next->fetch();

// 3. Équipage
$stmt_crew = $pdo->prepare("SELECT first_name, last_name, role FROM flight_crew fc JOIN employees e ON fc.employee_id = e.employee_id WHERE fc.flight_id = ?");
$stmt_crew->execute([$flight_id]);
$crew = $stmt_crew->fetchAll();

// 4. Passagers
$pax_list = $pdo->prepare("SELECT b.*, p.prenom, p.nom, bg.tag_number FROM bookings b JOIN passengers p ON b.passenger_id = p.passenger_id LEFT JOIN baggage bg ON b.booking_id = bg.booking_id WHERE b.flight_id = ? ORDER BY b.seat_number");
$pax_list->execute([$flight_id]);
$pax_list = $pax_list->fetchAll();

// Calcul du retard
$diff = strtotime($vol['actual_arrival']) - strtotime($vol['scheduled_arrival']);
$total_delay_min = round($diff / 60);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Exploitation Vol <?= $vol['flight_number'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-slate-900 font-sans antialiased">

<?php include 'nav.php'; ?>


    <nav class="bg-slate-900 text-white shadow-xl">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-12">
                <div class="text-center">
                    <div class="text-4xl font-black italic tracking-tighter leading-none"><?= $vol['departure_airport'] ?></div>
                    <div class="text-[10px] font-bold text-blue-400 uppercase tracking-widest"><?= $vol['dep_city'] ?></div>
                </div>

                <div class="flex flex-col items-center">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-[2px] bg-slate-700"></div>
                        <span class="text-2xl transform rotate-90 text-blue-500">✈</span>
                        <div class="w-16 h-[2px] bg-slate-700"></div>
                    </div>
                    <div class="text-blue-500 font-black italic text-sm mt-1 uppercase"><?= $vol['flight_number'] ?></div>
                </div>

                <div class="text-center">
                    <div class="text-4xl font-black italic tracking-tighter text-blue-500 leading-none"><?= $vol['arrival_airport'] ?></div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?= $vol['arr_city'] ?></div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <?php if($prev_vol): ?>
                    <a href="vol_details.php?id=<?= $prev_vol['flight_id'] ?>" class="bg-slate-800 px-3 py-2 rounded text-[10px] font-black hover:bg-blue-600 transition italic uppercase">PREV</a>
                <?php endif; ?>
                <?php if($next_vol): ?>
                    <a href="vol_details.php?id=<?= $next_vol['flight_id'] ?>" class="bg-slate-800 px-3 py-2 rounded text-[10px] font-black hover:bg-blue-600 transition italic uppercase">NEXT</a>
                <?php endif; ?>
        </div>
    </nav>

    <main class="container mx-auto p-6 grid grid-cols-1 lg:grid-cols-4 gap-6">

        <div class="space-y-6">

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <div class="mb-4">
                    <div class="text-4xl font-black italic text-slate-800 leading-none tracking-tighter uppercase"><?= $vol['registration'] ?></div>
                    <div class="text-xs font-bold text-blue-600 uppercase mt-1"><?= $vol['model_name'] ?></div>
                </div>

                <div class="mb-6 py-2 border-y border-gray-50">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Date de service</p>
                    <p class="text-lg font-black italic uppercase text-slate-700"><?= date('d F Y', strtotime($vol['scheduled_departure'])) ?></p>
                </div>

                <div class="mb-4">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Planning (STD / STA)</p>
                    <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded-lg">
                        <span class="text-xl font-black italic text-slate-600"><?= date('H:i', strtotime($vol['scheduled_departure'])) ?></span>
                        <span class="text-gray-300">➔</span>
                        <span class="text-xl font-black italic text-slate-600"><?= date('H:i', strtotime($vol['scheduled_arrival'])) ?></span>
                    </div>
                </div>

                <div class="mb-6">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Effectif (ATD / ATA)</p>
                    <div class="flex justify-between items-center bg-blue-50 px-3 py-2 rounded-lg border border-blue-100">
                        <span class="text-xl font-black italic text-blue-700"><?= $vol['actual_departure'] ? date('H:i', strtotime($vol['actual_departure'])) : '--:--' ?></span>
                        <span class="text-blue-200">➔</span>
                        <span class="text-xl font-black italic text-blue-700"><?= $vol['actual_arrival'] ? date('H:i', strtotime($vol['actual_arrival'])) : '--:--' ?></span>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <?php if ($total_delay_min > 5): ?>
                        <div class="flex items-start gap-3">
                            <div class="bg-orange-500 text-white font-black px-2 py-1 rounded text-sm italic">Code <?= $vol['delay_code'] ?></div>
                            <div>
                                <p class="text-[11px] font-black uppercase italic leading-tight text-slate-800"><?= $vol['delay_label'] ?></p>
                                <p class="text-[10px] font-bold text-orange-600 mt-1 uppercase tracking-tighter">+ <?= $total_delay_min ?> MINUTES</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-2 text-green-600">
                            <span class="text-lg">✓</span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-green-700 font-bold italic">Aucun retard IATA</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Équipage</p>
                <div class="space-y-4">
                    <?php foreach ($crew as $c): ?>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center text-xs font-black italic text-slate-400 border border-gray-100">
                                <?= substr($c['role'], 0, 1) ?>
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-800 uppercase leading-none"><?= $c['first_name'] ?> <?= $c['last_name'] ?></p>
                                <p class="text-[10px] font-bold text-blue-500 uppercase mt-1"><?= str_replace('_', ' ', $c['role']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-8 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-black italic text-xl uppercase tracking-tighter">Manifeste Passagers</h3>
                <span class="bg-slate-900 text-white px-4 py-1 rounded-full text-[10px] font-black uppercase"><?= count($pax_list) ?> PAX</span>
            </div>

            <div class="overflow-y-auto max-h-[calc(100vh-200px)]">
                <table class="w-full text-left">
                    <thead class="bg-white sticky top-0 z-10 border-b border-gray-100 shadow-sm">
                        <tr class="text-[11px] font-black text-slate-400 uppercase">
                            <th class="px-8 py-4">Siège</th>
                            <th class="px-8 py-4">Identité Passager</th>
                            <th class="px-8 py-4">Bagage</th>
                            <th class="px-8 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($pax_list as $p): ?>
                        <tr class="hover:bg-blue-50/50 transition-colors">
                            <td class="px-8 py-4 font-mono font-black text-3xl text-blue-600"><?= $p['seat_number'] ?></td>
                            <td class="px-8 py-4">
                                <div class="text-base font-black text-slate-800 uppercase leading-none mb-1"><?= $p['nom'] ?> <?= $p['prenom'] ?></div>
                                <div class="text-[11px] font-bold text-slate-400 font-mono">PNR : <?= $p['pnr'] ?></div>
                            </td>
                            <td class="px-8 py-4 text-center">
                                <?php if ($p['tag_number']): ?>
                                    <span class="text-[11px] font-black text-slate-600 uppercase border border-slate-200 px-3 py-1 rounded-md bg-white italic">🏷 <?= $p['tag_number'] ?></span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-300 italic">Cabine</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <a href="passager_details.php?id=<?= $p['booking_id'] ?>" class="border-2 border-slate-900 px-4 py-2 rounded text-[10px] font-black uppercase hover:bg-slate-900 hover:text-white transition italic inline-block">Détails</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>
