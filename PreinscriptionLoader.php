<?php

namespace Jobs\Model\Process\DataTransfer\Acquisition\Rentree\Preinscription;

use Minibus\Model\Process\DataTransfer\EndPointConnection;

class PreinscriptionLoader {
	private $pdo;
	//private $tableauNiveauForm;
	
	/**
	 */
	public function getAllEtudiant()
	{
		$pdo = $this->pdo;
		
		$sqlAllEtudiant = "SELECT * FROM etudiant where archive=0 AND id_niveauForm=1 ";
		$resultatSqlAllEtudiant = $pdo->prepare ( $sqlAllEtudiant );
		$resultatSqlAllEtudiant->execute ();
		$tableauAllEtudiant = $resultatSqlAllEtudiant->fetchAll ( \PDO::FETCH_ASSOC );
		
		return $tableauAllEtudiant;
	}
	
/*	public function initTableauNiveauForm()
    	{
    	    $sqlTableauNiveauForm = "select niveauForm.id_niveauForm,niveauForm.id_typeForm,niveauForm.code_PCL,typeForm.situation_PCL
    	from niveauForm
    	inner join typeForm on niveauForm.id_typeForm=typeForm.id_typeForm
    	where code_PCL is not Null
    	order by code_PCL";
    	    
    	    $resultatSqlTableauNiveauForm = $this->pdo->prepare ( $sqlTableauNiveauForm );
    	    $resultatSqlTableauNiveauForm->execute ();
    	    $this->tableauNiveauForm = $resultatSqlTableauNiveauForm->fetchAll ( \PDO::FETCH_ASSOC );
    	} */
	
	public function getcandPCL($idcandpcl) {
	    idcandpcl = '';
	    $params = array (
	        ':idcandpcl' => idcandpcl
	    );
	    $sqlIdCandPCL = "select * from etudiant where id_candidat_PCL IS NOT NULL AND ss_valid=1 AND login_ldap IS NOT NULL";
	    
	    $resultatSqlIdCandPCL = $this->pdo->prepare ( $sqlIdCandPCL );
	    $resultatSqlIdCandPCL->execute ( $params );
	    
	    if ($resultatSqlIdCandPCL != null) {
	        
	        $tableauIdCandPCL = $resultatSqlIdCandPCL->fetchAll ( \PDO::FETCH_ASSOC );
	        foreach ( $tableauIdCandPCL as $valIdCandPCL) {
	            $idcandpcl = $valIdCandPCL ['id_candidat_pcl'];
	        }
	    }
	    
	    return $idcandpcl;
	}
	
	
	public function getSitMaritale ($sitmaritale)
	{
	    sitmaritale = '';
	    $params = array (
	        ':sitmaritale' => sitmaritale
	    );
	    $sqlSitMaritale = "select etudiant.id_etudiant, situation_familiale.libelle from etudiant, situation_familiale
		where situation_familiale.id_situation_familiale=:etudiant.id_situation_familiale ";
	    
	    $resultatSqlSitMaritale = $this->pdo->prepare ( $sqlSitMaritale );
	    $resultatSqlSitMaritale->execute ( $params );
	    
	    if ($resultatSqlSitMaritale != null) {
	    
	    $tableauSitMaritale = $resultatSqlSitMaritale->fetchAll ( \PDO::FETCH_ASSOC );
	    foreach ( $tableauSitMaritale as $valSitMaritale ) {
	    $sitmaritale = $valSitMarital ['situation_maritale'];
	    }
	    }
	    
	    return $sitmaritale;
    }

    
    // Meme résultat que getCandPCL
        
    public function getMaster() {
    
        ine = '';
        $params = array (
            ':ine' => ine
        );
        $sqlIne = "select * from etudiant
		where id_niveauForm!=1 AND (INE_valid=1 OR  valideDeve=1) ";
        
        $resultatSqlIne = $this->pdo->prepare ( $sqlIne );
        $resultatSqlIne->execute ( $params );
        
        if ($resultatSqlIne != null) {
            
            $tableauIne = $resultatSqlIne->fetchAll ( \PDO::FETCH_ASSOC );
            foreach ( $tableauIne as $valIne ) {
                $idconcours = $valIne ['id_concours'];
                }
            }
       }
       
       // Ajoute seulement les étudiants pour ceux qui ont id_candidat_PCL différent de NULL
       
       /*public function getCandidatPCL($candpcl) {
           candpcl = '';
           $params = array (
               ':candpcl' => candpcl
           );
           $sqlCandPcl = "select * from etudiant
		where id_candidat_PCL!='' ";
           
           $resultatCandPcl = $this->pdo->prepare ( $sqlCandPcl );
           $resultatSqlCandPcl->execute ( $params );
           
           if ($resultatSqlCandPcl != null) {
               
               $tableauCandPcl = $resultatSqlCandPcl->fetchAll ( \PDO::FETCH_ASSOC );
               foreach ( $tableauCandPcl as $valCandPcl ) {
                   $candpcl = $valCandPcl ['id_candidat_PCL'];
               }
           }
       }
      */
       
       /* 
        * requete pour tout les type form et liebele A REVOIR 
        * 
        * SELECT niveauForm.id_niveauForm, niveauForm.libelle, niveauForm.id_typeForm, typeForm.libelle 
        * FROM niveauForm,typeForm 
        * WHERE niveauForm.id_typeForm=typeForm.id_typeForm */

	
	/**
	 *
	 * @param \PDO $pdo        	
	 */
	public function __construct(EndPointConnection $pdo)
	{
		$this->pdo = $pdo;
		$this->initTableauNiveauForm ();
	}
}