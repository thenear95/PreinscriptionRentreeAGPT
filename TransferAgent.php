<?php
namespace Jobs\Model\Process\DataTransfer\Acquisition\Rentree\Etudiants\Preinscription;

use Minibus\Model\Process\DataTransfer\Export\AbstractDataExportAgent;
use Minibus\Model\Entity\Execution;
use Jobs\Model\Entity\Personne;
use Jobs\Model\Entity\DossierEtudiant;
use Doctrine\DBAL\Driver\PDOException;
use Jobs\Model\Entity\AttributionPersonne;
use Jobs\Model\Process\DataTransfer\Export\Personnes\Commun\Ent\AbstractEntPersonneExportAgent;
use Jobs\Model\Entity\Etudiant;
use Jobs\Model\Entity\ParentEtudiant;
use Jobs\Model\Entity\InsAdminEtudiant;

class TransferAgent extends AbstractEntPersonneExportAgent
{
    
    protected $hashCalculator;
    
    const UAIAgro = '0753465J';
    
    // protected $elementPersonnesRepository;
    
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
        
        $ym = $this->getEntityManager();
        $elementInsAdminEtuRepository = $this->getElementInsAdminEtuRepository($ym);
        
        $this->getLogger()->info("Début acquisition étudiants depuis Préinscription");
        $pdo = $this->getPreinscriptionConnexion();
        
        $hashCalculator = $this->getServiceLocator()->get('hash-calculator');
        
        if (false === $pdo) {
            $this->setAlive(false);
            return;
        }
        
        $connectionParams = $this->getConnectionParameters();
        
        // Récupération des étudiants depuis Préinscription
        $preinscriptionLoader = new PreinscriptionLoader($pdo);
        try {
            
            $tabEtudiants = $preinscriptionLoader->getAllEtudiant();
            
            foreach ($tabEtudiants as $tabEtudiant) {
                // $this->getLogger()->info($tabEtudiant['id_etudiant'] . " : " . $tabEtudiant['Nom']);
            }
        } catch (\Exception $e) {
            $message = "Un problème est survenu lors de la récupération des personnes dans base de données Rentrée : " . $e->getMessage();
            $this->setAlive(false);
            self::alertError($message);
            $this->getLogger()->err($e->getMessage());
            
            return;
        }
        
        $nbEtudiants = count($tabEtudiants);
        $this->getLogger()->info("Nombre total d'etudiant : " . $nbEtudiants ." Début du processus");
        
        $nbPersonnesMAJ = 0;
        $nbPersonnesNonRecuperees = 0;
        
