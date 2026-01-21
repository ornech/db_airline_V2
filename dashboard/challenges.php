<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AirControl - Gestion et Audit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 text-gray-900">

<?php include 'nav.php'; ?>

<main class="container mx-auto mt-10 p-4 mb-20">

    <div class="grid grid-cols-1 gap-10">

        <section class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="bg-blue-600 p-4 flex items-center gap-3">
                <i class="fa-solid fa-clipboard-check text-white text-xl"></i>
                <h3 class="text-white font-bold uppercase tracking-wider">Demandes de la Direction Opérationnelle</h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col h-full bg-gray-50 rounded-lg border-l-4 border-blue-600 p-5">
                    <h4 class="font-bold text-blue-800 mb-2 underline decoration-blue-200">1. Analyse de la Ponctualité Globale</h4>
                    <p class="text-sm text-gray-700 mb-4 flex-grow">La direction souhaite connaître le pourcentage exact de vols arrivés à l'heure (sans aucun retard enregistré) sur l'année.</p>
                    <div class="space-y-2 mb-4">
                        <div class="bg-white p-2 rounded border border-gray-200 font-mono text-[10px] text-gray-500 italic">
                            Indices : Utiliser <code>flights</code> vs <code>flight_delays</code> (logique de non-existence).
                        </div>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-md mt-auto">
                        <span class="text-red-700 font-bold text-xs uppercase block mb-1">Objectif SQL :</span>
                        <code class="text-xs text-blue-900 font-semibold">Calculer le ratio (Vols sans retard / Total ARRIVED) * 100</code>
                    </div>
                </div>

                <div class="flex flex-col h-full bg-gray-50 rounded-lg border-l-4 border-blue-400 p-5">
                    <h4 class="font-bold text-blue-800 mb-2 underline decoration-blue-200">2. Impact des Retards en Cascade</h4>
                    <p class="text-sm text-gray-700 mb-4 flex-grow">Quel est le cumul total (en minutes) des retards dus uniquement à la répercussion d'un vol précédent (Code IATA 89) ?</p>
                    <div class="bg-blue-100 p-3 rounded-md mt-auto">
                        <span class="text-red-700 font-bold text-xs uppercase block mb-1">Objectif SQL :</span>
                        <code class="text-xs text-blue-900 font-semibold">SUM(delay_minutes) WHERE code_id = 89</code>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="bg-emerald-700 p-4 flex items-center gap-3">
                <i class="fa-solid fa-sack-dollar text-white text-xl"></i>
                <h3 class="text-white font-bold uppercase tracking-wider">Rapport de Rentabilité Commerciale</h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gray-50 p-5 rounded-lg border-t-4 border-emerald-600 shadow-sm flex flex-col">
                    <h4 class="font-bold text-gray-800 mb-3">Top 5 des Destinations</h4>
                    <p class="text-sm text-gray-600 mb-4 leading-relaxed italic">Identifier les 5 routes (Aéroport Départ -> Arrivée) ayant généré le plus de chiffre d'affaires total.</p>
                    <div class="flex gap-2 mt-auto">
                        <span class="px-2 py-1 bg-gray-200 rounded text-[10px] font-bold text-gray-500 uppercase">bookings</span>
                        <span class="px-2 py-1 bg-gray-200 rounded text-[10px] font-bold text-gray-500 uppercase">routes</span>
                        <span class="px-2 py-1 bg-emerald-100 text-emerald-800 rounded text-[10px] font-bold uppercase">SUM()</span>
                    </div>
                </div>

                <div class="bg-gray-50 p-5 rounded-lg border-t-4 border-emerald-600 shadow-sm flex flex-col">
                    <h4 class="font-bold text-gray-800 mb-3">Analyse par Appareil</h4>
                    <p class="text-sm text-gray-600 mb-4 leading-relaxed italic">Pour l'avion <strong>'F-HNET'</strong>, calculer son taux de remplissage moyen (Occupé vs Capacité) sur tous ses vols.</p>
                    <div class="flex gap-2 mt-auto">
                        <span class="px-2 py-1 bg-gray-200 rounded text-[10px] font-bold text-gray-500 uppercase">seats</span>
                        <span class="px-2 py-1 bg-gray-200 rounded text-[10px] font-bold text-gray-500 uppercase">bookings</span>
                        <span class="px-2 py-1 bg-emerald-100 text-emerald-800 rounded text-[10px] font-bold uppercase">COUNT()</span>
                    </div>
                </div>

                <div class="bg-gray-50 p-5 rounded-lg border-t-4 border-emerald-600 shadow-sm flex flex-col">
                    <h4 class="font-bold text-gray-800 mb-3">Panier Moyen</h4>
                    <p class="text-sm text-gray-600 mb-4 leading-relaxed italic">Quel est le prix moyen payé pour un billet en classe 'BUSINESS' comparé à la classe 'ECONOMY' ?</p>
                    <div class="flex gap-2 mt-auto">
                        <span class="px-2 py-1 bg-gray-200 rounded text-[10px] font-bold text-gray-500 uppercase">bookings</span>
                        <span class="px-2 py-1 bg-emerald-100 text-emerald-800 rounded text-[10px] font-bold uppercase">AVG() + GROUP BY</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="bg-red-700 p-4 text-white flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-screwdriver-wrench text-xl"></i>
                    <h3 class="font-bold uppercase tracking-wider">Audit de Maintenance Flotte</h3>
                </div>
                <span class="text-[10px] font-black bg-red-800 px-3 py-1 rounded-full uppercase animate-pulse">Alerte Prioritaire</span>
            </div>
            <div class="p-8">
                <div class="max-w-3xl mx-auto">
                    <p class="text-sm text-gray-600 mb-6 font-medium">Le service technique doit planifier les révisions. Il faut isoler les avions fragiles :</p>
                    <div class="bg-red-50 p-6 border-l-8 border-red-600 rounded-r-lg shadow-inner">
                        <p class="text-red-900 font-semibold leading-relaxed">
                            "Lister les immatriculations des appareils (registration) ayant cumulé <strong>plus de 10 incidents</strong> de type
                            <span class="underline decoration-red-300">Technique</span>
                            (Codes IATA 41 à 48) sur la période."
                        </p>
                        <div class="mt-4 flex gap-2">
                            <span class="text-xs bg-white border border-red-200 px-2 py-1 rounded text-red-600 font-mono">Indice: HAVING COUNT(*) > 10</span>
                            <span class="text-xs bg-white border border-red-200 px-2 py-1 rounded text-red-600 font-mono">Indice: WHERE code_id BETWEEN 41 AND 48</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="bg-purple-700 p-4 flex items-center gap-3">
                <i class="fa-solid fa-users-gear text-white text-xl"></i>
                <h3 class="text-white font-bold uppercase tracking-wider">Gestion des Ressources Humaines</h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-5 rounded-lg border-l-4 border-purple-600 relative">
                    <span class="absolute top-2 right-2 text-purple-200 text-4xl font-bold opacity-20">HR-01</span>
                    <h4 class="font-bold text-purple-900 mb-2">Calcul des Heures de Vol</h4>
                    <p class="text-sm text-gray-700 mb-4">
                        Pour payer les primes, nous devons identifier le pilote (Role: 'CAPTAIN') qui a accumulé le plus grand nombre de minutes de vol effectives (Actual Departure vs Actual Arrival).
                    </p>
                    <div class="bg-purple-100 p-3 rounded border border-purple-200 text-xs font-mono text-purple-900">
                        <span class="font-bold">Challenge :</span> Utiliser <code>TIMESTAMPDIFF(MINUTE, dep, arr)</code>, joindre <code>flight_crew</code>, <code>employees</code> et <code>flights</code>.
                    </div>
                </div>

                <div class="bg-gray-50 p-5 rounded-lg border-l-4 border-purple-400 relative">
                    <span class="absolute top-2 right-2 text-purple-200 text-4xl font-bold opacity-20">HR-02</span>
                    <h4 class="font-bold text-purple-900 mb-2">Équipages sur Vols à Problèmes</h4>
                    <p class="text-sm text-gray-700 mb-4">
                        Lister les noms et prénoms des membres d'équipage qui ont travaillé sur des vols ayant subi un retard "Météo" (Codes 71-77) de plus de 60 minutes.
                    </p>
                    <div class="bg-purple-100 p-3 rounded border border-purple-200 text-xs font-mono text-purple-900">
                        <span class="font-bold">Challenge :</span> Double jointure sur <code>flight_delays</code> et filtre sur les codes IATA.
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="bg-amber-600 p-4 flex items-center gap-3">
                <i class="fa-solid fa-medal text-white text-xl"></i>
                <h3 class="text-white font-bold uppercase tracking-wider">Qualité & Expérience Client</h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-amber-600 font-bold mb-2 text-sm uppercase">Top Contributeurs</div>
                    <p class="text-xs text-gray-600 mb-3">
                        Quels sont les 3 passagers (Nom/Prénom) qui ont dépensé le plus d'argent cumulé sur la totalité de leurs réservations ?
                    </p>
                    <code class="block bg-gray-100 p-2 rounded text-[10px] text-gray-500">ORDER BY SUM(price_paid) DESC LIMIT 3</code>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-amber-600 font-bold mb-2 text-sm uppercase">Impact Bagages</div>
                    <p class="text-xs text-gray-600 mb-3">
                        Quel est le poids total des bagages enregistrés sur les vols à destination de "JFK" (New York) ?
                    </p>
                    <code class="block bg-gray-100 p-2 rounded text-[10px] text-gray-500">JOIN routes -> flights -> bookings -> baggage</code>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-red-600 font-bold mb-2 text-sm uppercase">Taux de No-Show</div>
                    <p class="text-xs text-gray-600 mb-3">
                        Compter le nombre de passagers ayant raté leur vol (<code>is_noshow = 1</code>) et grouper ce chiffre par classe de siège.
                    </p>
                    <code class="block bg-gray-100 p-2 rounded text-[10px] text-gray-500">WHERE is_noshow = 1 GROUP BY class</code>
                </div>
            </div>
        </section>

    </div>
</main>

</body>
</html>
