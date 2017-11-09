<?php

namespace Jobs\Model\Process\DataTransfer\Acquisition\Rentree\Etudiants\Preinscription;

use Minibus\Model\Entity\Execution;
use Jobs\Model\Entity\Personne;
use Jobs\Model\Entity\AttributionPersonne;

class TransferAgent extends AbstractDataExportAgent {
	const UAIAgro = '0753465J';
	public function run()
	{
		$this->getLogger ()
		->info ( "Exécution en mode " . $this->getExecutionMode () );

		switch ($this->getExecutionMode ()) {
			case 'sync' :
				$this->runSync ();
				break;
			default :
				$this->runControl ();
		}
	}
	public function runSync()
	{

		/**
		 * Acquisition des données depuis Préinscription
		 */
		$em = $this->getEntityManager ();
		$elementPersonnesRepository = $this->getElementPersonnesRepository ( $em );

		$this->getLogger ()
		->info ( "Début acquisition étudiants depuis Préinscription" );
		$this->getExecution ()
		->setState ( Execution::RUNNING_STATE );

		$this->setAlive ( true );
		$pdo = $this->getPreinscriptionConnexion ();

		if (false === $pdo) {
			//$this->setAlive ( false );
			return;
		}
		
		//$connectionParams=$this->getConnectionParameters();
		//$this->getLogger()->info(var_export($connectionParams, true));

		// Récupération des étudiants depuis Préinscription
		$preinscriptionLoader = new PreinscriptionLoader ( $pdo );
		try {

			$etudiants = $preinscriptionLoader->getAllEtudiant ();
			
			//foreach ( $etudiants as $etudiant ) {
			//$this->getLogger()->info($etudiant['id_etudiant']. " : ".$etudiant['Nom']);
			//}
			
		} catch ( \Exception $e ) {
			$message = "Un problème est survenu lors de la récupération des personnes dans base de données Rentrée : " . $e->getMessage ();
			$this->setAlive ( false );
			self::alertError ( $message );
			$this->getLogger ()
			->err ( $e->getMessage () );

			return;
		}
		
		/*
		$nbEtudiants = count ( $etudiants );
		$this->getLogger ()->info ( "Nombre total d'etudiant : " . $nbEtudiants );
		
		
		$i=1; */
		
		//set_alive(false);

		// Pour chaque étudiant, récupération de ses candidatures dans PED
		foreach ( $etudiants as $etudiant ) {
		    
		    $sitMaritale = $preinscriptionLoader->getSitMaritale($sitmaritale);
		    // $PaysEtu = $preinscriptionLoader->getPays($pays) Faire une méthode 
		    
		    $idEtudiantPreinscription = $etudiant ['id_etudiant'];
		    //$civilite = $etudiant ['existe pad dans la BD que faire ??'];
		    $idCandidatPCL = $etudiant ['id_candidat_PCL'];
		    $login = $etudiant ['login_ldap'];
		    $sexe = $etudiant ['sexe'];
		    $nom = $etudiant ['Nom']; // Ne pas oublier de faire une liasion avec le nom de JF
		    $prenom = $etudiant ['Prenom1'];
		    $prenomautre = $etudiant ['Prenom2'];
		    $bpublinommari = $etudiant ['Nom'];
		    $codenationalité = $etudiant ['id_nationnalité1'];
		    $codedblnationalité = $etudiant ['id_nationnalité2'];
		    $codepaysnaiss = $etudiant ['id_pays_naissance'];
		    $datenaiss = $etudiant ['date_naissance'];
		    $iddeptnaiss = $etudiant ['id_departement_naissance'];
		    $villenaiss = $etudiant ['ville_naissance'];
		    //$EmailEtb = $etudiant [''];
		    $telephonefixe = $etudiant ['telephone'];
		    $telephonemobile = $etudiant ['mobile'];
		    //$CodePostal = $etudiant [''];
		    //$Localite = $etudiant [''];
		    $mail = $etudiant ['mel'];
		    //$Adresse = $etudiant [''];
		    $bpubliphoto = $etudiant ['photo_valid'];
		    $numsecu = $etudiant ['N_Sécurite_Soc'];
		    //Voir pour les dates
		    
		   
		    
		   // $this->getLogger ()->info ( "preetu " . $idEtudiantPreinscription);    
		    
		    if ($idCandidatPCL == null) {
		        
		    
		    $idexterne = "preetu".$idEtudiantPreinscription;
		    $etudiantPED = $elementPersonnesRepository->findBy ( array (
		        'idexterne' => $idexterne));
		    
		    $this->insertPreetu($em, $idexterne, $login, $sexe, $nom, $prenom, $prenomautre, $bpublinommari, $codenationalite, $codedblnationalite, $codepaysnaiss, $datenaiss, $iddeptnaiss, $villenaiss, $telephonefixe, $telephonemobile, $mail, $bpubliphoto, $numsecu );
		    
		    /*
		    if (id_candidat_PCL!=NULL) $allPersonnes = $elementPersonnesRepository->findBy ( array (
		        'id_candidat' => $idCandidatPCL
		    ) );
		    
		   // $nbPersonnes = $nbPersonnes + count ( $allPersonnes );
		    
		    
		     if (count ( $etudiantPED )>0) {
		        $this->getLogger ()
		        ->info ( '  etudiant avec Id externe trouvé = ' . $idexterne );
		   }*/
		    
		      else{
		          
		          $idexterne = "pcletu".$idCandidatPCL;
		          $etudiantPED = $elementPersonnesRepository->findBy ( array (
		              'idexterne' => $idexterne));
		          
		          $this->insertPreetu($em, $idexterne, $login, $sexe, $nom, $prenom, $prenomautre, $bpublinommari, $codenationalite, $codedblnationalite, $codepaysnaiss, $datenaiss, $iddeptnaiss, $villenaiss, $telephonefixe, $telephonemobile, $mail, $bpubliphoto, $numsecu );
		      }
		         
		    }
		    
		    foreach ($allpersonnes as $personne)
		        try {
		           
		            
		        } catch (Exception $e) {
		        }
		    
		}
    		$this->getLogger ()
    		->info ( " Fin du processus." );
    		$this->setAlive ( false );
	}	
	

