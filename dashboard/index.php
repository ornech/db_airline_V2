<?php 
require_once 'config.php'; 

// Requête optimisée pour les volumes et les dernières entrées
$query = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM flights) as total_flights,
        (SELECT COUNT(*) FROM bookings) as total_bookings,
        (SELECT COUNT(*) FROM baggage) as total_bags,
        (SELECT COUNT(*) FROM passengers) as total_passengers,
        (SELECT COUNT(*) FROM employees) as total_staff,
        (SELECT actual_arrival FROM flights WHERE status = 'ARRIVED' ORDER BY actual_arrival DESC LIMIT 1) as last_update
    FROM DUAL
");
$data = $query->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AirControl - Monitoring BDD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hover-card { transition: all 0.2s ease-out; }
        .hover-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        
        /* Personnalisation de la barre de défilement du modal pour un look pro */
        .custom-scrollbar::-webkit-scrollbar { width: 10px; height: 10px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #1e293b; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; border-radius: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>
<body class="bg-gray-50 text-slate-900">

<?php include 'nav.php'; ?>

<main class="container mx-auto mt-8 p-6">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Audit Database : <span class="text-blue-600">db_airline_V4</span></h1>
            <p class="text-slate-500 text-sm">Dernière injection de données : <span class="font-mono font-bold text-slate-700"><?= $data['last_update'] ?? 'Aucune donnée' ?></span></p>
        </div>
        <div class="flex gap-2">
            <span class="px-3 py-1 bg-slate-200 text-slate-600 rounded text-xs font-bold uppercase">MySQL 8.0</span>
            <span class="px-3 py-1 bg-blue-100 text-blue-600 rounded text-xs font-bold uppercase">UTF8MB4</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-10">
        <?php
        $cards = [
            ['Vols', $data['total_flights'], 'fa-plane', 'blue'],
            ['Bookings', $data['total_bookings'], 'fa-ticket-alt', 'indigo'],
            ['Bagages', $data['total_bags'], 'fa-suitcase', 'purple'],
            ['Clients', $data['total_passengers'], 'fa-user-friends', 'emerald'],
            ['Staff', $data['total_staff'], 'fa-id-badge', 'orange']
        ];
        foreach ($cards as $card): ?>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover-card">
                <div class="flex items-center gap-3 mb-2 text-<?= $card[3] ?>-600">
                    <i class="fas <?= $card[2] ?>"></i>
                    <span class="text-xs font-black uppercase tracking-widest text-slate-400"><?= $card[0] ?></span>
                </div>
                <p class="text-2xl font-black text-slate-800"><?= number_format($card[1], 0, ',', ' ') ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-project-diagram text-blue-500"></i>
                    Structure des Relations
                </h3>
                <button onclick="openModal()" class="text-xs bg-blue-50 text-blue-600 px-3 py-1 rounded-full font-bold hover:bg-blue-100 transition">
                    <i class="fas fa-expand mr-1"></i> Voir Schéma Complet
                </button>
            </div>

            <div onclick="openModal()" class="mt-6 cursor-pointer overflow-hidden rounded-lg border border-slate-200 group relative">
                <img src="db_airline_V4.png" alt="Schéma BDD" class="w-full h-80 object-cover group-hover:scale-105 transition duration-500 opacity-70">
                <div class="absolute inset-0 bg-slate-900/20 group-hover:bg-transparent transition"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="bg-white px-4 py-2 rounded shadow-xl text-xs font-black text-slate-700">AGRANDIR LE SCHÉMA</span>
                </div>
            </div>
        </div>

        <div class="bg-slate-800 text-white p-6 rounded-2xl shadow-xl">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-clipboard-check text-emerald-400"></i>
                Intégrité du Dataset
            </h3>
            <ul class="space-y-4">
                <li class="flex items-start gap-3 text-sm">
                    <i class="fas fa-check-circle text-emerald-500 mt-1"></i>
                    <div>
                        <span class="font-bold">Contraintes d'intégrité :</span>
                        <p class="text-slate-400 text-xs">Toutes les clés étrangères (FK) sont indexées pour optimiser les JOIN.</p>
                    </div>
                </li>
                <li class="flex items-start gap-3 text-sm">
                    <i class="fas fa-check-circle text-emerald-500 mt-1"></i>
                    <div>
                        <span class="font-bold">Historique Simulation :</span>
                        <p class="text-slate-400 text-xs">Couverture de 360 jours d'opérations aériennes générées.</p>
                    </div>
                </li>
                <li class="flex items-start gap-3 text-sm">
                    <i class="fas fa-check-circle text-emerald-500 mt-1"></i>
                    <div>
                        <span class="font-bold">Variables métier :</span>
                        <p class="text-slate-400 text-xs">Injection des données de No-Show et Retards IATA (Code 89) confirmée.</p>
                    </div>
                </li>
            </ul>
            
            <div class="mt-10 p-4 bg-blue-900/30 rounded-xl border border-blue-500/30">
                <p class="text-xs text-blue-200 leading-relaxed">
                    <i class="fas fa-info-circle mr-1 text-blue-400"></i> 
                    <strong>Note technique :</strong> Le dataset contient des anomalies volontaires (retards, no-shows, annulations) pour tester vos capacités d'analyse SQL.
                </p>
            </div>
        </div>
    </div>
</main>

<div id="db-schema-modal" class="hidden fixed inset-0 bg-black/95 z-50 flex flex-col backdrop-blur-md p-4" onclick="closeModal()">
    
    <div class="flex justify-between items-center text-white mb-4 px-4">
        <div class="flex items-center gap-3">
            <i class="fas fa-project-diagram text-blue-400"></i>
            <p class="text-sm font-black uppercase tracking-widest text-slate-300">Schéma relationnel db_airline_V4</p>
        </div>
        <button class="bg-white/10 hover:bg-red-500 hover:text-white px-4 py-2 rounded-lg text-sm font-black flex items-center gap-2 transition-all">
            FERMER <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="flex-grow overflow-auto custom-scrollbar bg-slate-900 rounded-xl shadow-inner border border-white/10" onclick="event.stopPropagation()">
        <img src="db_airline_V4.png" alt="Schéma Complet" class="max-w-none block mx-auto p-8" style="min-width: 1800px;">
    </div>
    
    <div class="text-center text-slate-500 text-[10px] mt-4 uppercase tracking-tighter">
        <i class="fas fa-mouse mr-1"></i> Utilisez la molette ou les barres latérales pour naviguer dans le dictionnaire de données
    </div>
</div>

<script>
// Logique d'ouverture du modal
function openModal() {
    const modal = document.getElementById('db-schema-modal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Bloque le défilement de la page principale
}

// Logique de fermeture du modal
function closeModal() {
    const modal = document.getElementById('db-schema-modal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto'; // Restaure le défilement
}

// Fermeture avec la touche Echap pour l'accessibilité
document.addEventListener('keydown', function(e) {
    if (e.key === "Escape") closeModal();
});
</script>

</body>
</html>