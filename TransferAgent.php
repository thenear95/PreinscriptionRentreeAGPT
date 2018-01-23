<?php
namespace Jobs\Model\Process\DataTransfer\Acquisition\Rentree\Etudiants\Preinscription;

use Minibus\Model\Process\DataTransfer\Export\AbstractDataExportAgent;
use Minibus\Model\Entity\Execution;
use Jobs\Model\Entity\Personne;
use Jobs\Model\Entity\DossierEtudiant;
// use Jobs\Model\Process\DataTransfer\Acquisition\Rentree\ConvertPersonne;
use Doctrine\DBAL\Driver\PDOException;
use Jobs\Model\Entity\AttributionPersonne;
class TransferAgent extends AbstractDataExportAgent
{

    const UAIAgro = '0753465J';

    public function run()
    {
        $this->getLogger()->info("Exécution en mode " . $this->getExecutionMode());
        
        switch ($this->getExecutionMode()) {
            case 'sync':
                $this->runSync();
                break;
            default:
                $this->runControl();
        }
    }

    public function runSync()
    {
        
        /**
         * Acquisition des données depuis Préinscription
         */
        $em = $this->getEntityManager();
        $elementPersonnesRepository = $this->getElementPersonnesRepository($em);
        
        $am = $this->getEntityManager();
        $elementDossierRepository = $this->getElementDossierRepository($am);
        
        $im = $this->getEntityManager();
        $elementParentRepository = $this->getElementParentRepository($im);
        
        $this->getLogger()->info("Début acquisition étudiants depuis Préinscription");
        $pdo = $this->getPreinscriptionConnexion();
        
        if (false === $pdo) {
            $this->setAlive(false);
            return;
        }
        
        $connectionParams = $this->getConnectionParameters();
        $this->getLogger()->info(var_export($connectionParams, true));
        
        // Récupération des étudiants depuis Préinscription
        $preinscriptionLoader = new PreinscriptionLoader($pdo);
        try {
            
            $etudiants = $preinscriptionLoader->getAllEtudiant();
            
            foreach ($etudiants as $etudiant) {
                // $this->getLogger()->info($etudiant['id_etudiant'] . " : " . $etudiant['Nom']);
            }
        } catch (\Exception $e) {
            $message = "Un problème est survenu lors de la récupération des personnes dans base de données Rentrée : " . $e->getMessage();
            $this->setAlive(false);
            self::alertError($message);
            $this->getLogger()->err($e->getMessage());
            
            return;
        }
        
        $nbEtudiants = count($etudiants);
        $this->getLogger()->info("Nombre total d'etudiant : " . $nbEtudiants);
        
        $i = 1;
        
        // set_alive(false);
        
        // Pour chaque étudiant, récupération de ses candidatures dans PED
        foreach ($etudiants as $etudiant) {
            // PERSONNE
            $id_etudiant = $etudiant['id_etudiant'];
            $sitMaritaleEtu = $etudiant['id_situation_familiale'];
            $idEtudiantPreinscription = $etudiant['id_etudiant'];
            $idCandidatPCL = $etudiant['id_candidat_PCL'];
            $login = $etudiant['login_ldap'];
            $sexe = $etudiant['sexe'];
            $nom = $etudiant['Nom'];
            $prenom = $etudiant['Prenom1'];
            $prenomautre = $etudiant['Prenom2'];
            $bpublinommari = 1;
            $codenationalite = $etudiant['id_nationalite1'];
            $codedblnationalite = $etudiant['id_nationalite2'];
            $codepaysnaiss = $etudiant['id_pays_naissance'];
            // $datenaiss = $etudiant ['date_naissance'];
            $iddeptnaiss = $etudiant['id_departement_naiss'];
            $codepaysnaiss = $etudiant['id_pays_naissance'];
            $nbenfant = $etudiant['enfants'];
            $villenaiss = $etudiant['ville_naissance'];
            $telephonefixe = $etudiant['telephone'];
            $telephonemobile = $etudiant['mobile'];
            $mail = $etudiant['mel'];
            $pays = 1;
            $bpubliphoto = $etudiant['photo_valide'];
            $numsecu = $etudiant['N_Securite_Soc'];
            
            $ine = $etudiant['INE'];
            
            $adresses = $preinscriptionLoader->getAdresse($id_etudiant);
            
            foreach ($adresses as $adresse) {
                $adresse1 = $adresse['rue'];
                $localite = $adresse['ville'];
                $codePostal = $adresse['codeP'];
                // $this->getLogger ()->info ( "CP :".$codePostal);
            }
            
            if ($idCandidatPCL == null) {
                // id
                $idexterne = "preetu" . $idEtudiantPreinscription;
                $etudiantPED = $elementPersonnesRepository->findBy(array(
                    'idexterne' => $idexterne
                ));
                
                // prenom1
                $prenomP = explode(",", $prenom);
                
                // $this->getLogger ()->info ( "PrenomP :".$prenomP[0]);
                $prenom1 = $this->encodeIfNonUTF8($prenomP[0]);
                
                // Civilité par rapport au sexe
                $civilite = "";
                
                if ($sexe == 'H') {
                    $civilite = "M";
                } else {
                    $civilite = "Mme";
                }
                
                // Situation Maritale
                $sitMaritale = "";
                
                if ($sitMaritaleEtu = 1) 
                {
                    $sitMaritale = "Celibataire";
                } 
                
                else 
                {
                    $sitMaritale = "Marié";
                }
                
                // $this->getLogger()->info ("Candidat :".$idexterne ." "."CP :". $codePostal);
                $idPers = $this->insertPreetu($em, $idexterne, $login, $civilite, $sexe, $sitMaritale, $nom, $prenom1, $prenomautre, $bpublinommari, $codenationalite, $codedblnationalite, $codepaysnaiss, /*$datenaiss,*/ $iddeptnaiss, $nbenfant, $villenaiss, $telephonefixe, $telephonemobile, $pays, $codePostal, $localite, $mail, $adresse1, $bpubliphoto, $numsecu);
            } else {
                
                $idexterne = "preetu" . $idCandidatPCL;
                $etudiantPED = $elementPersonnesRepository->findBy(array(
                    'idexterne' => $idexterne
                ));
                
                $prenomP = explode(",", $prenom);
                
                // $this->getLogger ()->info ( "PrenomP :".$prenomP[0]);
                $prenom1 = $this->encodeIfNonUTF8($prenomP[0]);
                
                // Civilité par rapport au sexe
                $civilite = " ";
                
                if ($sexe == 'H') {
                    $civilite = "M";
                } else {
                    $civilite = "Mme";
                }
                
                // Civilité ( FAIRE UN CASE)
                
                $sitMaritale = " ";
                
                if ($sitMaritaleEtu == 0) {
                    
                    $sitMaritale = "Non indiqué";
                }
                
                if ($sitMaritaleEtu == 1) {
                    $sitMaritale = "Célibataire";
                }
                
                if ($sitMaritaleEtu == 2) {
                    
                    $sitMaritale = "Marié(e)";
                }
                
                $idPers = $this->insertPreetu($em, $idexterne, $login, $civilite, $sexe, $sitMaritale, $nom, $prenom1, $prenomautre, $bpublinommari, $codenationalite, $codedblnationalite, $codepaysnaiss, /*$datenaiss,*/ $iddeptnaiss, $nbenfant, $villenaiss, $telephonefixe, $telephonemobile, $pays, $codePostal, $localite, $mail, $adresse1, $bpubliphoto, $numsecu);
            }
            
            // DOSSIER ETU
            
            $infosparents = $preinscriptionLoader->getInfosParentEtu($id_etudiant);
  
            
            $ids_bacs_etu = $preinscriptionLoader->getAllInfosBac($id_etudiant);
            
            foreach ($ids_bacs_etu as $id_bac_etu)
            {
                $id_bac = $id_bac_etu ['id_bac'];
            }
            
            $seriesbac = $preinscriptionLoader->getSeriebac($id_etudiant, $id_bac);
            
            foreach ($seriesbac as $seriebac)
            {
                $seriebacetu = $seriebac ['libelle'];
                $anneebacetu = $seriebac ['annee_bac'];
                //$this->getLogger()->info(" Serie BAC : ". $seriebacetu);
                $id_academie = $seriebac ['id_academie'];
                
                //$this->getLogger()->info(" academie N° ". $id_academie);
            }
            
            $academies = $preinscriptionLoader->getAcademies($id_etudiant, $id_bac);
            
            foreach ($academies as $academie)
                {
                    $uneAcademie = $academie ['libelle'];
                }

            foreach ($infosparents as $infoparent)
            {
                $id_lien_parente = $infoparent['id_lien_parente'];
            
            
                $cspparents = $preinscriptionLoader->getCspparent($id_etudiant, $id_lien_parente);
                foreach ($cspparents as $cspparent)
                {
                    if ($id_lien_parente ==1) 
                    {
                        $professionPere = $cspparent ['libelle'];
                    }
                    
                    else 
                    {
                        $professionMere = $cspparent ['libelle'];
                        
                        $this->insertDossieretu($am, $professionPere, $professionMere, $ine, $matricule, $seriebacetu, $anneebacetu, $uneAcademie); 
                    }
                } 
            }

                
                
                $matricule = $etudiant ['login_ldap'];
           
            //$this->getLogger()->info(var_export($etudiants_id, true));

            // PARENT
            $parents = $preinscriptionLoader->getInfosParentEtu($id_etudiant);
            
            foreach ($parents as $parent) {
                $nomParent = $parent['nom'];
                $prenomParent = $parent['prenom'];
                $emailParent = $parent['mel'];
                $telephonefixeParent = $parent['telephone'];
                $telephonemobileParent = $parent['mobile'];
                $codepostalParent = $parent['codeP'];
                $localiteParent = $parent['ville'];
                $paysParent = $parent['id_pays'];
                $adresse1Parent = $parent['adresse'];
                $professionParent = $parent['profession'];
                $idLienParent = $parent['id_lien_parente'];
                
                $lienparente = " ";
                
                if ($idLienParent == 1) {
                    $lienparente = "pere";
                } else {
                    $lienparente = "mere";
                }
                
                $idexterneEtu = $this->convertIdExterne($parent['id_etudiant']);
                
                $idetudiantPED = $elementPersonnesRepository->findBy(array(
                    'idexterne' => $idexterne));

                $this->insertParentetu($im, $idPers, $nomParent, $prenomParent, $emailParent, $telephonefixeParent, $telephonemobileParent, $codepostalParent, $localiteParent, $paysParent, $adresse1Parent, $professionParent, $lienparente);
        
            }
            //$this->getLogger ()->info ( var_export( $idetudiantPED,true) );
        }
        
        $this->getLogger()->info(" Fin du processus.");
        $this->setAlive(false);
    }

