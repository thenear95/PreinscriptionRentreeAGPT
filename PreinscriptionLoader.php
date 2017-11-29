<?php

namespace Jobs\Model\Process\DataTransfer\Acquisition\Rentree\Etudiants\Preinscription;

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
	    $idcandpcl = '';
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
	    $sitmaritale = '';
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
    
        $ine = '';
        $params = array (
            ':ine' => ine
        );
        $sqlIne = "select * from etudiant
		where id_niveauForm!=1 AND (INE_valid=1 OR  valideDeve=1) AND archive=0 ";
        
        $resultatSqlIne = $this->pdo->prepare ( $sqlIne );
        $resultatSqlIne->execute ( $params );
        
        if ($resultatSqlIne != null) {
            
            $tableauIne = $resultatSqlIne->fetchAll ( \PDO::FETCH_ASSOC );
            foreach ( $tableauIne as $valIne ) {
                $idconcours = $valIne ['id_concours'];
                }
            }
       }
       
       // FAIRE UN GET ADRESSE EN FONCTION DE LA TABLE ADRESSE

	
	/**
	 *
	 * @param \PDO $pdo        	
	 */
	public function __construct(EndPointConnection $pdo)
	{
		$this->pdo = $pdo;
		//$this->initTableauNiveauForm ();
	}
}
