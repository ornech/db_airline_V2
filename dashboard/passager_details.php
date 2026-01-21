<?php
require_once 'config.php';

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) { die("ID Réservation manquant."); }

// Requête enrichie avec Géo complète, Classe, et Infos Personnelles
$stmt = $pdo->prepare("
    SELECT b.*, p.*,
           f.flight_number, f.scheduled_departure, f.scheduled_arrival, f.status as flight_status,
           a.registration,
           r.departure_airport, r.arrival_airport,
           dep.city AS dep_city, dep.country AS dep_country,
           arr.city AS arr_city, arr.country AS arr_country,
           bg.tag_number, bg.weight_kg, bg.is_priority, bg.status as bag_status
    FROM bookings b
    JOIN passengers p ON b.passenger_id = p.passenger_id
    JOIN flights f ON b.flight_id = f.flight_id
    JOIN routes r ON f.route_id = r.route_id
    JOIN aircrafts a ON f.aircraft_id = a.aircraft_id
    JOIN airports dep ON r.departure_airport = dep.iata_code
    JOIN airports arr ON r.arrival_airport = arr.iata_code
    LEFT JOIN baggage bg ON b.booking_id = bg.booking_id
    WHERE b.booking_id = ?
");
$stmt->execute([$booking_id]);
$data = $stmt->fetch();

if (!$data) { die("Réservation introuvable."); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PAX : <?= strtoupper($data['nom']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-slate-900 font-sans antialiased">


<?php include 'nav.php'; ?>


    <nav class="bg-slate-900 text-white p-4 shadow-xl">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="vol_details.php?id=<?= $data['flight_id'] ?>" class="text-xs bg-white/10 px-3 py-1 rounded hover:bg-white/20 transition uppercase font-black italic">← Retour Vol</a>
                <h1 class="text-xl font-black italic uppercase tracking-tighter">Fiche Passager</h1>
            </div>
            <div class="text-right">
                <span class="block text-blue-500 font-black italic leading-none uppercase">Réservation : <?= $data['pnr'] ?></span>
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">AirControl V4 System</span>
            </div>
        </div>
    </nav>

    <main class="container mx-auto p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 italic">Profil Passager</p>
                <div class="mb-6">
                    <p class="text-4xl font-black italic text-slate-800 uppercase leading-none"><?= $data['nom'] ?></p>
                    <p class="text-2xl font-black italic text-blue-600 uppercase"><?= $data['prenom'] ?></p>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase italic"><?= $data['gender'] ?? 'Genre non spécifié' ?> — <?= $data['nationality'] ?></p>
                </div>

                <div class="space-y-4 pt-4 border-t border-gray-100">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase italic">Adresse Résidence</p>
                        <p class="font-black italic text-slate-700 uppercase text-sm leading-tight"><?= $data['address'] ?? 'Aucune adresse enregistrée' ?></p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase italic">Passeport</p>
                            <p class="font-black italic text-slate-700 uppercase"><?= $data['passport_number'] ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase italic">Téléphone</p>
                            <p class="font-black italic text-slate-700"><?= $data['tel'] ?></p>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase italic">E-mail</p>
                        <p class="font-black italic text-slate-700 underline"><?= $data['email'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <div class="flex justify-between items-start mb-4">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic">Itinéraire du <?= date('d/m/Y', strtotime($data['scheduled_departure'])) ?></p>
                    <span class="bg-blue-600 text-white text-[10px] font-black px-3 py-1 rounded italic uppercase"><?= $data['class'] ?? 'ECONOMY' ?></span>
                </div>

                <div class="flex items-center justify-between mb-8 bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <div class="text-center">
                        <p class="text-3xl font-black italic text-slate-800 leading-none"><?= $data['departure_airport'] ?></p>
                        <p class="text-[9px] font-bold text-slate-400 uppercase italic leading-none mt-1"><?= $data['dep_city'] ?></p>
                    </div>
                    <div class="flex flex-col items-center">
                        <span class="text-[9px] font-black italic text-blue-500"><?= $data['flight_number'] ?></span>
                        <span class="text-xl text-blue-500 italic font-black">✈</span>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-black italic text-slate-800 leading-none"><?= $data['arrival_airport'] ?></p>
                        <p class="text-[9px] font-bold text-blue-500 uppercase italic leading-none mt-1"><?= $data['arr_city'] ?></p>
                    </div>
                </div>

                <div class="mb-6">
                    <p class="text-[10px] font-bold text-gray-400 uppercase italic">Destination Finale</p>
                    <p class="font-black italic text-slate-700 uppercase text-lg leading-tight"><?= $data['arr_city'] ?>, <?= $data['arr_country'] ?></p>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                    <div class="text-center">
                        <p class="text-[9px] font-bold text-gray-400 uppercase">Siège</p>
                        <p class="text-3xl font-black italic text-blue-600"><?= $data['seat_number'] ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-[9px] font-bold text-gray-400 uppercase">Heure Décollage</p>
                        <p class="text-2xl font-black italic text-slate-700"><?= date('H:i', strtotime($data['scheduled_departure'])) ?></p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 italic">Statut Bagages</p>

                    <?php if ($data['tag_number']): ?>
                        <div class="bg-white border border-slate-100 p-4 rounded-xl shadow-inner mb-4">
                            <div class="flex justify-between items-start mb-2">
                                <p class="text-2xl font-black italic text-slate-800 tracking-tighter"><?= $data['tag_number'] ?></p>
                                <?php if($data['is_priority']): ?>
                                    <span class="bg-orange-500 text-white text-[9px] font-black px-2 py-1 rounded italic uppercase">PRIORITY</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex justify-between items-center text-[10px] font-bold uppercase">
                                <span class="text-slate-400 italic">Charge: <span class="text-slate-800"><?= $data['weight_kg'] ?> KG</span></span>
                                <span class="text-blue-600 italic">Statut: <?= $data['bag_status'] ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 border-l-4 border-gray-300 p-4 rounded-r-lg text-center">
                            <p class="text-xs italic text-gray-400 font-bold uppercase tracking-tighter italic">Pas de bagage enregistré</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col gap-3">
                    <a href="boarding_pass.php?id=<?= $data['booking_id'] ?>" class="bg-slate-900 text-white text-center py-5 rounded-2xl font-black italic uppercase tracking-widest hover:bg-blue-600 transition shadow-xl border-b-4 border-slate-700">
                        ⎙ Générer Carte d'Embarquement
                    </a>
                    <p class="text-[9px] text-center font-bold text-slate-400 uppercase italic">Accès restreint au personnel au sol uniquement</p>
                </div>
            </div>

        </div>
    </main>

</body>
</html>