    // $this->setAlive ( false );
    
    /**
     * Méthode de contrôle
     */
    public function runControl()
    {
        $this->getLogger()->info(" Not implemented.");
        
        $this->getLogger()->info(" Fin du processus.");
        $this->setAlive(false);
    }

    // PERSONNE
    /**
     *
     * @return void|\Doctrine\ORM\EntityRepository
     */
    public function getElementPersonnesRepository($entityManager)
    {
        try {
            $elementPersonnesRepository = $entityManager->getRepository('Jobs\Model\Entity\Personne');
        } catch (Exception $e) {
            $this->setAlive(false);
            $this->getLogger()->err($e->getMessage());
            
            return;
        }
        return $elementPersonnesRepository;
    }

    // DOSSIER
    /**
     *
     * @return void|\Doctrine\ORM\EntityRepository
     */
    public function getElementDossierRepository($entityManager)
    {
        try {
            $elementDossierRepository = $entityManager->getRepository('Jobs\Model\Entity\DossierEtudiant');
        } catch (Exception $e) {
            $this->setAlive(false);
            $this->getLogger()->err($e->getMessage());
            
            return;
        }
        return $elementDossierRepository;
    }

    // PARENT
    /**
     *
     * @return void|\Doctrine\ORM\EntityRepository
     */
    public function getElementParentRepository($entityManager)
    {
        try {
            $elementParentRepository = $entityManager->getRepository('Jobs\Model\Entity\ParentEtudiant');
        } catch (Exception $e) {
            $this->setAlive(false);
            $this->getLogger()->err($e->getMessage());
            
            return;
        }
        return $elementParentRepository;
    }

