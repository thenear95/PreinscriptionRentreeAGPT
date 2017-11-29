<?php

namespace Jobs\Model\Process\DataTransfer\Acquisition\Rentree\Etudiants\Preinscription;
use Minibus\Model\Process\DataTransfer\Export\AbstractDataExportAgent;
use Minibus\Model\Entity\Execution;
use Jobs\Model\Entity\Personne;
use Jobs\Model\Entity\DossierEtudiant;
use Jobs\Model\Process\DataTransfer\Acquisition\Rentree\ConvertPersonne;
use Doctrine\DBAL\Driver\PDOException;

use Jobs\Model\Entity\AttributionPersonne;

class TransferAgent extends AbstractDataExportAgent 
{
    
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
		
		$am = $this->getEntityManager ();
		$elementDossierRepository = $this->getElementDossierRepository ( $am);

		$this->getLogger ()
		->info ( "Début acquisition étudiants depuis Préinscription" );
		//$this->getExecution ()
		//->setState ( Execution::RUNNING_STATE );

		//$this->setAlive ( true );
		$pdo = $this->getPreinscriptionConnexion ();

		if (false === $pdo) {
			$this->setAlive ( false );
			return;
		}
		
		$connectionParams=$this->getConnectionParameters();
		$this->getLogger()->info(var_export($connectionParams, true));

		// Récupération des étudiants depuis Préinscription
		$preinscriptionLoader = new PreinscriptionLoader ( $pdo );
		try {

			$etudiants = $preinscriptionLoader->getAllEtudiant ();
			
			foreach ( $etudiants as $etudiant ) {
			$this->getLogger()->info($etudiant['id_etudiant']. " : ".$etudiant['Nom']);
			}
			
		} catch ( \Exception $e ) {
			$message = "Un problème est survenu lors de la récupération des personnes dans base de données Rentrée : " . $e->getMessage ();
			$this->setAlive ( false );
			self::alertError ( $message );
			$this->getLogger ()
			->err ( $e->getMessage () );

			return;
		}
		
		
		$nbEtudiants = count ( $etudiants );
		$this->getLogger ()->info ( "Nombre total d'etudiant : " . $nbEtudiants );
		
		
		$i=1; 
		
		//set_alive(false);

		// Pour chaque étudiant, récupération de ses candidatures dans PED
		foreach ( $etudiants as $etudiant ) 
        {
		    
		    //$sitMaritale = $preinscriptionLoader->getSitMaritale($sitmaritale);
            $sitMaritale = $etudiant ['id_situation_familiale'];
		    $idEtudiantPreinscription = $etudiant ['id_etudiant'];
		    $idCandidatPCL = $etudiant ['id_candidat_PCL'];
		    $login = $etudiant ['login_ldap'];
		    $sexe = $etudiant ['sexe'];
		    $nom = $etudiant ['Nom'];
		    $prenom = $etudiant ['Prenom1'];
		    $prenomautre = $etudiant ['Prenom2'];
		    $bpublinommari = 1;
		    $codenationalite = $etudiant ['id_nationalite1'];
		    $codedblnationalite = $etudiant ['id_nationalite2'];
		    $codepaysnaiss = $etudiant ['id_pays_naissance'];
		    //$datenaiss = $etudiant ['date_naissance'];
		    $iddeptnaiss = $etudiant ['id_departement_naiss'];
		    $codepaysnaiss = $etudiant ['id_pays_naissance'];
		    $nbenfant = $etudiant ['enfants'];
		    $villenaiss = $etudiant ['ville_naissance'];
		    $telephonefixe = $etudiant ['telephone'];
		    $telephonemobile = $etudiant ['mobile'];
		    $mail = $etudiant ['mel'];
		    $bpubliphoto = $etudiant ['photo_valide'];
		    $numsecu = $etudiant ['N_Securite_Soc'];
		    
		    $etudiant_id = $etudiant ['id_etudiant'];
		    $ine = $etudiant ['INE'];
		    
		   
		    
		   // $this->getLogger ()->info ( "preetu " . $idEtudiantPreinscription);
		   //$this->getLogger ()->info ( "Date : " .$datenaiss);
		   //$this->getLogger ()->info ( "Prenom :" .$prenom);
		    
		    if ($idCandidatPCL == null) {
		        
		    //id 
		    $idexterne = "preetu".$idEtudiantPreinscription;
		    $etudiantPED = $elementPersonnesRepository->findBy ( array (
		        'idexterne' => $idexterne));
		    
		    //prenom1
		    $prenomP = explode(",",$prenom);

		    $this->getLogger ()->info ( "PrenomP :".$prenomP[0]);
		    $prenom1= $this->encodeIfNonUTF8($prenomP[0]);
		    
		    // Civilité par rapport au sexe
		    $civilite = " ";
		    
		    if ($sexe == 'M'){
		        $civilite= "M";
		    }
		    else{
		        $civilite= "Mme";
		    }

		    $this->insertPreetu($em, $idexterne, $login, $civilite, $sexe, $sitMaritale, $nom, $prenom1, $prenomautre, $bpublinommari, $codenationalite, $codedblnationalite, $codepaysnaiss, /*$datenaiss,*/ $iddeptnaiss, $nbenfant, $villenaiss, $telephonefixe, $telephonemobile, $mail, $bpubliphoto, $numsecu );
		    $this->insertDossetu($am, $etudiant_id, $ine);
		    }
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
		          
		          $prenomP = explode(",",$prenom);
		          
		          $this->getLogger ()->info ( "PrenomP :".$prenomP[0]);
		          $prenom1= $this->encodeIfNonUTF8($prenomP[0]);
		          
		          // Civilité par rapport au sexe
		          $civilite = " ";
		          
		          if ($sexe == 'M'){
		              $civilite= "M";
		          }
		          else{
		              $civilite= "Mme";
		          }
		          
		          $this->insertPreetu($em, $idexterne, $login, $civilite, $sexe, $sitMaritale, $nom, $prenom1, $prenomautre, $bpublinommari, $codenationalite, $codedblnationalite, $codepaysnaiss, /*$datenaiss,*/ $iddeptnaiss, $nbenfant, $villenaiss, $telephonefixe, $telephonemobile, $mail, $bpubliphoto, $numsecu );
		          $this->insertDossetu($am, $etudiant_id, $ine);
		      }
        }
		    
