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
	    $sqlAcademies = "SELECT bac.libelle, etudiant.id_etudiant, bac_etu.*, academie.libelle
        FROM bac_etu
        INNER JOIN etudiant on bac_etu.id_etudiant=etudiant.id_etudiant
        LEFT JOIN bac on bac.id_bac = bac_etu.id_bac
        LEFT JOIN academie on academie.code = bac_etu.id_academie
        WHERE etudiant.id_etudiant =:id_etudiant
        AND bac.id_bac=:id_bac ";
	    $resultatSqlAcademies = $pdo->prepare($sqlAcademies);
	    $resultatSqlAcademies->execute($params);
	    $tableauToutesAcademies = $resultatSqlAcademies->fetchAll(\PDO::FETCH_ASSOC);
	    
	    return $tableauToutesAcademies;
	}
	
	public function getDiplomes($id_etudiant)
	{
	    $pdo = $this->pdo;
	    
	    $params = array(
	        ':id_etudiant' => $id_etudiant
	    );
	    $sqlDiplome = "SELECT diplome.libelle
        FROM diplome
        INNER JOIN diplome_etu on diplome_etu.id_diplome=diplome.id_diplome
        INNER JOIN etudiant on etudiant.id_etudiant=diplome_etu.id_etudiant
        WHERE etudiant.id_etudiant =:id_etudiant";
	    $resultatSqlDiplome = $pdo->prepare($sqlDiplome);
	    $resultatSqlDiplome->execute($params);
	    $tableauToutDiplomes = $resultatSqlDiplome->fetchAll(\PDO::FETCH_ASSOC);
	    
	    return $tableauToutDiplomes;
	}
	
	
    public function getEtablissement($id_etudiant)
	{
	    $pdo = $this->pdo;
	    
	    $params = array(
	        ':id_etudiant' => $id_etudiant
	    );
	    $sqlEtablissement = "SELECT etabliss_etu.nometabl
        FROM etabliss_etu
        INNER JOIN etudiant on etudiant.id_etudiant = etabliss_etu.id_etudiant
        WHERE etudiant.id_etudiant =:id_etudiant";
	    $resultatSqlEtablissement = $pdo->prepare($sqlEtablissement);
	    $resultatSqlEtablissement->execute($params);
	    $tableauToutLesEtab = $resultatSqlEtablissement->fetchAll(\PDO::FETCH_ASSOC);
	    
	    return $tableauToutLesEtab;
	} 
	
	public function getConcours($id_etudiant)
	{
	    $pdo = $this->pdo;
	    
	    $params = array(
	        ':id_etudiant' => $id_etudiant
	    );
	    $sqlConcours = "SELECT concours.libelle
        FROM concours
        INNER JOIN etudiant on etudiant.id_concours = concours.id_concours
        WHERE etudiant.id_etudiant =:id_etudiant";
	    $resultatSqlConcours = $pdo->prepare($sqlConcours);
	    $resultatSqlConcours->execute($params);
	    $tableauToutLesConcours = $resultatSqlConcours->fetchAll(\PDO::FETCH_ASSOC);
	    
	    return $tableauToutLesConcours;
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
	
	
	// Requete permettant de detecter un accent ou caract dans une colone
	//SELECT * FROM `adresse` where adre LIKE _utf8'%é%' COLLATE utf8_bin
	
	//Requete permettant de remplacer les caractères speciaux par ...
	/*UPDATE diplome_etu
	SET intitule = REPLACE(intitule, 'ÃƒÂ©', 'texte de remplacement')
	
	*
	*SELECT * FROM `etudiant` WHERE INE='2505001527X'
	*/
    
	
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