    /**
     *
     * @return boolean|\Minibus\Model\Process\DataTransfer\PDO
     */
    public function getPreinscriptionConnexion()
    {
        try {
            
            $pdo = $this->getEndPointConnection();
        } catch (\Exception $e) {
            $message = "Un problème est survenu lors de la connexion à la base de données Preinscription : " . $e->getMessage();
            $this->setAlive(false);
            self::alertWarn($message);
            $this->getLogger()->err($e->getMessage());
            
            return;
        }
        return $pdo;
    }

    // Insert idexterne dans personne pour un étudiant première année
    public function insertPreetu($em, $idexterne, $login, $civilite, $sexe, $sitMaritale, $nom, $prenom1, $prenomautre, $bpublinommari, $codenationalite, $codedblnationalite, $codepaysnaiss, /*$datenaiss,*/ $iddeptnaiss, $nbenfant, $villenaiss, $telephonefixe, $telephonemobile, $pays, $codePostal, $localite, $mail, $adresse1, $bpubliphoto, $numsecu)
    {
        $preEtu = new \Jobs\Model\Entity\Personne();
        $preEtu->setIdExterne($idexterne);
        $preEtu->setCivilite($civilite);
        $preEtu->setLogin($login);
        $preEtu->setSexe($sexe);
        $preEtu->setSitMaritale($sitMaritale);
        $preEtu->setNom($nom);
        $preEtu->setPrenom($prenom1);
        $preEtu->setPrenomAutre($prenomautre);
        $preEtu->setbPubliNomMari($bpublinommari);
        $preEtu->setCodenationnalite($codenationalite);
        $preEtu->setCodedblNationalite($codedblnationalite);
        $preEtu->setCodePaysNaiss($codepaysnaiss);
        // $preEtu->setDateNaiss( $datenaiss);
        $preEtu->setIdDeptNaiss($iddeptnaiss);
        $preEtu->setNbEnfants($nbenfant);
        $preEtu->setVillenaiss($villenaiss);
        $preEtu->setTelephoneFixe($telephonefixe);
        $preEtu->setTelephoneMobile($telephonemobile);
        $preEtu->setCodePostal($codePostal);
        $preEtu->setLocalite($localite);
        $preEtu->setEmail($mail);
        $preEtu->setPays($pays);
        $preEtu->setAdresse1($adresse1);
        $preEtu->setBpubliphotointranet($bpubliphoto);
        $preEtu->setNumSecu($numsecu);
        // $preEtu->setEmailetb( $emailtab);
        $em->persist($preEtu);

//         $conn = $em->getConnection();
//         $this->getLogger()->info("LastId".$conn->lastInsertId());
        
        $em->flush();
        
        $this->getLogger()->info("LastId : ".$preEtu->getId());
       

        
        return $preEtu->getId();
    }

    
    public function insertDossieretu($am, $professionPere, $professionMere, $ine, $matricule, $seriebacetu, $anneebacetu, $uneAcademie)
    {
        $preEtu = new \Jobs\Model\Entity\DossierEtudiant();
        
        $preEtu->setCsppere($professionPere);
        $preEtu->setCspmere($professionMere);
        $preEtu->setIne($ine);
        $preEtu->setMatricule($matricule);
        $preEtu->setSeriebac($seriebacetu);
        $preEtu->setAnneebac($anneebacetu);
        $preEtu->setAcademiebac($uneAcademie);
        $am->persist($preEtu);
        $am->flush();
        
        return;
    } 

