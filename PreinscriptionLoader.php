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
		
		$sqlAllEtudiant = "SELECT * FROM etudiant where (archive=0 AND id_niveauForm=1) OR (id_niveauForm!=1 AND INE_valid=1 OR valideDeve=1 AND archive=0) ";
		$resultatSqlAllEtudiant = $pdo->prepare ( $sqlAllEtudiant );
		$resultatSqlAllEtudiant->execute ();
		$tableauAllEtudiant = $resultatSqlAllEtudiant->fetchAll ( \PDO::FETCH_ASSOC );
		
		return $tableauAllEtudiant;
	}
	
	public function getAdresse($id_etudiant)
	{
	    $pdo = $this->pdo;
	    
	    $params = array (
	        ':id_etudiant' => $id_etudiant
	    );
	    $sqlAdresse = "SELECT rue, ville, codeP FROM adresse where id_etudiant=:id_etudiant";
	    $resultatSqlAdresse = $pdo->prepare ( $sqlAdresse );
	    $resultatSqlAdresse->execute ($params);
	    $tableauAdresse = $resultatSqlAdresse->fetchAll ( \PDO::FETCH_ASSOC );
	    
	    return $tableauAdresse;
	}
	
	public function getInfosParentEtu($id_etudiant)
	{
	    $pdo = $this->pdo;
	    
	    $params = array (
	        ':id_etudiant' => $id_etudiant
	    );
	    $sqlParentEtu = "SELECT * FROM parent where id_etudiant=:id_etudiant";
	    $resultatSqlParentEtu = $pdo->prepare ( $sqlParentEtu );
	    $resultatSqlParentEtu->execute ($params);
	    $tableauParentEtu = $resultatSqlParentEtu->fetchAll ( \PDO::FETCH_ASSOC );
	    
	    return $tableauParentEtu;
	}
	
	
	public function getCspParent($id_etudiant, $id_lien_parente)
	{
	    $pdo = $this->pdo;
	    
	    $params = array(
	        ':id_etudiant' => $id_etudiant,
	        ':id_lien_parente' => $id_lien_parente
	    );
	    $sqlCspPar = "SELECT profession.libelle
        FROM  parent inner join etudiant on parent.id_etudiant=etudiant.id_etudiant
        LEFT JOIN profession on parent.id_profession = profession.id_profession
        WHERE etudiant.id_etudiant = :id_etudiant
        AND parent.id_lien_parente=:id_lien_parente";
	    $resultatSqlCspPar = $pdo->prepare($sqlCspPar);
	    $resultatSqlCspPar->execute($params);
	    $tableauCspPar = $resultatSqlCspPar->fetchAll(\PDO::FETCH_ASSOC);
	    
	    return $tableauCspPar;
	}
	
	/*
	public function getCsppere($id_profession)
	{
	    $csppere = '';
	    $params = array (
	        ':id_profession' => $id_profession
	    );
	    $sqlCsppere = "SELECT profession.libelle FROM profession, parent, etudiant WHERE profession.id_profession=:parent.id_profession AND parent.id_etudiant=etudiant.id_etudiant AND parent.id_lien_parente=1 ";
	    
	    $resultatSqlCsppere= $this->pdo->prepare ( $sqlCsppere );
	    $resultatSqlCsppere->execute ( $params );
	    
	    if ($resultatSqlCsppere != null) {
	        
	        $tableauCsppere = $resultatSqlCsppere->fetchAll ( \PDO::FETCH_ASSOC );
	        foreach ( $tableauCsppere as $valCsppere )
	        {
	            $csppere = $valCsppere ['libelle'];
	        }
	    }
	    
	    return $csppere;
	}
	
	
	public function getCspmere($id_profession)
	{
	    $cspmere = '';
	    $params = array (
	        ':id_profession' => $id_profession
	    );
	    $sqlCspere = "SELECT profession.libelle, etudiant.id_etudiant FROM profession, parent, etudiant WHERE profession.id_profession=parent.id_profession AND parent.id_etudiant=etudiant.id_etudiant AND parent.id_lien_parente=2 ";
	    
	    $resultatSqlCspmere= $this->pdo->prepare ( $sqlCspmere );
	    $resultatSqlCspmere->execute ( $params );
	    
	    if ($resultatSqlCspmere != null) {
	        
	        $tableauCspmere = $resultatSqlCspmere->fetchAll ( \PDO::FETCH_ASSOC );
	        foreach ( $tableauCspmere as $valCspmere )
	        {
	            $cspmere = $valCspmere ['libelle'];
	        }
	    }
	    
	    return $cspmere;
	} */
	
	// Requete permettant de detecter un accent ou caract dans une colone
	//SELECT * FROM `adresse` where adre LIKE _utf8'%é%' COLLATE utf8_bin
    
	
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