// 		    foreach ($allpersonnes as $personne)
		   
// 		        try {
		           
		            
// 		        } catch (Exception $e) {
// 		        }
		    
    		$this->getLogger()->info ( " Fin du processus." );
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
	 * @return void|\Doctrine\ORM\EntityRepository
	 */
	public function getElementDossierRepository($entityManager)
	{
	    try {
	        $elementDossierRepository = $entityManager->getRepository ( 'Jobs\Model\Entity\DossierEtudiant' );
	    } catch ( Exception $e ) {
	        $this->setAlive ( false );
	        $this->getLogger ()
	        ->err ( $e->getMessage () );
	        
	        return;
	    }
	    return $elementDossierRepository;
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
	public function insertPreetu($em, $idexterne, $login, $civilite, $sexe, $sitMaritale, $nom, $prenom1, $prenomautre, $bpublinommari, $codenationalite, $codedblnationalite, $codepaysnaiss, /*$datenaiss,*/ $iddeptnaiss, $nbenfant, $villenaiss, $telephonefixe, $telephonemobile, $mail, $bpubliphoto, $numsecu)
	{
	    	        
	        $preEtu = New \Jobs\Model\Entity\Personne();
	        $preEtu->setIdExterne( $idexterne);
	        $preEtu->setcivilite( $civilite);
	        $preEtu->setLogin( $login);
	        $preEtu->setSexe( $sexe);
	        $preEtu->setSitMaritale( $sitMaritale);
	        $preEtu->setNom( $nom);
	        $preEtu->setPrenom( $prenom1);
	        $preEtu->setPrenomAutre( $prenomautre);
	        $preEtu->setbPubliNomMari( $bpublinommari);
	        $preEtu->setCodenationnalite( $codenationalite);
	        $preEtu->setCodedblNationalite( $codedblnationalite);
	        $preEtu->setCodePaysNaiss( $codepaysnaiss);
	        //$preEtu->setDateNaiss( $datenaiss);
	        $preEtu->setIdDeptNaiss( $iddeptnaiss);
	        $preEtu->setNbEnfants( $nbenfant);
	        $preEtu->setVilleNaiss( $villenaiss);
	        $preEtu->setTelephoneFixe( $telephonefixe);
	        $preEtu->setTelephoneMobile( $telephonemobile);
	        $preEtu->setEmail( $mail);
	        $preEtu->setBpubliphotointranet( $bpubliphoto);
	        $preEtu->setNumSecu( $numsecu);
	        //$preEtu->setEmailetb( $emailtab);
	        $em->persist ( $preEtu );
	        $em->flush ();
	    
	    return ;
	}
	
	public function insertDossetu($am, $etudiant_id, $ine)
	{
	    
	    $preEtu = New \Jobs\Model\Entity\DossierEtudiant();
	    $preEtu->setEtudiant ( $etudiant_id);
	    $preEtu->setIne ( $ine);
	    $am->persist ( $preEtu );
	    $am->flush ();
	    
	    return ;
	}
	
	function isUTF8($string)
	{
	    return (utf8_encode(utf8_decode($string)) == $string);
	}
	
	function encodeIfNonUTF8($string)
	{
	    if ($this->isUTF8($string)) {
	        return $string;
	    } else {
	        return utf8_encode($string);
	    }
	}
	
	
}
