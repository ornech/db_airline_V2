<?php
require_once 'config.php';

// On récupère l'ID du booking (billet)
$booking_id = $_GET['id'] ?? null;

if (!$booking_id) {
    die("ID de réservation manquant.");
}

// Requête ultra-complète pour rassembler toutes les infos du billet
$stmt = $pdo->prepare("
    SELECT
        b.booking_id, b.seat_number, b.class, b.booking_date,
        p.nom, p.prenom, p.passport_number,
        f.flight_number, f.scheduled_departure,
        r.departure_airport, r.arrival_airport
    FROM bookings b
    JOIN passengers p ON b.passenger_id = p.passenger_id
    JOIN flights f ON b.flight_id = f.flight_id
    JOIN routes r ON f.route_id = r.route_id
    WHERE b.booking_id = ?
");
$stmt->execute([$booking_id]);
$ticket = $stmt->fetch();

if (!$ticket) { die("Billet introuvable."); }

// Calcul de l'heure d'embarquement (45 min avant le départ)
$departure_ts = strtotime($ticket['scheduled_departure']);
$boarding_time = date('H:i', $departure_ts - (45 * 60));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Boarding Pass - <?= $ticket['nom'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; }
        }
        .barcode { font-family: 'Libre Barcode 39', cursive; font-size: 60px; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-200 p-4 md:p-10">
<?php include 'nav.php'; ?>
    <div class="no-print mb-6 text-center">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition">
            🖨️ Imprimer la carte d'embarquement
        </button>
        <p class="text-gray-500 text-xs mt-2">Format optimisé pour impression A4 Paysage</p>
    </div>

    <div class="max-w-4xl mx-auto bg-white border-2 border-dashed border-gray-400 rounded-3xl overflow-hidden shadow-2xl flex flex-col md:flex-row min-h-[350px]">

        <div class="flex-1 p-8 border-r-2 border-dashed border-gray-200 relative">
            <div class="absolute top-0 right-0 m-4 opacity-10 text-6xl font-black italic text-gray-400">BOARDING PASS</div>

            <div class="flex justify-between items-start mb-8">
                <div>
                    <h1 class="text-3xl font-black italic text-blue-900 tracking-tighter">AIRCONTROL <span class="text-blue-500">V4</span></h1>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Official Boarding Document</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-gray-400 uppercase">Classe</p>
                    <p class="text-2xl font-black text-blue-600"><?= $ticket['class'] == 'BUS' ? 'BUSINESS' : 'ECONOMY' ?></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Passager</p>
                    <p class="text-xl font-black uppercase"><?= htmlspecialchars($ticket['nom']) ?> <?= htmlspecialchars($ticket['prenom']) ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Passport</p>
                    <p class="text-xl font-bold italic"><?= $ticket['passport_number'] ?></p>
                </div>
            </div>

            <div class="flex justify-between items-center bg-gray-50 p-6 rounded-2xl border border-gray-100">
                <div class="text-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase">From</p>
                    <p class="text-4xl font-black text-slate-800"><?= $ticket['departure_airport'] ?></p>
                </div>
                <div class="flex-1 px-4 flex flex-col items-center">
                    <span class="text-blue-500 text-xl">✈</span>
                    <div class="w-full h-px bg-gray-300"></div>
                    <span class="text-[10px] font-bold text-blue-500 mt-1"><?= $ticket['flight_number'] ?></span>
                </div>
                <div class="text-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase">To</p>
                    <p class="text-4xl font-black text-blue-600"><?= $ticket['arrival_airport'] ?></p>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-4 mt-8">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Date</p>
                    <p class="font-bold text-sm"><?= date('d M Y', $departure_ts) ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase italic text-blue-500">Boarding</p>
                    <p class="font-black text-xl text-blue-600"><?= $boarding_time ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Gate</p>
                    <p class="font-black text-xl">B22</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Seat</p>
                    <p class="font-black text-2xl text-blue-600"><?= $ticket['seat_number'] ?></p>
                </div>
            </div>
        </div>

        <div class="w-full md:w-64 bg-slate-900 text-white p-8 flex flex-col justify-between items-center text-center">
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase mb-4 tracking-widest">Passenger Stub</p>
                <div class="text-xs opacity-60 mb-1 italic">Flight</div>
                <div class="text-2xl font-black mb-4"><?= $ticket['flight_number'] ?></div>

                <div class="text-xs opacity-60 mb-1 italic">Seat</div>
                <div class="text-4xl font-black text-blue-400 mb-6"><?= $ticket['seat_number'] ?></div>
            </div>

            <div class="w-full">
                <div class="barcode leading-none opacity-80"><?= $ticket['booking_id'] ?><?= $ticket['flight_number'] ?></div>
                <p class="text-[8px] font-mono tracking-widest mt-2 uppercase"><?= htmlspecialchars($ticket['nom']) ?> / <?= $ticket['booking_id'] ?></p>
            </div>
        </div>
    </div>

    <div class="mt-8 text-center text-gray-400 text-[10px] uppercase tracking-widest">
        &copy; <?= date('Y') ?> AirControl V4 - Systèmes Aéroportuaires - Document de simulation
    </div>

</body>
</html>
