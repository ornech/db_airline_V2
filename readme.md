1. Requêtes simples (Niveau : Échauffement)

Cibler l'extraction de données de base.

    Inventaire : Affichez la liste des immatriculations et le nom du modèle (model_name) de chaque appareil.

    Infrastructure : Comptez le nombre total d'aéroports enregistrés dans la table airports.

    Capacité : Pour l'avion dont l'immatriculation est 'F-GRHY', affichez le nombre de sièges disponibles (Somme de la capacité).

    Record : Identifiez le numéro du vol (flight_number) ayant eu la durée la plus longue (différence entre actual_arrival et actual_departure).

2. Jointures & Agrégations (Niveau : Intermédiaire)

Manipuler les relations entre les tables.

    Manifeste : Pour le vol 'AF123', affichez le numéro de vol, le nom, le prénom et la date de naissance des passagers (Jointure flights, bookings et passengers).

    Logistique : Affichez l'immatriculation de l'avion utilisé et la ville de destination pour le vol 'EZ456'.

    Cockpit : Affichez le nom du CDB (Commandant de Bord) et le numéro de vol pour le vol du 20 janvier 2026.

    Placement : Affichez le numéro de siège (seat_number) et le nom du passager pour le vol 'LH789'.

    Analyse de remplissage : Identifiez les sièges qui ne figurent pas dans la table bookings pour un vol donné (Indice : utilisez un LEFT JOIN avec une condition WHERE is NULL).

    Inactivité : Listez les passagers enregistrés en base mais n'ayant aucune réservation associée.

3. Vues & Analyse métier (Niveau : Avancé)

Simplifier l'accès aux données pour le Dashboard.

    Logbook Avions : Créez une vue v_logbook_aircraft qui affiche l'immatriculation, le modèle, l'aéroport de départ, l'heure de décollage réelle, l'aéroport d'arrivée, l'heure d'arrivée réelle et la durée calculée en minutes.

    Logbook Personnel : Créez une vue v_crew_hours qui affiche le nom, le prénom, la fonction, la date du vol, le numéro de vol et la durée du vol pour chaque membre d'équipage.

4. Sous-requêtes & Statistiques (Niveau : Expert)

Extraire de l'intelligence métier.

    Top Personnel : Affichez le ou les pilotes ayant effectué le plus de vols en décembre 2023.

    Remplissage Rome : Calculez le nombre moyen de passagers pour tous les vols à destination de 'FCO' (Rome).

    Sur-moyenne : Affichez les vols dont le nombre de passagers est supérieur à la moyenne globale des vols de la compagnie.

    Marketing : Déterminez l'âge moyen des passagers ayant voyagé en classe 'Business' à destination de Zurich.

5. Programmation procédurale (Triggers & Procédures)

Automatiser et sécuriser la base de données.

    Trigger Statut : Créez un trigger qui, après l'insertion d'un membre dans flight_crew, met à jour son statut dans la table employees à 'ENGAGÉ'.

    Trigger Sécurité : Ajoutez un trigger Before_Flight_Insert qui empêche la création d'un vol si aucun avion n'est assigné ou si l'heure de départ est dans le passé.

    Procédure Manifeste : Créez proc_get_manifest(p_flight_id) qui renvoie le Nom, Prénom, Siège et Date de naissance des passagers d'un vol spécifique.

    Procédure Planification : Créez proc_schedule_return(p_dep, p_arr, p_time) qui insère un vol aller ET génère automatiquement un vol retour 2 heures après l'arrivée prévue de l'aller.

6. Le "Boss Final" (Cas de synthèse BTS SIO)

    Sujet : "La direction s'inquiète des retards. Créez une vue qui calcule le retard moyen par delay_category (Météo, Technique, ATC) et intégrez cette donnée dans une nouvelle colonne du Dashboard."
