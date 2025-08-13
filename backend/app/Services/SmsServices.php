<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SmsServices
{
    protected $baseUrl;
    protected $config;

    public function __construct()
    {
        $this->baseUrl = config('services.sms.url');
        $this->config = config('services.sms');
    }

    public function sendVerificationCode($phoneNumber, $code)
    {
        try {
            // Formatage du numéro de téléphone
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Message de vérification
            $message = "Code de vérification e-CPPF: $code. Valide pendant 15 minutes.";
            
            // Paramètres pour l'API
            $params = [
                'client' => $this->config['client'],
                'password' => $this->config['password'],
                'phone' => $formattedPhone,
                'text' => $message,
                'from' => $this->config['from']
            ];

            // Log des paramètres envoyés (pour debugging)
            Log::info('SMS API Request', [
                'url' => $this->baseUrl,
                'params' => $params,
                'phone_original' => $phoneNumber,
                'phone_formatted' => $formattedPhone
            ]);

            // Envoi de la requête HTTP
            $response = Http::timeout(30)
                ->asForm()
                ->post($this->baseUrl, $params);

            // Log de la réponse
            Log::info('SMS API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            // Vérification du statut de la réponse
            if ($response->successful()) {
                $responseText = $response->body();
                
                // ✅ CORRECTION : Gérer la réponse XML de Wirepick
                if ($this->isXmlResponse($responseText)) {
                    return $this->parseXmlResponse($responseText);
                }
                
                // Essayer de parser en JSON si possible
                try {
                    $responseData = $response->json();
                    if ($responseData && isset($responseData['status'])) {
                        if ($responseData['status'] === 'success' || $responseData['status'] === '200') {
                            return [
                                'success' => true,
                                'message' => 'SMS envoyé avec succès',
                                'response' => $responseData
                            ];
                        } else {
                            return [
                                'success' => false,
                                'message' => $responseData['message'] ?? 'Erreur inconnue de l\'API SMS',
                                'response' => $responseData
                            ];
                        }
                    }
                } catch (Exception $e) {
                    // Si ce n'est pas du JSON valide, continuer avec le traitement texte
                    Log::info('Response is not JSON, treating as text', ['response' => $responseText]);
                }
                
                // Vérifier le texte de la réponse pour des mots-clés de succès
                if (stripos($responseText, 'success') !== false || 
                    stripos($responseText, 'sent') !== false ||
                    stripos($responseText, 'delivered') !== false ||
                    preg_match('/<id>\d+<\/id>/', $responseText)) { // Présence d'un ID XML indique succès
                    
                    return [
                        'success' => true,
                        'message' => 'SMS envoyé avec succès',
                        'response' => $responseText
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Réponse inattendue de l\'API SMS : ' . substr($responseText, 0, 200) . '...',
                    'response' => $responseText
                ];
            }

            // Gestion des erreurs HTTP
            return [
                'success' => false,
                'message' => "Erreur HTTP {$response->status()}: {$response->body()}",
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('SMS Service Error', [
                'message' => $e->getMessage(),
                'phone' => $phoneNumber,
                'code' => $code,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du SMS: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Vérifier si la réponse est du XML
     */
    private function isXmlResponse($responseText)
    {
        return strpos(trim($responseText), '<?xml') === 0 || 
               strpos($responseText, '<messages>') !== false;
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Parser la réponse XML de Wirepick
     */
    private function parseXmlResponse($xmlString)
    {
        try {
            // Nettoyer le XML
            $cleanXml = trim($xmlString);
            
            // Parser le XML
            $xml = simplexml_load_string($cleanXml);
            
            if ($xml === false) {
                return [
                    'success' => false,
                    'message' => 'Impossible de parser la réponse XML',
                    'response' => $xmlString
                ];
            }

            // Vérifier la structure XML de Wirepick
            if (isset($xml->message)) {
                $message = $xml->message;
                
                // Si on a un ID, c'est généralement un succès
                if (isset($message->id) && !empty((string)$message->id)) {
                    return [
                        'success' => true,
                        'message' => 'SMS envoyé avec succès',
                        'response' => [
                            'id' => (string)$message->id,
                            'cost' => isset($message->cost) ? (string)$message->cost : null,
                            'currency' => isset($message->currency) ? (string)$message->currency : null
                        ]
                    ];
                }
                
                // Vérifier s'il y a un message d'erreur
                if (isset($message->error)) {
                    return [
                        'success' => false,
                        'message' => 'Erreur SMS: ' . (string)$message->error,
                        'response' => $xmlString
                    ];
                }
            }

            // Si la structure n'est pas reconnue mais qu'on a du XML valide
            return [
                'success' => true,
                'message' => 'SMS traité (réponse XML reçue)',
                'response' => $xmlString
            ];

        } catch (Exception $e) {
            Log::error('XML Parsing Error', [
                'message' => $e->getMessage(),
                'xml' => $xmlString
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'analyse de la réponse SMS',
                'response' => $xmlString
            ];
        }
    }

    /**
     * Formatage du numéro de téléphone pour le Gabon
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Supprimer tous les caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Si le numéro est déjà au format international (+241...)
        if (strpos($phoneNumber, '+241') === 0) {
            return $phoneNumber;
        }
        
        // Si le numéro commence par 241, on ajoute le +
        if (strpos($phone, '241') === 0) {
            return '+' . $phone;
        }
        
        // Si le numéro commence par 0, on le remplace par +241
        if (strpos($phone, '0') === 0) {
            return '+241' . substr($phone, 1);
        }
        
        // Si le numéro a 8-9 chiffres, on ajoute +241
        if (strlen($phone) >= 8 && strlen($phone) <= 9) {
            return '+241' . $phone;
        }
        
        // Format par défaut
        return $phoneNumber;
    }

    /**
     * Test de l'API SMS
     */
    public function testSmsApi($phoneNumber = null)
    {
        $testPhone = $phoneNumber ?? '+24177777777'; // Numéro de test
        $testCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); // Code aléatoire
        
        return $this->sendVerificationCode($testPhone, $testCode);
    }

    /**
     * Vérification des paramètres de configuration
     */
    public function checkConfiguration()
    {
        $issues = [];
        
        if (empty($this->config['client'])) {
            $issues[] = 'Client ID manquant';
        }
        
        if (empty($this->config['password'])) {
            $issues[] = 'Mot de passe manquant';
        }
        
        if (empty($this->config['from'])) {
            $issues[] = 'Expéditeur manquant';
        }
        
        if (empty($this->baseUrl)) {
            $issues[] = 'URL de base manquante';
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'config' => $this->config
        ];
    }
}