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
	
	
	public function getAllInfosBac($id_etudiant)
	{
	    $pdo = $this->pdo;
	    
	    $params = array (
	        ':id_etudiant' => $id_etudiant
	    );
	    $sqlBacEtu = "SELECT * FROM bac_etu where id_etudiant=:id_etudiant";
	    $resultatSqlBacEtu = $pdo->prepare ( $sqlBacEtu );
	    $resultatSqlBacEtu->execute ($params);
	    $tableauBacEtu = $resultatSqlBacEtu->fetchAll ( \PDO::FETCH_ASSOC );
	    
	    return $tableauBacEtu;
	}
	
	public function getSeriebac($id_etudiant, $id_bac)
	{
	    $pdo = $this->pdo;
	    
	    $params = array(
	        ':id_etudiant' => $id_etudiant,
	        ':id_bac' => $id_bac
	    );
	    $sqlSerieBac = "SELECT bac.libelle, etudiant.id_etudiant, bac_etu.*
        FROM bac_etu 
        INNER JOIN etudiant on bac_etu.id_etudiant=etudiant.id_etudiant 
        LEFT JOIN bac on bac.id_bac = bac_etu.id_bac 
        WHERE etudiant.id_etudiant =:id_etudiant 
        AND bac.id_bac=:id_bac ";
	    $resultatSqlSerieBac = $pdo->prepare($sqlSerieBac);
	    $resultatSqlSerieBac->execute($params);
	    $tableauSerieBac = $resultatSqlSerieBac->fetchAll(\PDO::FETCH_ASSOC);
	    
	    return $tableauSerieBac;
	}
	
	public function getAcademies($id_etudiant, $id_bac)
	{
	    $pdo = $this->pdo;
	    
	    $params = array(
	        ':id_etudiant' => $id_etudiant,
	        ':id_bac' => $id_bac
	    );
	    $sqlSerieBac = "SELECT bac.libelle, etudiant.id_etudiant, bac_etu.*, academie.libelle
        FROM bac_etu
        INNER JOIN etudiant on bac_etu.id_etudiant=etudiant.id_etudiant
        LEFT JOIN bac on bac.id_bac = bac_etu.id_bac
        LEFT JOIN academie on academie.code = bac_etu.id_academie
        WHERE etudiant.id_etudiant =:id_etudiant
        AND bac.id_bac=:id_bac ";
	    $resultatSqlSerieBac = $pdo->prepare($sqlSerieBac);
	    $resultatSqlSerieBac->execute($params);
	    $tableauSerieBac = $resultatSqlSerieBac->fetchAll(\PDO::FETCH_ASSOC);
	    
	    return $tableauSerieBac;
	}
	
/*	
	public function getAcademie($id_academie)
	{
	    
	    $pdo = $this->pdo;
	    
	    $params = array (
	        ':id_academie' => $id_academie
	    );

	    
	    try {
	        $sqlAcademie = "SELECT libelle FROM academie WHERE code=:id_academie";
	        
	        $resultatSqlAcademie = $pdo->prepare ( $sqlAcademie );
	        
	        $resultatSqlAcademie->execute ($params);
	        
	        $tableauAcademie = $resultatSqlAcademie->fetchAll ( \PDO::FETCH_ASSOC );
	    } catch (\Exception $e) {
	        $message = "Un problème est survenu  : " . $e->getMessage();
	        self::alertWarn($message);
	        $this->getLogger()->err($e->getMessage());
	        $this->setAlive(false);
	        return;
	    } 
	    
	    

	    
	   // $this->getLogger()->info("Count : ". count($tableauAcademie));
	    
	    foreach (  $tableauAcademie as $value )
	    {
	        $lib = $value['libelle'];
	    }
	    
	    return $lib;
	    
	}*/
	
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
	
	
	// Requete permettant de detecter un accent ou caract dans une colone
	//SELECT * FROM `adresse` where adre LIKE _utf8'%é%' COLLATE utf8_bin
	
	//Requete permettant de remplacer les caractères speciaux par ...
	/*UPDATE diplome_etu
	SET intitule = REPLACE(intitule, 'ÃƒÂ©', 'texte de remplacement')*/
    
	
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
