<?php
require_once 'config.php';

$selected_aircraft = $_GET['aircraft_id'] ?? null;

// Liste des avions pour le sélecteur
$aircrafts = $pdo->query("
    SELECT a.aircraft_id, a.registration, m.model_name
    FROM aircrafts a
    JOIN aircraft_models m ON a.model_id = m.model_id
    ORDER BY a.registration
")->fetchAll();

$vols = [];
if ($selected_aircraft) {
    // On récupère les vols de l'avion sélectionné par ordre chronologique
    $stmt = $pdo->prepare("
        SELECT f.flight_id, f.flight_number, r.departure_airport, r.arrival_airport,
               f.scheduled_departure, f.scheduled_arrival
        FROM flights f
        JOIN routes r ON f.route_id = r.route_id
        WHERE f.aircraft_id = ?
        ORDER BY f.scheduled_departure ASC
    ");
    $stmt->execute([$selected_aircraft]);
    $vols = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AirControl - Rotations Flotte</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 pb-10">

<?php include 'nav.php'; ?>

<nav class="bg-slate-900 text-white shadow-xl py-4">
    <div class="container mx-auto px-6 flex justify-between items-center">

        <div class="flex flex-col">
            <span class="text-mb font-bold text-slate-500 uppercase tracking-[0.2em]">Operational Monitoring</span>
        </div>

        <form method="GET" action="aircraft_details.php" class="flex items-end gap-6">
            <div class="flex flex-col">
                <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1 italic">
                    Choisir un appareil :
                </label>
                <select name="id" class="bg-slate-800 border border-slate-700 text-white text-xs font-black italic rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none uppercase cursor-pointer">
                    <option value="">-- Sélectionner l'immatriculation --</option>
                    <?php foreach ($aircrafts as $a): ?>
                        <option value="<?= $a['aircraft_id'] ?>" <?= $selected_aircraft == $a['aircraft_id'] ? 'selected' : '' ?>>
                            <?= $a['registration'] ?> (<?= $a['model_name'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded-lg font-black italic uppercase text-xs tracking-widest transition-all shadow-lg active:scale-95">
                Analyser
            </button>
        </form>

    </div>
</nav>

    <main class="container mx-auto p-4">

        <?php if ($selected_aircraft && $vols): ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="bg-gray-800 text-white uppercase text-[10px] tracking-widest">
                        <tr>
                            <th class="p-4">N° Vol</th>
                            <th class="p-4">Itinéraire</th>
                            <th class="p-4 text-center">Départ (UTC)</th>
                            <th class="p-4 text-center">Arrivée (UTC)</th>
                            <th class="p-4 text-center">Durée Vol</th>
                            <th class="p-4 text-center">État Logique</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $prev_arrival_airport = null; // Pour le test de téléportation

                        foreach ($vols as $vol):
                            // 1. CALCUL DE LA DURÉE DU VOL (Arrivée - Départ du même vol)
                            $timestamp_dep = strtotime($vol['scheduled_departure']);
                            $timestamp_arr = strtotime($vol['scheduled_arrival']);
                            $duree_vol = round(($timestamp_arr - $timestamp_dep) / 60);

                            // 2. TEST DE TÉLÉPORTATION (Départ actuel vs Arrivée précédente)
                            $is_teleport = false;
                            if ($prev_arrival_airport !== null && $prev_arrival_airport !== $vol['departure_airport']) {
                                $is_teleport = true;
                            }

                            // 3. ERREUR CHRONOLOGIQUE (Si l'arrivée est avant le départ)
                            $chrono_error = ($duree_vol <= 0);
                        ?>
                            <tr class="border-b hover:bg-blue-50/50 transition">
                                <td class="p-4">
                                    <a href="vol_details.php?id=<?= $vol['flight_id'] ?>" class="font-bold text-blue-600 hover:underline">
                                        <?= $vol['flight_number'] ?>
                                    </a>
                                </td>
                                <td class="p-4 font-bold">
                                    <span class="<?= $is_teleport ? 'text-red-600 underline decoration-wavy' : '' ?>">
                                        <?= $vol['departure_airport'] ?>
                                    </span>
                                    <span class="text-gray-300 font-normal mx-1">→</span>
                                    <span><?= $vol['arrival_airport'] ?></span>
                                </td>
                                <td class="p-4 text-center text-gray-500 font-mono">
                                    <?= date('d/m H:i', $timestamp_dep) ?>
                                </td>
                                <td class="p-4 text-center text-gray-500 font-mono">
                                    <?= date('d/m H:i', $timestamp_arr) ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="font-mono font-bold <?= $chrono_error ? 'text-red-600' : 'text-blue-600' ?>">
                                        ⏱ <?= $duree_vol ?> min
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($is_teleport): ?>
                                        <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-[10px] font-black uppercase">❌ Téléportation</span>
                                    <?php elseif ($chrono_error): ?>
                                        <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-[10px] font-black uppercase">⚠️ Temps Négatif</span>
                                    <?php else: ?>
                                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[10px] font-black uppercase">✅ OK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                            // On stocke l'arrivée pour la ligne suivante (test téléportation)
                            $prev_arrival_airport = $vol['arrival_airport'];
                        endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