        $i = 1;
        
//---------------------- PERSONNE ----------------------------------------------------------
        // Pour chaque étudiant
        foreach ($tabEtudiants as $tabEtudiant) {
            
            $hashCalculator = $this->getServiceLocator()->get('hash-calculator');
            
            //Id dans la base d'origine pour récupération des données dans les objets liés
            $id_etudiant = $tabEtudiant['id_etudiant'];
            $login = $tabEtudiant['login_ldap'];
            $updated = 0;
            $idCandidatPCL = $tabEtudiant['id_candidat_PCL'];
            $sexe = $tabEtudiant['sexe'];
            $sitMaritaleEtu = $tabEtudiant['id_situation_familiale'];
            
            $idEtudiantPreinscription = $tabEtudiant['id_etudiant'];
            
            
            if ($idCandidatPCL == null)
            {
                $idexterne = "preetu" . $idEtudiantPreinscription;
            }
            else
            {
                $idexterne = "preetu" . $idCandidatPCL;
            }
            
            //Vérification que l'étudiant existe sur idexterne. si Existe Update sinon Insert
            //Tente de récupérer un Etudiant sur son idExerne
            $etudiant = $em->getRepository('Jobs\Model\Entity\Etudiant')->findOneBy(array(
                'idexterne' => $idexterne
            ));
            $modeUpdate = true;
            if (!isset($etudiant)){
                //Création de l'étudiant pour PED
                $etudiant = new Etudiant();
                $modeUpdate = false;
            }
            
            $etudiant->setIdExterne($idexterne);
            $etudiant->setLogin($tabEtudiant['login_ldap']);
            $etudiant->setSexe($tabEtudiant['sexe']);
            
            $hashEtudiant = array(
                $tabEtudiant
            );
            
            //$this->getLogger()->info("Calcul du hash");
            
            $hash = $hashCalculator->getHash($hashEtudiant);
            $etudiant->setHash($hash);
            
            // if ($hashEtudiant != $hash){
            
            // $this->getLogger()->info("Mise à jour");
            // 
//             }

            // else 
            //    {
            //        $this->getLogger()->info("Insert");
              //      $etudiant = new Etudiant();
              //  }
            
            //$etudiant->setDatenaiss($tabEtudiant['date_naissance']);
            
            // Civilité par rapport au sexe
            $civilite = "Mme";
            if ($sexe == 'H') {
                $civilite = "M";
            }
            $etudiant->setCivilite($civilite);
            
            $etudiant->setNom($tabEtudiant['Nom']);
            
            //Prenom 1 peut contenir plusieurs prénoms, on veut récupérer le premier
            $prenomP = explode(",", $tabEtudiant['Prenom1']);
            $prenom1 = $this->encodeIfNonUTF8($prenomP[0]);
            $etudiant->setPrenom($prenom1);
            
           $prenom2 = $tabEtudiant['Prenom2'];
           // On parcours le tableau explosé de prenomP pour avoir chaque valeur pour concatener le prenom2 avec la suite des Prenoms1
           $prenomAutre = $prenom2;
           unset($prenomP[0]);
           foreach ($prenomP as $prenomA) {
               $prenomAutre .= ','.$prenomA;
            }
            $etudiant->setPrenomAutre($prenomAutre);
            
            $sitMaritale = "Non indiqué";
            if ($sitMaritaleEtu == 1) {
                $sitMaritale = "Célibataire";
            }elseif ($sitMaritaleEtu == 2) {
                $sitMaritale = "Marié(e)";
            }
            $etudiant->setSitMaritale($sitMaritale);
            
            $etudiant->setbPubliNomMari(1);
            $etudiant->setCodenationnalite($tabEtudiant['id_nationalite1']);
            $etudiant->setCodedblNationalite($tabEtudiant['id_nationalite2']);
            $etudiant->setCodePaysNaiss($tabEtudiant['id_pays_naissance']);
            $etudiant->setIdDeptNaiss($tabEtudiant['id_departement_naiss']);
            $etudiant->setNbEnfants($tabEtudiant['enfants']);
            $etudiant->setVillenaiss($tabEtudiant['ville_naissance']);
            $etudiant->setTelephoneFixe($tabEtudiant['telephone']);
            $etudiant->setTelephoneMobile($tabEtudiant['mobile']);
            $etudiant->setEmail($tabEtudiant['mel']);
            
            $loginPourEmail = $tabEtudiant['login_ldap'];
            $emailETB = $loginPourEmail ."@agroparistech.fr";
            $etudiant->setEmailetb($emailETB);
            
            
            $etudiant->setPays(1);
            $etudiant->setBpubliphotointranet($tabEtudiant['photo_valide']);
            $etudiant->setNumSecu($tabEtudiant['N_Securite_Soc']);
            
            $tabAdresses = $preinscriptionLoader->getAdresse($id_etudiant);
            foreach ($tabAdresses as $tabAdresse) {
                $adresse1 = $tabAdresse['rue'];
                $localite = $tabAdresse['ville'];
                $codePostal = $tabAdresse['codeP'];
            }
            $etudiant->setAdresse1($adresse1);
            $etudiant->setLocalite($localite);
            $etudiant->setCodepostal($codePostal);

            $em->persist($etudiant);
            $em->flush();
            
            $this->getLogger ()->info ( "etudiant :".$id_etudiant);
 // ------------------------------- DOSSIER ----------------------------------------------------
            
            $dossier = null;
            if ($modeUpdate){
                //récupère le dossier existant
                $dossier = $etudiant->getDossierEtudiant();
            }
            if (!isset($dossier)){
                // Création du dossier pour PED
                $dossier = new DossierEtudiant();
                $dossier->setEtudiant($etudiant);
            }
            
            $tabInfosparents = $preinscriptionLoader->getInfosParentEtu($id_etudiant);
            $professionPere = '';
            $professionMere = '';
            
            foreach ($tabInfosparents as $tabInfoparent) {
                $id_lien_parente = $tabInfoparent['id_lien_parente'];
                
                $tabCspparents = $preinscriptionLoader->getCspparent($id_etudiant, $id_lien_parente);
                foreach ($tabCspparents as $tabCspparent) {
                    if ($id_lien_parente == 1) {
                        $professionPere = $tabCspparent['libelle'];
                    }
                    else {
                        $professionMere = $tabCspparent['libelle'];
                    }
                }
            }
            $dossier->setCsppere($professionPere);
            $dossier->setCspmere($professionMere);
            
            $dossier->setIne($tabEtudiant['INE']);
            $dossier->setMatricule($tabEtudiant['login_ldap']);
            
            $tabIds_bacs_etu = $preinscriptionLoader->getAllInfosBac($id_etudiant);
            foreach ($tabIds_bacs_etu as $tabId_bac_etu)
            {
                $id_bac = $tabId_bac_etu['id_bac'];
            }
            
            $tabSeriesbac = $preinscriptionLoader->getSeriebac($id_etudiant, $id_bac);
            $seriebacetu = '';
            foreach ($tabSeriesbac as $tabSeriebac)
            {
                $seriebacetu = $tabSeriebac['libelle'];
                $anneebacetu = $tabSeriebac['annee_bac'];
                $id_academie = $tabSeriebac['id_academie'];
            }
            $dossier->setSeriebac($seriebacetu);
            $dossier->setAnneebac($anneebacetu);
            
            $tabAcademies = $preinscriptionLoader->getAcademies($id_etudiant, $id_bac);
            foreach ($tabAcademies as $tabAcademie)
            {
                $uneAcademie = $tabAcademie['libelle'];
            }
            $dossier->setAcademiebac($uneAcademie);
            
            $tabDiplomes = $preinscriptionLoader->getDiplomes($id_etudiant);
            foreach ($tabDiplomes as $tabDiplome) {
                $unDiplome = $tabDiplome['libelle'];
            }
            $dossier->setTypediplome($unDiplome);
            
            $tabEtablissements = $preinscriptionLoader->getEtablissement($id_etudiant);
            $unEtablissement = "";
            foreach ($tabEtablissements as $tabEtablissement) {
                
                if ($tabEtablissement['id_etabliss_Arvus'] == 999999) {
                    $unEtablissement = $tabEtablissement['autre_lycee'];
                } else
                    $unEtablissement = $tabEtablissement['libelle'];
            }
            $dossier->setEtablissement($unEtablissement);
            
            $tabLycees = $preinscriptionLoader->getEtablissement($id_etudiant);
            $unLycee = "";
            foreach ($tabLycees as $tabLycee) {
                
                if ($tabLycee['id_etabliss_Arvus'] == 999999)
                {
                    $unLycee = $tabLycee['autre_lycee'];
                } else
                    $unLycee = "";
            }
            $dossier->setLycee($unLycee);
            
            $tabDerniersDiplomes = $preinscriptionLoader->getLibelleDiplomes($id_etudiant);
            $intituleDernDiplome = '';
            $annedernierDiplome = '';
            foreach ($tabDerniersDiplomes as $tabDernierDiplome)
            {
                $intituleDernDiplome = $tabDernierDiplome['intitule'];
                $annedernierDiplome = $tabDernierDiplome['delivre'];
            }
            
            $dossier->setIntituleDernDiplome($intituleDernDiplome);
            $dossier->setAnneeDernDiplome($annedernierDiplome);
            
            $tabConcours = $preinscriptionLoader->getConcours($id_etudiant);
            foreach ($tabConcours as $tabConcour)
            {
                $Unconcours = $tabConcour['libelle'];
                $idConcours = $tabConcour['id_concours'];
            }
            $dossier->setConcours($Unconcours);
            
            //$this->getLogger()->info("IDEXT : " . $idexterne);
            //$personnePourEtudiant = $this->getPersonneByIdExterne($idexterne);
            
            $am->persist($dossier);
            $am->flush();
            
            //------------------------------PARENT---------------------------------------------------------
            $parent = new ParentEtudiant();
            $tabParents = $preinscriptionLoader->getInfosParentEtu($id_etudiant);
            
            foreach ($tabParents as $tabParent)
            {
                
                $parent->setEtudiant($etudiant->getId());
                $parent->setNom($tabParent['nom']);
                $parent->setPrenom($tabParent['prenom']);
                $parent->setEmail($tabParent['mel']);
                $parent->setTelephonefixe($tabParent['telephone']);
                $parent->setTelephonemobile($tabParent['mobile']);
                $parent->setCodepostal($tabParent['codeP']);
                $parent->setLocalite($tabParent['ville']);
                $parent->setPays($tabParent['id_pays']);
                $parent->setAdresse1($tabParent['adresse']);
                $parent->setProfession($tabParent['profession']);
                
                $idLienParent = $tabParent['id_lien_parente'];
                
                $lienparente = "mere";
                if ($idLienParent == 1) {
                    $lienparente = "pere";
                }
                
                $parent->setLienparente($lienparente);
            }
            
            $im->persist($parent);
            $im->flush();
            
            //---------------------------------INSADMINETU---------------------------------------------------            
            $insAdminEtu = new InsAdminEtudiant();
            $insAdminEtu->setDossier($dossier);

            $insAdminEtu->setTypeinscription('Principale');
            
            if ($idCandidatPCL == null) {
                $voieentree = $Unconcours;
                $cursus = 'Ingénieur';
                $diplomeinvariable = 'ING';
                $niveau = '1A';
            }
            else {
                $voieentree = '';
                $cursus = 'M1/M2';
                $diplomeinvariable = 'M1/M2';
                $niveau = 'M1/M2';
            }
            
            $insAdminEtu->setVoieentree($voieentree);
            $insAdminEtu->setCursus($cursus);
            
            $statutscolarite = 'En scolarité';
            $insAdminEtu->setStatutscolarite($statutscolarite);
            
            $insAdminEtu->setDiplomeinvariable($diplomeinvariable);
            
            $insAdminEtu->setNiveau($niveau);
            
            $date = date("y");
            $promoorig = $date;
            $insAdminEtu->setPromoorig($promoorig);
            
            $promorattach1 = $promoorig;
            $insAdminEtu->setPromorattach1($promorattach1);
            
            $tabObservations = $preinscriptionLoader->getOberservations($id_etudiant);
            $uneObservation = '';
            foreach ($tabObservations as $tabObservation) {
                $uneObservation = $tabObservation['info'];
            }
            
            $insAdminEtu->setObservations($uneObservation);
            
            $regimeinscription = 'Formation initiale';
            $insAdminEtu->setRegimeinscription($regimeinscription);
            
            $tabLibellesBourses = $preinscriptionLoader->getBourse($id_etudiant);
            $unLibelleBourse = '';
            foreach ($tabLibellesBourses as $tabLibelleBourse) {
                $unLibelleBourse = $tabLibelleBourse['libelle'];
            }
            
            $insAdminEtu->setBourse($unLibelleBourse);
            
            $id_profil = $preinscriptionLoader->getProfil($id_etudiant);
            
            
            $situation = 'Préparation';
            $insAdminEtu->setSituation($situation);
            
            $ym->persist($insAdminEtu);
            $ym->flush();
        }
        