		//$this->setAlive ( false );

	/**
	 * Méthode de contrôle
	 */
	public function runControl()
	{
		$this->getLogger ()
		->info ( " Not implemented." );

		$this->getLogger ()
		->info ( " Fin du processus." );
		$this->setAlive ( false );
	}

	/**
	 *
	 * @return void|\Doctrine\ORM\EntityRepository
	 */
	public function getElementPersonnesRepository($entityManager)
	{
		try {
			$elementPersonnesRepository = $entityManager->getRepository ( 'Jobs\Model\Entity\Personne' );
		} catch ( Exception $e ) {
			$this->setAlive ( false );
			$this->getLogger ()
			->err ( $e->getMessage () );

			return;
		}
		return $elementPersonnesRepository;
	}

	/**
	 *
	 * @return boolean|\Minibus\Model\Process\DataTransfer\PDO
	 */
	public function getPreinscriptionConnexion()
	{
		try {

			$pdo = $this->getEndPointConnection ();
		} catch ( \Exception $e ) {
			$message = "Un problème est survenu lors de la connexion à la base de données Preinscription : " . $e->getMessage ();
			$this->setAlive ( false );
			self::alertWarn ( $message );
			$this->getLogger ()
			->err ( $e->getMessage () );

			return;
		}
		return $pdo;
	}
	
	
	// Insert idexterne dans personne pour un étudiant première année
	public function insertPreetu($em, $idexterne, $login, $sexe, $nom, $prenom, $prenomautre, $bpublinommari, $codenationalite, $codedblnationalite, $codepaysnaiss, $datenaiss, $iddeptnaiss, $villenaiss, $telephonefixe, $telephonemobile, $mail, $bpubliphoto, $numsecu)
	{
	    	        
	        $preEtu = New \Jobs\Model\Entity\Personne();
	        $preEtu->setIdExterne( $idexterne);
	        //$preEtu->setcivilite( $civilite);
	        $preEtu->setLogin( $login);
	        $preEtu->setSexe( $sexe);
	        $preEtu->setNom( $nom);
	        $preEtu->setPrenom( $prenom);
	        $preEtu->setPrenomAutre( $prenomautre);
	        $preEtu->setbPubliNomMari( $bpublinommari);
	        $preEtu->setCodeNationalite( $codenationalite);
	        $preEtu->setCodeDblNationalite( $codedblationalite);
	        $preEtu->setCodePaysNaiss( $codepaysnaiss);
	        $preEtu->setDateNaiss( $datenaiss);
	        $preEtu->setIdDeptNaiss( $iddeptnaiss);
	        $preEtu->setVilleNaiss( $villenaiss);
	        $preEtu->setTelephoneFixe( $telephonefixe);
	        $preEtu->setTelephoneMobile( $telephonemobile);
	        $preEtu->setMail( $mail);
	        $preEtu->setBpubliPhoto( $bpubliphoto);
	        $preEtu->setNumSecu( $numsecu);
	        $em->persist ( $preEtu );
	        $em->flush ();
	    
	    return ;
	}
	
}
