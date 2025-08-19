<?php
// app/Services/AccuseReceptionService.php - MISE À JOUR avec sexe et situation matrimoniale

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Reclamation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AccuseReceptionService
{
    private $dompdf;

    public function __construct()
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', true);
        
        $this->dompdf = new Dompdf($options);
    }

    /**
     * ✅ NOUVELLE FONCTION : Obtenir la civilité selon le sexe et la situation matrimoniale
     */
    private function getCivilite($user)
    {
        $sexe = $user->sexe ?? '';
        $situationMatrimoniale = $user->situation_matrimoniale ?? '';
        
        // Si on a le sexe, on utilise la logique de civilité
        if (strtoupper($sexe) === 'M' || strtoupper($sexe) === 'MASCULIN') {
            return 'M.';
        } elseif (strtoupper($sexe) === 'F' || strtoupper($sexe) === 'FEMININ') {
            // Pour les femmes, on regarde la situation matrimoniale
            if (in_array(strtoupper($situationMatrimoniale), ['MARIEE', 'MARIE', 'MARIÉ', 'MARIÉE'])) {
                return 'Mme';
            } else {
                return 'Mlle';
            }
        }
        
        // Fallback si pas d'info
        return '';
    }

    /**
     * ✅ NOUVELLE FONCTION : Formater le nom complet avec civilité
     */
    private function getIdentiteComplete($user)
    {
        $civilite = $this->getCivilite($user);
        $nomComplet = trim($user->prenoms . ' ' . $user->nom);
        
        return $civilite ? $civilite . ' ' . $nomComplet : $nomComplet;
    }

    /**
     * ✅ NOUVELLE FONCTION : Obtenir le libellé de la situation matrimoniale
     */
    private function getSituationMatrimonialeLibelle($situationMatrimoniale)
    {
        $situations = [
            'celibataire' => 'Célibataire',
            'marie' => 'Marié(e)',
            'mariee' => 'Mariée',
            'divorce' => 'Divorcé(e)',
            'divorcee' => 'Divorcée',
            'veuf' => 'Veuf/Veuve',
            'veuve' => 'Veuve',
            'concubinage' => 'En concubinage',
            'separe' => 'Séparé(e)',
            'separee' => 'Séparée'
        ];
        
        $key = strtolower($situationMatrimoniale ?? '');
        return $situations[$key] ?? ucfirst($situationMatrimoniale ?? 'Non spécifiée');
    }

    /**
     * Générer l'accusé de réception PDF
     */
    public function genererAccuseReception(Reclamation $reclamation, $user)
    {
        try {
            Log::info('📄 Génération accusé de réception:', [
                'reclamation_id' => $reclamation->id,
                'numero' => $reclamation->numero_reclamation,
                'user_id' => $user->id
            ]);

            // Générer le contenu HTML
            $html = $this->genererHTML($reclamation, $user);
            
            // Configurer DomPDF
            $this->dompdf->loadHtml($html);
            $this->dompdf->setPaper('A4', 'portrait');
            $this->dompdf->render();
            
            // Obtenir le contenu PDF
            $pdfContent = $this->dompdf->output();
            
            Log::info('✅ Accusé de réception généré avec succès', [
                'taille_pdf' => strlen($pdfContent),
                'numero_reclamation' => $reclamation->numero_reclamation
            ]);
            
            return $pdfContent;
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur génération accusé de réception:', [
                'reclamation_id' => $reclamation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Sauvegarder l'accusé de réception
     */
    public function sauvegarderAccuse(Reclamation $reclamation, $user)
    {
        try {
            $pdfContent = $this->genererAccuseReception($reclamation, $user);
            
            // Chemin de stockage
            $filename = "accuse_reception_{$reclamation->numero_reclamation}.pdf";
            $path = "accuses_reception/{$user->id}/{$filename}";
            
            // Sauvegarder le fichier
            Storage::disk('public')->put($path, $pdfContent);
            
            Log::info('💾 Accusé de réception sauvegardé:', [
                'path' => $path,
                'reclamation' => $reclamation->numero_reclamation
            ]);
            
            return [
                'path' => $path,
                'filename' => $filename,
                'url' => Storage::url($path)
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur sauvegarde accusé:', [
                'reclamation_id' => $reclamation->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Télécharger l'accusé de réception
     */
    public function telechargerAccuse(Reclamation $reclamation, $user)
    {
        try {
            $pdfContent = $this->genererAccuseReception($reclamation, $user);
            $filename = "Accuse_Reception_{$reclamation->numero_reclamation}.pdf";
            
            return [
                'content' => $pdfContent,
                'filename' => $filename,
                'mime_type' => 'application/pdf'
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur téléchargement accusé:', [
                'reclamation_id' => $reclamation->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * ✅ MISE À JOUR : Générer le contenu HTML de l'accusé avec sexe et situation matrimoniale
     */
    private function genererHTML(Reclamation $reclamation, $user)
    {
        // Chemin du logo
        $logoPath = public_path('images/cppf.png');
        $logoBase64 = '';
        
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoBase64 = 'data:image/png;base64,' . $logoData;
        }

        // ✅ NOUVEAU : Informations utilisateur avec civilité et situation matrimoniale
        $infoUtilisateur = $this->getInfoUtilisateur($user, $reclamation->user_type);
        $identiteComplete = $this->getIdentiteComplete($user);
        
        // Informations de la réclamation
        $typeInfo = $reclamation->type_reclamation_info;
        $dateFormatee = $reclamation->date_soumission->format('d/m/Y à H:i');
        $prioriteInfo = $reclamation->priorite_info;
        
        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Accusé de Réception - {$reclamation->numero_reclamation}</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Arial', sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #333;
                    background: #fff;
                }
                
                .container {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                }
                
                .header {
                    border-bottom: 3px solid #1E3A8A;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                    position: relative;
                }
                
                .logo {
                    position: absolute;
                    top: 0;
                    left: 0;
                    max-width: 100px;
                    height: auto;
                }
                
                .header-content {
                    margin-left: 120px;
                    text-align: center;
                }
                
                .header h1 {
                    color: #1E3A8A;
                    font-size: 24px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                
                .header h2 {
                    color: #374151;
                    font-size: 18px;
                    font-weight: normal;
                    margin-bottom: 10px;
                }
                
                .header p {
                    color: #6B7280;
                    font-size: 11px;
                }
                
                .accuse-title {
                    background: linear-gradient(135deg, #1E3A8A, #3B82F6);
                    color: white;
                    text-align: center;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 20px 0;
                    font-size: 16px;
                    font-weight: bold;
                }
                
                .info-section {
                    background: #F8FAFC;
                    border: 1px solid #E2E8F0;
                    border-radius: 8px;
                    padding: 15px;
                    margin: 15px 0;
                }
                
                .info-section h3 {
                    color: #1E3A8A;
                    font-size: 14px;
                    font-weight: bold;
                    margin-bottom: 10px;
                    border-bottom: 1px solid #CBD5E0;
                    padding-bottom: 5px;
                }
                
                .info-grid {
                    display: table;
                    width: 100%;
                    margin-top: 10px;
                }
                
                .info-row {
                    display: table-row;
                }
                
                .info-label {
                    display: table-cell;
                    font-weight: bold;
                    color: #374151;
                    width: 30%;
                    padding: 5px 10px 5px 0;
                    vertical-align: top;
                }
                
                .info-value {
                    display: table-cell;
                    color: #1F2937;
                    padding: 5px 0;
                    vertical-align: top;
                }
                
                .description-box {
                    background: white;
                    border: 1px solid #D1D5DB;
                    border-radius: 6px;
                    padding: 12px;
                    margin: 10px 0;
                    white-space: pre-wrap;
                    font-family: 'Arial', sans-serif;
                    line-height: 1.5;
                }
                
                .documents-list {
                    margin: 10px 0;
                }
                
                .document-item {
                    background: white;
                    border: 1px solid #D1D5DB;
                    border-radius: 4px;
                    padding: 8px 12px;
                    margin: 5px 0;
                    display: table;
                    width: 100%;
                }
                
                .document-icon {
                    display: table-cell;
                    width: 20px;
                    color: #6B7280;
                    vertical-align: middle;
                }
                
                .document-info {
                    display: table-cell;
                    vertical-align: middle;
                    padding-left: 10px;
                }
                
                .badge {
                    display: inline-block;
                    padding: 3px 8px;
                    border-radius: 12px;
                    font-size: 10px;
                    font-weight: bold;
                    color: white;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .badge-priorite {
                    background-color: {$prioriteInfo['couleur']};
                }
                
                .badge-statut {
                    background-color: #F59E0B;
                }
                
                .footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 2px solid #E5E7EB;
                    text-align: center;
                }
                
                .footer p {
                    color: #6B7280;
                    font-size: 10px;
                    margin: 5px 0;
                }
                
                .important-notice {
                    background: #FEF3C7;
                    border: 1px solid #F59E0B;
                    border-radius: 6px;
                    padding: 12px;
                    margin: 20px 0;
                    color: #92400E;
                }
                
                .important-notice strong {
                    color: #78350F;
                }
                
                .timestamp {
                    text-align: right;
                    color: #6B7280;
                    font-size: 10px;
                    margin-top: 20px;
                    font-style: italic;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <!-- En-tête -->
                <div class='header'>
                    " . ($logoBase64 ? "<img src='{$logoBase64}' alt='Logo CPPF' class='logo'>" : "") . "
                    <div class='header-content'>
                        <h1>CAISSE DES PENSIONS ET DES PRESTATIONS FAMILIALES DES AGENTS DE L'ETAT</h1>
                        <h2>e-Services - Gestion des Réclamations</h2>
                        <p>République Gabonaise -  Union - Travail - Justice</p>
                    </div>
                </div>
                
                <!-- Titre de l'accusé -->
                <div class='accuse-title'>
                    ACCUSÉ DE RÉCEPTION DE RÉCLAMATION
                </div>
                
                <!-- Informations de la réclamation -->
                <div class='info-section'>
                    <h3>Informations de la réclamation</h3>
                    <div class='info-grid'>
                        <div class='info-row'>
                            <div class='info-label'>Numéro de réclamation :</div>
                            <div class='info-value'><strong>{$reclamation->numero_reclamation}</strong></div>
                        </div>
                        <div class='info-row'>
                            <div class='info-label'>Type de réclamation :</div>
                            <div class='info-value'>{$typeInfo['nom']}</div>
                        </div>
                        " . ($reclamation->sujet_personnalise ? "
                        <div class='info-row'>
                            <div class='info-label'>Sujet :</div>
                            <div class='info-value'>{$reclamation->sujet_personnalise}</div>
                        </div>
                        " : "") . "
                        <div class='info-row'>
                            <div class='info-label'>Date de soumission :</div>
                            <div class='info-value'>{$dateFormatee}</div>
                        </div>
                        <div class='info-row'>
                            <div class='info-label'>Priorité :</div>
                            <div class='info-value'>
                                <span class='badge badge-priorite'>{$prioriteInfo['nom']}</span>
                            </div>
                        </div>
                        <div class='info-row'>
                            <div class='info-label'>Statut actuel :</div>
                            <div class='info-value'>
                                <span class='badge badge-statut'>En attente</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ✅ NOUVEAU : Informations du demandeur avec civilité et situation matrimoniale -->
                <div class='info-section'>
                    <h3>Informations du demandeur</h3>
                    <div class='info-grid'>
                        <div class='info-row'>
                            <div class='info-label'>Identité complète :</div>
                            <div class='info-value'><strong>{$identiteComplete}</strong></div>
                        </div>
                        <div class='info-row'>
                            <div class='info-label'>Email :</div>
                            <div class='info-value'>{$user->email}</div>
                        </div>
                        " . ($user->telephone ? "
                        <div class='info-row'>
                            <div class='info-label'>Téléphone :</div>
                            <div class='info-value'>{$user->telephone}</div>
                        </div>
                        " : "") . "
                        " . ($user->sexe ? "
                        <div class='info-row'>
                            <div class='info-label'>Sexe :</div>
                            <div class='info-value'>" . (strtoupper($user->sexe) === 'M' || strtoupper($user->sexe) === 'MASCULIN' ? 'Masculin' : 'Féminin') . "</div>
                        </div>
                        " : "") . "
                        " . ($user->situation_matrimoniale ? "
                        <div class='info-row'>
                            <div class='info-label'>Situation matrimoniale :</div>
                            <div class='info-value'>" . $this->getSituationMatrimonialeLibelle($user->situation_matrimoniale) . "</div>
                        </div>
                        " : "") . "
                        <div class='info-row'>
                            <div class='info-label'>Type de compte :</div>
                            <div class='info-value'>{$infoUtilisateur['type_compte']}</div>
                        </div>
                        " . $infoUtilisateur['champs_specifiques'] . "
                    </div>
                </div>
                
                <!-- Description -->
                <div class='info-section'>
                    <h3>Description du problème</h3>
                    <div class='description-box'>{$reclamation->description}</div>
                </div>
                
                " . ($reclamation->documents && count($reclamation->documents) > 0 ? "
                <!-- Documents joints -->
                <div class='info-section'>
                    <h3>Documents joints (" . count($reclamation->documents) . " fichier(s))</h3>
                    <div class='documents-list'>
                        " . $this->genererListeDocuments($reclamation->documents) . "
                    </div>
                </div>
                " : "") . "
                
                <!-- Notice importante -->
                <div class='important-notice'>
                    <strong>Important :</strong><br>
                    • Votre réclamation a été enregistrée avec succès dans notre système.<br>
                    • Un accusé de traitement vous sera envoyé dès qu'un agent prendra en charge votre dossier.<br>
                    • Vous pouvez suivre l'évolution de votre réclamation en vous connectant à votre espace e-Services.<br>
                    • Conservez précieusement ce numéro de réclamation : <strong>{$reclamation->numero_reclamation}</strong>
                </div>
                
                <!-- Pied de page -->
                <div class='footer'>
                    <p><strong>CAISSE DES PENSIONS ET DES PRESTATIONS FAMILIALES DES AGENTS DE L'ETAT</strong></p>
                    <p>Service e-Services | Email: contact@cppf.ga | Tél: +241 01 XX XX XX</p>
                    <p>Siège social : Libreville, Gabon</p>
                </div>
                
                <!-- Timestamp -->
                <div class='timestamp'>
                    Document généré automatiquement le " . now()->format('d/m/Y à H:i:s') . "
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * ✅ MISE À JOUR : Obtenir les informations utilisateur selon le type avec champs étendus
     */
    private function getInfoUtilisateur($user, $userType)
    {
        if ($userType === 'agent') {
            return [
                'type_compte' => 'Agent actif',
                'champs_specifiques' => "
                    <div class='info-row'>
                        <div class='info-label'>Matricule :</div>
                        <div class='info-value'>" . ($user->matricule_solde ?? 'Non spécifié') . "</div>
                    </div>
                    <div class='info-row'>
                        <div class='info-label'>Poste :</div>
                        <div class='info-value'>" . ($user->poste ?? 'Non spécifié') . "</div>
                    </div>
                    <div class='info-row'>
                        <div class='info-label'>Direction :</div>
                        <div class='info-value'>" . ($user->direction ?? 'Non spécifiée') . "</div>
                    </div>
                "
            ];
        } else {
            return [
                'type_compte' => 'Retraité',
                'champs_specifiques' => "
                    <div class='info-row'>
                        <div class='info-label'>N° Pension :</div>
                        <div class='info-value'>" . ($user->numero_pension ?? 'Non spécifié') . "</div>
                    </div>
                "
            ];
        }
    }

    /**
     * Générer la liste des documents
     */
    private function genererListeDocuments($documents)
    {
        $html = '';
        
        foreach ($documents as $index => $document) {
            $taille = $this->formatTaillefichier($document['taille']);
            $type = strtoupper($document['type']);
            $date = \Carbon\Carbon::parse($document['date_upload'])->format('d/m/Y H:i');
            
            $html .= "
                <div class='document-item'>
                    <div class='document-icon'>📄</div>
                    <div class='document-info'>
                        <strong>" . ($index + 1) . ". {$document['nom_original']}</strong><br>
                        <small>Type: {$type} | Taille: {$taille} | Téléchargé le: {$date}</small>
                    </div>
                </div>
            ";
        }
        
        return $html;
    }

    /**
     * Formater la taille des fichiers
     */
    private function formatTaillefichier($taille)
    {
        if ($taille < 1024) return $taille . ' B';
        if ($taille < 1024 * 1024) return round($taille / 1024, 1) . ' KB';
        return round($taille / (1024 * 1024), 1) . ' MB';
    }
}