        // --------------------------------- FIN ------------------------------------------------------
        
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
    
    // INSADMINETU
    /**
     *
     * @return void|\Doctrine\ORM\EntityRepository
     */
    public function getElementInsAdminEtuRepository($entityManager)
    {
        try {
            $elementInsAdminEtuRepository = $entityManager->getRepository('Jobs\Model\Entity\InsAdminEtudiant');
        } catch (Exception $e) {
            $this->setAlive(false);
            $this->getLogger()->err($e->getMessage());
            
            return;
        }
        return $elementInsAdminEtuRepository;
    }
    
    /**
     *
     * @return boolean|Minibus\Model\Process\DataTransfer\EndPointConnection
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
    
    /**
     *
     * @return Personne
     */
    protected function getPersonneByIdExterne($personnelIdentifier)
    {
        error_log("getPersonneByIdExterne " . $personnelIdentifier);
        $tabEtudiantTest = $this->getElementPersonnesRepository($this->getEntityManager())
        ->findOneBy(array(
            'idexterne' => $personnelIdentifier
        ));
        
        return $tabEtudiantTest;
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
        return $id;
    }
    
    public function findIdETu($idExterne)
    {
        $tabEtudiantPED = $elementPersonnesRepository->findBy(array(
            'idexterne' => $idexterne
        ));
        if ($tabEtudiantPED) {
            return $tabEtudiantPED->getId();
        } else {
            return null;
        }
    }
}
