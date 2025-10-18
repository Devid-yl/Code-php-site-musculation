<?php

function connecter(){
    try{
		$dns = "mysql:host=mysql.info.unicaen.fr;port=3306;dbname=yala221_bd;charset=utf8";
        $utilisateur = "yala221";
        $motDePasse = "ooRe2EiTh7weobee";
        $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        );
        $connection = new PDO( $dns, $utilisateur, $motDePasse, $options );
        return($connection);
        }catch ( Exception $e ){
        echo "Connection à MySQL impossible : ", $e->getMessage();
        die();
        }
}


class Exo{
	private $id;
    private $titre;
    private $auteur;
    private $texte;
    private $date_publication;

    public function __construct($id,$titre,$auteur,$date_publication="00-00-0000",$texte){	
		$this->id=bin2hex($titre);
        $this->titre=$titre;
        $this->auteur=$auteur;
        $this->texte=$texte;
        $this->date_publication=$date_publication;
    }

    public function afficherSimple(){
       $ligneT = '<li><a href="index.php?action='.$this->id.'">'.$this->titre.'</a>, par '.$this->auteur.'</li>';
        return $ligneT;
    }
    public function afficherComplet(){
        $ligneT= '<exo>';
        $ligneT.='<h2>'.$this->titre.'</h2>';
        $ligneT.='<h3>Crée le '.date("d/m/Y", strtotime($this->date_publication)).'<img src="imageexo/'.$this->titre.'.gif" alt="" style="float: right;"></h3>';
        $ligneT.='<p>'.$this->texte.'</p>';
        $ligneT.='<h4>Ecrit par '.$this->auteur.'</h4>';
        $ligneT.='<button onclick="location.href=\'index.php?action=update&id='.$this->id.'\'"class="btn btn-primary">Modifier</button>';
        $ligneT.='<button onclick="location.href=\'index.php?action=delete&id='.$this->id.'\'"class="btn btn-secondary">Supprimer</button>';
        $ligneT.='</exo>';
        return $ligneT;
    }
}

$donneeTriListe = array(
    "date ASC"=>"Date croissante",
    "date DESC"=>"Date décroissante",
    "titre ASC"=>"Nom A-Z",
    "titre DESC"=>"Nom Z-A",
    "auteur ASC"=>"Auteur A-Z",
    "auteur DESC"=>"Auteur Z-A",
 );

$titreP=null; $zonePrincipale=null; $file=null; $id=null;$titre = null;$auteur = null;$texte = null;$date_publication = null;			
$erreur=array("titre"=>null,"auteur"=>null,"texte"=>null,"image"=>null);
$tab_Exo=array();
?>
