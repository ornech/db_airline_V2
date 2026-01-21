<?php
/**
 * ÉTAPE 1 : Importation de l'instance
 * Le fichier 'config.php' contient l'instanciation de l'objet $pdo.
 * (ex: $pdo = new PDO(...);)
 * En faisant le require_once ici, on rend l'objet $pdo disponible dans ce script.
 */
require_once 'config.php';

/**
 * ÉTAPE 2 : Utilisation de la méthode 'prepare'
 * On appelle la méthode 'prepare()' de l'objet $pdo.
 * Cette méthode analyse la requête SQL et retourne un NOUVEL objet :
 * une instance de la classe PDOStatement, qu'on stocke dans la variable $stmt.
 */
$stmt = $pdo->prepare("SELECT * FROM aircraft_models");

/**
 * ÉTAPE 3 : Exécution de la commande
 * On utilise la méthode 'execute()' de l'objet $stmt (le PDOStatement).
 * C'est à ce moment précis que la requête est envoyée à la base de données.
 */
$stmt->execute();

/**
 * ÉTAPE 4 : Récupération des données
 * On appelle la méthode 'fetchAll()' sur l'objet $stmt.
 * Elle extrait tous les résultats de la base de données et les transforme
 * en un tableau PHP (array) que l'on stocke dans $data.
 */
$data = $stmt->fetchall();
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
    <main class="container mx-auto p-6">
      <?php
      /**
       * DÉBOGAGE :
       * * 1. On ouvre la balise HTML <pre>. Le navigateur passe en mode "respect
       * total de la mise en forme".
       */
      echo '<pre>';

      /**
       * 2. On utilise var_dump() sur $data qui contient le résultat de notre requête.
       * PHP affiche la structure du tableau associatif $data et grâce à la balise
       *  <pre> situé juste avant, les caractères de retour à la ligne sont pris en compte.
       */
      var_dump($data);

      /**
       * 3. On referme la balise <pre>
       */
      echo '</pre>';
      ?>
    </main>

</body>
</html>