    public function insertParentetu($im, $idPers, $nomParent, $prenomParent, $emailParent, $telephonefixeParent, $telephonemobileParent, $codepostalParent, $localiteParent, $paysParent, $adresse1Parent, $professionParent, $lienparente)
    {
        $preEtu = new \Jobs\Model\Entity\ParentEtudiant();
        //$preEtu->setEtudiant($idetudiantPED);
        $preEtu->setEtudiant($idPers);
        $preEtu->setNom($nomParent);
        $preEtu->setPrenom($prenomParent);
        $preEtu->setEmail($emailParent);
        $preEtu->setTelephonefixe($telephonefixeParent);
        $preEtu->setTelephonemobile($telephonemobileParent);
        $preEtu->setCodepostal($codepostalParent);
        $preEtu->setLocalite($localiteParent);
        $preEtu->setPays($paysParent);
        $preEtu->setAdresse1($adresse1Parent);
        $preEtu->setProfession($professionParent);
        $preEtu->setLienparente($lienparente);
        $im->persist($preEtu);
        $im->flush();
        
        return;
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

   public function convertIdExterne($id)
    {
        return  $id;
    }
    
  public function findIdETu ($idExterne){
       
        $etudiantPED = $elementPersonnesRepository->findBy(array(
            'idexterne' => $idexterne
        ));
        if ($etudiantPED){
        return $etudiantPED->getId();  
    }
    else {
        return null;
    }
    }
}
