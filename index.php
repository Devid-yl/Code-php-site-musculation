<?php
require_once("codesite/db.php");
$action = key_exists('action', $_GET) ? trim($_GET['action']) : null;
$sauvegarde = key_exists('sauvegarde', $_GET)? trim($_GET['sauvegarde']): null;

switch ($action){
	case "sauvegarde":
		$connection =connecter();
		$id = key_exists('id',$_POST)? $_POST['id']: null;
		$type = key_exists('type',$_POST)? $_POST['type']: null;
		$sql = key_exists('sql',$_POST)? $_POST['sql']: null;
		$newfile = key_exists('newfile',$_POST)? $_POST['newfile']: null;//chemin de l'image définitive
		$filetmp = key_exists('filetmp',$_POST)? $_POST['filetmp']: null;//chemin de l'image temporaire
		if($type =='confirmupdate'){
			if(file_exists("imageexo/".hex2bin($id).".gif")){//On supprime l'ancienne image si il y en avait une
               unlink("imageexo/".hex2bin($id).".gif");
            }
			if($newfile!=null){//si l'utilisateur a ajouté une image (il peut ne pas en mettre)
			   rename($filetmp, $newfile);//on déplace l'image qui se trouve dans le répertoire d'image temporaire dans le répertoire définitif (imageexo)
			}
			$corps="<h1>Mise à jour de l'exercice ".$id."</h1>" ;
			$req=$connection->prepare($sql);
		    $req->execute();
		    $titreP = "Exercice modifié";
		}else{
			if(file_exists("imageexo/".hex2bin($id).".gif")){//On supprime l'image de l'exercice si il y en avait une
               unlink("imageexo/".hex2bin($id).".gif");
            }
			$corps="<h1>Suppression de l'exercice ".$id."</h1>" ;
			$req=$connection->prepare($sql);
		    $req->execute();
		    $titreP = "Exercice supprimé";	
		}
		$zonePrincipale=$corps ;		
		$connection = null;
		break;
	case "delete": 
        $id=$_GET["id"];//la variable id est le nom de l'exercice converti en chaîne de caractères hexadécimaux
        $titreP = "Supprimer ".hex2bin($id)."?";
		$sql = "DELETE FROM exo where id like '$id'";
		$corps="<form action=\"index.php?action=sauvegarde\" method=\"post\">
        <input type=\"hidden\" name=\"type\" value=\"confirmdelete\"/>
        <input type=\"hidden\" name=\"id\" value=\"$id\"/>
        <input type=\"hidden\" name=\"sql\" value=\"$sql\"/>
        Etes vous sûr de vouloir supprimer cette exercice ?
         <p>
        <input type=\"submit\" value=\"Enregistrer\" class=\"btn btn-danger\">
        <a href=\"index.php\" class=\"btn btn-secondary\">Annuler</a>
       </p>
       </form> ";
      $zonePrincipale=$corps ;
      break;
	case "update": 
        $id=$_GET["id"]; 
        $titreP = "Modification de ".hex2bin($id);
		$cible='update';
		$connection =connecter();
		$requete ="SELECT * FROM exo WHERE id = '$id'";
		$query = $connection->query($requete);
		$query->setFetchMode(PDO::FETCH_OBJ);
		while($enregistrement = $query->fetch()){//cette boucle permet de préremplir les champs de texte avec les données de l'exercice qu'on veut modifier
            if($id==$enregistrement->id){
				$titre = $enregistrement->titre;
				$auteur = $enregistrement->auteur;
				$texte = $enregistrement->texte;
			}
        }
		$corps = "";
		if(!isset($_POST["auteur"])	&& !isset($_POST["titre"]) && !isset($_POST["texte"])){
			include("codesite/formulairePersonne.html");
		}else{
			$auteur = key_exists('auteur', $_POST)? trim($_POST['auteur']): null;
			$titre = key_exists('titre', $_POST)? trim($_POST['titre']): null;
			$texte = key_exists('texte', $_POST)? trim($_POST['texte']): null;
			if ($auteur=="") $erreur["auteur"] ="Saisissez votre nom!"; 
			if ($titre=="") $erreur["titre"] ="Saisissez le nom de l'exercice!"; 
			if ($texte=="") $erreur["texte"] ="Saisissez la description de l'exercice!";
            $requete = "SELECT * FROM exo WHERE titre=\"".$titre."\"";
            $query = $connection->query($requete);
            $query->setFetchMode(PDO::FETCH_OBJ);
            while($enregistrement = $query->fetch()){
            if($enregistrement->titre==$titre&&$enregistrement->titre!=hex2bin($id)){
				/*cette erreur sera active que si le nom d'exo saisi est déjà présent 
				 * dans la base de données qu'il est différent du nom de l'exercice 
				 * u'on est en train de modifier*/
				$erreur["titre"] ="Ce nom est déjà pris saisissez-en un autre!";
			}
            }
            
            $newfile = null;
			if(isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE){//on vérifie si un fichier a été transmis
				$imageFileType = strtolower(pathinfo(basename($_FILES["image"]["name"]),PATHINFO_EXTENSION));//on récupère le type du fichier
                if($imageFileType!="gif"){//on accepte que les gif juste pour des raisons estéthique
                   $erreur["image"] ="Le fichier doit être une image GIF exclusivement!";
                }else{
					if($_FILES["image"]["size"] > 5000000){
                       $erreur["image"] = "Le fichier est trop volumineux!";
                    }else{
						$file = "imagetemporaire/".$titre.".".$imageFileType;
						$newfile = "imageexo/".$titre.".".$imageFileType;
					}
				}
            } 
            	
			$compteur_erreur=count($erreur);
			foreach ($erreur as $cle=>$valeur){
				if($valeur==null) $compteur_erreur=$compteur_erreur-1;
			}

			
			if($compteur_erreur == 0){
			if($file!=null){//si un fichier a été transmis
			   /*grâce à move_uploaded_file() on importe le fichier dans le répertoire 
			    * imagetemporaire parce que l'utilisateur n'a pas encore comfirmer 
			    * s'il était sûr de vouloir modifier*/
               if(!move_uploaded_file($_FILES["image"]["tmp_name"], $file)){
				  $erreur["image"] = "Erreur, l'importation du fichier a échoué!";
                  include("codesite/formulairePersonne.html");
               }
            }
			$id2 = bin2hex($titre);//c'est le nouveau nom de l'exercice
			$date_publication = date('Y-m-d');
			$texte = str_replace("'", "", $texte);//Pour ne pas avoir d'erreur dans la requête
			$texte = str_replace('"', '', $texte);
			$titre = str_replace("'", "", $titre);//Pour ne pas avoir d'erreur dans la requête
			$titre = str_replace('"', '', $titre);
			$auteur = str_replace("'", "", $auteur);//Pour ne pas avoir d'erreur dans la requête
			$auteur = str_replace('"', '', $auteur);
			$sql = "update exo set id='$id2', titre='$titre', auteur='$auteur', date='$date_publication', texte='$texte' where id='$id'";
			$corps="<form action=\"index.php?action=sauvegarde\" method=\"post\">
            <input type=\"hidden\" name=\"type\" value=\"confirmupdate\"/>
            <input type=\"hidden\" name=\"id\" value=\"$id\"/>
            <input type=\"hidden\" name=\"sql\" value=\"$sql\"/>
            <input type=\"hidden\" name=\"newfile\" value=\"$newfile\"/>
            <input type=\"hidden\" name=\"filetmp\" value=\"$file\"/>
            Etes vous sûr de vouloir mettre à jour cet exercice ?
            <p>
            <input type=\"submit\" value=\"Enregistrer\" class=\"btn btn-danger\">
            <a href=\"index.php\" class=\"btn btn-secondary\">Annuler</a>
            </p>
            </form>";
		   }else{
			include("codesite/formulairePersonne.html");
			}
		}
		$zonePrincipale=$corps ;
		$query = null;
		$connection = null;
        break;
	case "propos":
        $titreP = "à propos de moi";
        $zonePrincipale = "<p>Numéro étudiant: 22200123</p>";
        $zonePrincipale .= "<p>Nom: Yala</p>";
        $zonePrincipale .= "<p>Prénom: David</p>";
        $zonePrincipale .= "<p>Goupe de TD: 4B</p>";
        $zonePrincipale .= "<p>Compléments effectués: possibilité de modifier l'ordre d'affichage de la liste (tri par date, nom des exos...), pagination de la liste (ne montrer que N objets par page), possibilité d'illustrer un objet en uploadant une image.</p>";
        $zonePrincipale .= "<p>Rien d'autre à signaler</p>";
        break;
	case "ajout": 
	    $titreP = "ajouter un exercice";
	    $cible='ajout';
	    if(!isset($_POST["auteur"])	&& !isset($_POST["titre"]) && !isset($_POST["texte"])){
			include("codesite/formulairePersonne.html");
		}else{
			$auteur = key_exists('auteur', $_POST)? trim($_POST['auteur']): null;
			$titre = key_exists('titre', $_POST)? trim($_POST['titre']): null;
			$texte = key_exists('texte', $_POST)? trim($_POST['texte']): null;
			if ($auteur=="") $erreur["auteur"] ="Saisissez votre nom!"; 
			if ($titre=="") $erreur["titre"] ="Saisissez le nom de l'exercice!"; 
			if ($texte=="") $erreur["texte"] ="Saisissez la description de l'exercice!";  
			$connection = connecter();
            $requete = "SELECT * FROM exo WHERE titre=\"".$titre."\"";
            $query = $connection->query($requete);
            $query->setFetchMode(PDO::FETCH_OBJ);
            while($enregistrement = $query->fetch()){
            if($enregistrement->titre==$titre){
				$erreur["titre"] ="Ce nom est déjà pris saisissez-en un autre!";
			}
            }
            $query = null;
            $connection = null;
            if(isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE){
				$imageFileType = strtolower(pathinfo(basename($_FILES["image"]["name"]),PATHINFO_EXTENSION));
                if($imageFileType != "gif") {
                   $erreur["image"] ="Le fichier doit être une image GIF exclusivement!";
                }else{
					if($_FILES["image"]["size"] > 5000000){
                       $erreur["image"] = "Le fichier est trop volumineux!";
                    }else{
						$file = "imageexo/".$titre.".".$imageFileType;
					}
				}
            }
 
				
			$compteur_erreur=count($erreur);
			foreach ($erreur as $cle=>$valeur){
				if($valeur==null) $compteur_erreur=$compteur_erreur-1;
			}

			if($compteur_erreur == 0){
				if($file!=null){
                   if(!move_uploaded_file($_FILES["image"]["tmp_name"], $file)){
					   $erreur["image"] = "Erreur, l'importation du fichier a échoué!";
                       include("codesite/formulairePersonne.html");
                   }
                }
				$id = bin2hex($titre);
				$date_publication = date('Y-m-d');
				$texte = str_replace("'", "", $texte);//Pour ne pas avoir d'erreur dans la requête
			    $texte = str_replace('"', '', $texte);
			    $titre = str_replace("'", "", $titre);//Pour ne pas avoir d'erreur dans la requête
			    $titre = str_replace('"', '', $titre);
			    $auteur = str_replace("'", "", $auteur);//Pour ne pas avoir d'erreur dans la requête
			    $auteur = str_replace('"', '', $auteur);
			    $connection =connecter();
				$corps = "Connection etablie <br>";
				$corps .= "Il faut maintenant insérer les données du formulaire dans la base <br>";
				$rq = "INSERT INTO exo (id, titre, auteur, date, texte) VALUES (:id, :titre, :auteur, :date, :texte) ";
				$stmt = $connection->prepare($rq);
				$data = array(
				  ':id' => $id,
				  ':titre' => $titre,
				  ':auteur' => $auteur,
				  ':date' => $date_publication,
				  ':texte' => $texte,
				);
				$stmt->execute($data);
				$exo = new Exo($id,$titre,$auteur,$date_publication,$texte);
				$corps .= "Saisie de : ". $titre;
				
				$zonePrincipale=$corps ;
				$query = null;
				$connection = null;
			}else{
				include("codesite/formulairePersonne.html");
			}
		}
        break;
    case "liste": 
        $tri = key_exists('tri',$_POST)? $_POST['tri']: "date DESC";//la variable qui défini dans quel ordre la liste est affichée
        $page = key_exists('page',$_POST)? $_POST['page']: 1;//la variable qui indique à quelle page on se situe dans la liste
        $direction = key_exists('direction',$_POST)? $_POST['direction']: null;//la variable qui indique si l'utilisateur a cliqué sur pagesuivante ou sur pageprecedente
  
		$titreP = "Les meilleurs exercices";
        $corps = "<form method=\"post\" action=\"index.php?action=liste\">
        <label for=\"ordre\">Faites les exercices suivants :</label>
        <select id=\"ordre\" name=\"tri\">";
        foreach($donneeTriListe as $ordre => $ordreNom){
			if($ordre==$tri){
			   $corps .= "<option value=\"".$ordre."\" selected>".$ordreNom."</option>";
			}else{
			   $corps .= "<option value=\"".$ordre."\">".$ordreNom."</option>";
			}
		}
        $corps .= "</select><button type=\"submit\">Valider</button></form><ul>";
        
        
        $connection = connecter();
        $query = $connection->query("SELECT COUNT(*) FROM exo");
        if($direction=="precedent"&&$page!=1){
		   $page--;
		}else if($direction=="suivant"&&$page!=ceil($query->fetchColumn()/10)){
		/*ceil($query->fetchColumn()/10) c'est le maximum de page qu'on peut avoir 
		 * en fonction du nombre d'exercice si la limite d'affichage est 10*/
		   $page++;
		}
        $offset = ($page - 1) * 10;//c'est la variable qui permet de spécifier le nombre de lignes à sauter avant de commencer à retourner les résultats de la requête
        $requete = "SELECT * FROM exo ORDER BY ".$tri." LIMIT 10 OFFSET ".$offset;
        $query = $connection->query($requete);
        $query->setFetchMode(PDO::FETCH_OBJ);
        $tab_Exo = array();
        while($enregistrement = $query->fetch()){
            $exo = new Exo($enregistrement->id, $enregistrement->titre, $enregistrement->auteur, $enregistrement->date, $enregistrement->texte);
            $tab_Exo[] = $exo;
        }
        foreach ($tab_Exo as $exo){
            $corps .= $exo->afficherSimple();
        }
        $corps .= "</ul>";
        $corps .= "<form id=\"form-pagination\" method=\"post\" action=\"index.php?action=liste\">
        <input type=\"hidden\" name=\"tri\" value=\"".$tri."\">
        <input type=\"hidden\" name=\"page\" value=\"".$page."\">
        <button type=\"submit\" name=\"direction\" value=\"precedent\">Page précédente</button>
        <button type=\"submit\" name=\"direction\" value=\"suivant\">Page suivante</button>
        </form>";
        $zonePrincipale = $corps;
        $query = null;
        $connection = null;
        break;

    default:
        $filesliste = glob('imagetemporaire/*');
        foreach($filesliste as $files){//on vide le répertoire des images temporaire car elles ne servent plus à rien
            unlink($files);
        }
        $titreP = "Accueil";
        //j'ai mis le texte de l'ecran d'accueil dans un fichier parce qu'il est un peu long
        $zonePrincipale = file_get_contents('https://dev-yala221.users.info.unicaen.fr/dm-tw3-2023/elementsite/textaccueil.html');
        $connection = connecter();
        $requete = "SELECT * FROM exo";
        $query = $connection->query($requete);
        $query->setFetchMode(PDO::FETCH_OBJ);

        while($enregistrement = $query->fetch()){
            if($action==$enregistrement->id){//Dans cette condition on affiche complètement l'exercice qui a été selectionné si aucun n'a été sélectionné c'est lécran d'accueil qui est affiché
				$titreP = $enregistrement->titre;
				$exo = new Exo($enregistrement->id, $enregistrement->titre, $enregistrement->auteur, $enregistrement->date, $enregistrement->texte);
                $zonePrincipale = $exo->afficherComplet();
			}
        }
        $query = null;
        $connection = null;
        break;
}

include("codesite/squelette.php");

?>

