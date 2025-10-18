<!DOCTYPE html>
<html lang="fr">
  <head>
    <title><?php echo $titreP ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="codesite/form.css">
  </head>
  <body>
    <header>
      <h1>Les meilleurs exercices de musculation</h1>
      <nav>
        <ul>
          <li><a href="index.php">Accueil</a></li>
          <li><a href="index.php?action=liste">Liste des exercices</a></li>
          <li><a href="index.php?action=ajout">Ajouter un exercice</a></li>
          <li><a href="index.php?action=propos">A propos de moi</a></li>
        </ul>
      </nav>
    </header>
    <main>
      <div class="content">
        <?php echo $zonePrincipale ?>
      </div>
      
    </main>

  </body> 
</html>

