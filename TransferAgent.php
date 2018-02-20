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
        // Pour chaque étudiant, récupération de ses candidatures dans PED
        foreach ($tabEtudiants as $tabEtudiant) {

            //Id dans la base d'origine pour récupération des données dans les objets liés
            //Doit-on set l'id_etudiant de l'ancienne base ??
            $id_etudiant = $tabEtudiant['id_etudiant'];
            $login = $tabEtudiant['login_ldap']; // supprimer ses lignes ?? car elles sont en bas en set ?
            // $datenaiss = $tabEtudiant ['date_naissance'];
            $updated = 0;
            $idCandidatPCL = $tabEtudiant['id_candidat_PCL'];
            $sexe = $tabEtudiant['sexe'];
            $sitMaritaleEtu = $tabEtudiant['id_situation_familiale'];
            
            $idEtudiantPreinscription = $tabEtudiant['id_etudiant'];
            
            //Création de l'étudiant pour PED    
            $etudiant = new Etudiant();
            
            if ($idCandidatPCL == null) 
            {
                $idexterne = "preetu" . $idEtudiantPreinscription;
            } 
            else 
            {
                $idexterne = "preetu" . $idCandidatPCL;
            }
            $etudiant->setIdExterne($idexterne);
            $etudiant->setLogin($tabEtudiant['login_ldap']);
            $etudiant->setSexe($tabEtudiant['sexe']);
            
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
            //TODO concaténer la fin de $prenomP avec Prenom2
            $etudiant->setPrenomAutre($tabEtudiant['Prenom2']);

            $sitMaritale = "Non indiqué"; // null ou 0
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
            //Pas de setMatricule dans Personne ?
            //$etudiant->setMatricule($tabEtudiant['login_ldap']);
            $etudiant->setEmail($tabEtudiant['mel']);
            $etudiant->setPays(1);
            $etudiant->setBpubliphotointranet($tabEtudiant['photo_valide']);
            $etudiant->setNumSecu($tabEtudiant['N_Securite_Soc']);
            
            //On prend la première adresse
//             $tabAdresses = $preinscriptionLoader->getAdresse($id_etudiant);
//             $etudiant->setCodePostal($tabAdresses[0]['codeP']);
//             $etudiant->setLocalite($tabAdresses[0]['ville']);
//             $etudiant->setAdresse1($tabAdresses[0]['rue']);

            $tabAdresses = $preinscriptionLoader->getAdresse($id_etudiant);
            foreach ($tabAdresses as $tabAdresse) {
                $adresse1 = $tabAdresse['rue'];
                $localite = $tabAdresse['ville'];
                $codePostal = $tabAdresse['codeP'];
                // $this->getLogger ()->info ( "CP :".$codePostal);
            }
            $etudiant->setAdresse1($adresse1);
            $etudiant->setLocalite($localite);
            $etudiant->setCodepostal($codePostal);
        
            $em->persist($etudiant);
            $em->flush();
            
// ------------------------------- DOSSIER ----------------------------------------------------
            //Création du dossier pour PED  
            $dossier = new DossierEtudiant();
            
            $dossier->setEtudiant($etudiant);
            
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
    
    
    
    /**
     * Fonction de mise à jour d'une personne dans la base de données PED.
     *
     * @param unknown $em
     *            : entity manager
     * @param array $personne
     *            : tableau regroupant les informations de la personne.
     * @param array $etudiant
     *            : tableau regroupant les informations de l'étudiant.
     * @param string $temoinValidation
     * @param string $dateValidation
     * @param string $dateAbandon
     * @param string $situationPCL
     * @param string $codeSise
     *            : code SISE du diplôme
     * @return $updated : indiquant si la personne a été mise à jour (1) ou non (0)
     */
    public function updateCandidature($em, $candidature, $etudiant, $temoinValidation, $dateValidation, $dateAbandon, $situationPCL, $codeSise,$codeEtu,$emailEtab,$anneeArrive,$mentionRof)
    {
        $updated = 0;
        
        // Si situationPCL = IA ou IA-IP
        if ($situationPCL !== 'IP') {
            $candidature->setBIaTemValide ( $temoinValidation );
            $candidature->setDIaRealisation ( $dateValidation );
            $candidature->setDIaAnnulation ( $dateAbandon );
            
            $candidature->setCIaUai ( self::UAIAgro );
            
            if ($etudiant ['valideDeve'] == 1) {
                $candidature->setCIaRegimeInscription ( $etudiant ['code_SISE_reg_ins'] );
                
                if ($temoinValidation == 'O') {
                    
                    $candidature->setCIaSise ( $codeSise );
                    
                    if ($etudiant ['INE_valid'] == 1) {
                        $candidature->setCIaIneControle ( $etudiant ['INE'] );
                    }
                }
            }
            
            $updated = 1;
            $this->setAlive ( true );
        }
        
        // Si situationPCL = IP ou IA-IP
        if ($situationPCL !== 'IA') {
            if ($temoinValidation == 'R' && $etudiant ['valideDeve'] == 1) {
                $temoinValidation = 'O';
            }
            if ($temoinValidation !== 'R')
            {
                $candidature->setBIrTemValide ( $temoinValidation );
                $candidature->setDIrRealisation ( $dateValidation );
                $candidature->setDIrAnnulation ( $dateAbandon );
                $candidature->setCIrUai ( self::UAIAgro );
                $updated = 1;
                $this->setAlive ( true );
            }
        }
        // On essaye de récupérer en base de données la candidature résultat correspondante de manière à faire un
        // update et non un insert si besoin
        $candidatureResultat = $em->getRepository('Jobs\Model\Entity\CandidatureResultat')->findOneBy(
            array(
                'id_candidature' => $candidature->getIdCandidature()
            )
            );
        
        // Cast en CandidatureResultat
        $candidatureResultat = static::toCandidatureResultat($candidature, $candidatureResultat);
        $candidatureResultat->setCEtuLocal($codeEtu);
        $candidatureResultat->setEmailEtab($emailEtab);
        $candidatureResultat->setAnneeArrive($anneeArrive);
        $candidatureResultat->setMentionRof($mentionRof);
        $em->persist ( $candidatureResultat );
        $em->flush ();
        return $updated;
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
