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
            $message = "Code de verification e-CPPF: $code. Valide pendant 15 minutes.";
            
            // Construction de l'URL avec tous les paramètres selon votre chef
            $params = [
                'client' => $this->config['client'],
                'password' => $this->config['password'],
                'phone' => $formattedPhone,
                'text' => $message,
                'from' => $this->config['from'],
                'affiliate' => $this->config['affiliate'] ?? '999'
            ];

            // Construction de l'URL complète
            $fullUrl = $this->baseUrl . '?' . http_build_query($params);

            Log::info('SMS API Request (GET Method)', [
                'url' => $this->baseUrl,
                'full_url' => $fullUrl,
                'params' => $params,
                'phone_original' => $phoneNumber,
                'phone_formatted' => $formattedPhone,
                'message_length' => strlen($message),
                'method' => 'GET'
            ]);

            // Envoi de la requête HTTP GET (pas POST)
            $response = Http::timeout(45)->get($fullUrl);

            Log::info('SMS API Response (GET Method)', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful(),
                'response_size' => strlen($response->body())
            ]);

            // Traitement de la réponse
            if ($response->successful()) {
                $responseText = $response->body();
                
                // Log de la réponse brute pour debug
                Log::info('Raw SMS Response', [
                    'response' => $responseText,
                    'length' => strlen($responseText)
                ]);
                
                // Parser la réponse XML si c'est du XML
                if ($this->isXmlResponse($responseText)) {
                    $result = $this->parseXmlResponse($responseText);
                    Log::info('XML Response Parsed', ['parsed_result' => $result]);
                    return $result;
                }
                
                // Rechercher des indicateurs de succès dans la réponse
                if ($this->isSuccessResponse($responseText)) {
                    return [
                        'success' => true,
                        'message' => 'SMS envoyé avec succès',
                        'response' => $responseText
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Réponse inattendue: ' . substr($responseText, 0, 200),
                        'response' => $responseText
                    ];
                }
            }

            // Gestion des erreurs HTTP
            Log::error('SMS API HTTP Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $fullUrl
            ]);

            return [
                'success' => false,
                'message' => "Erreur HTTP {$response->status()}: {$response->body()}",
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('SMS Service Critical Error', [
                'message' => $e->getMessage(),
                'phone' => $phoneNumber,
                'code' => $code,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur critique: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier si la réponse est du XML
     */
    private function isXmlResponse($responseText)
    {
        return strpos(trim($responseText), '<?xml') === 0 || 
               strpos($responseText, '<messages>') !== false ||
               strpos($responseText, '<msgid>') !== false ||
               strpos($responseText, '<message>') !== false;
    }

    /**
     * Vérifier si la réponse indique un succès
     */
    private function isSuccessResponse($responseText)
    {
        $responseTextLower = strtolower(trim($responseText));
        
        // Mots-clés de succès
        $successKeywords = [
            'success', 'sent', 'delivered', 'ok', 
            'message sent', 'sms sent', 'queued'
        ];
        
        // Vérifier la présence d'un ID de message
        if (preg_match('/\b\d{10,}\b/', $responseText)) {
            Log::info('Message ID pattern found in response');
            return true;
        }
        
        // Vérifier les mots-clés de succès
        foreach ($successKeywords as $keyword) {
            if (strpos($responseTextLower, $keyword) !== false) {
                Log::info('Success keyword found', ['keyword' => $keyword]);
                return true;
            }
        }
        
        // Si la réponse ne contient que des chiffres (souvent un ID)
        if (preg_match('/^\s*\d+\s*$/', $responseText)) {
            Log::info('Response contains only numbers (likely message ID)');
            return true;
        }
        
        return false;
    }

    /**
     * Parser la réponse XML
     */
    private function parseXmlResponse($xmlString)
    {
        try {
            $cleanXml = trim($xmlString);
            Log::info('Parsing XML Response', ['xml' => $cleanXml]);
            
            $xml = simplexml_load_string($cleanXml);
            
            if ($xml === false) {
                return [
                    'success' => false,
                    'message' => 'Impossible de parser la réponse XML',
                    'response' => $xmlString
                ];
            }

            // Chercher un ID de message
            if (isset($xml->msgid) || isset($xml->id) || isset($xml->message_id)) {
                $messageId = (string)($xml->msgid ?? $xml->id ?? $xml->message_id);
                return [
                    'success' => true,
                    'message' => 'SMS envoyé avec succès',
                    'response' => ['message_id' => $messageId]
                ];
            }

            // Chercher des erreurs
            if (isset($xml->error) || isset($xml->err)) {
                $error = (string)($xml->error ?? $xml->err);
                return [
                    'success' => false,
                    'message' => 'Erreur SMS: ' . $error,
                    'response' => $xmlString
                ];
            }

            return [
                'success' => true,
                'message' => 'SMS traité (XML reçu)',
                'response' => $xmlString
            ];

        } catch (Exception $e) {
            Log::error('XML Parsing Error', [
                'error' => $e->getMessage(),
                'xml' => $xmlString
            ]);

            return [
                'success' => false,
                'message' => 'Erreur parsing XML: ' . $e->getMessage(),
                'response' => $xmlString
            ];
        }
    }

    /**
 * Formatage du numéro de téléphone pour le Gabon
 * Normalise TOUS les numéros vers le format +241XXXXXXXX (8 chiffres, sans 0 initial)
 */
private function formatPhoneNumber($phoneNumber)
{
    $phone = preg_replace('/[^0-9+]/', '', $phoneNumber);
    
    Log::info('Phone formatting', [
        'original' => $phoneNumber,
        'cleaned' => $phone
    ]);
    
    // Enlever le + pour traitement
    $digits = ltrim($phone, '+');
    
    // Si déjà au format +241XXXXXXXX (8 chiffres)
    if (preg_match('/^\+241[1-9][0-9]{7}$/', $phone)) {
        return $phone;
    }
    
    // Si format +241 avec 9 chiffres commençant par 0 → supprimer le 0
    if (preg_match('/^\+2410[0-9]{8}$/', $phone)) {
        return '+241' . substr($digits, 4); // Garder après "2410"
    }
    
    // Si commence par 241 (sans +)
    if (preg_match('/^241[1-9][0-9]{7}$/', $digits)) {
        return '+' . $digits; // Format 8 chiffres après 241
    }
    
    // Si commence par 241 avec 0 → supprimer le 0
    if (preg_match('/^2410[0-9]{8}$/', $digits)) {
        return '+241' . substr($digits, 4); // Supprimer "2410", garder les 8 chiffres
    }
    
    // Si commence par 0 (format local avec 0)
    if (preg_match('/^0[1-9][0-9]{7}$/', $digits)) {
        return '+241' . substr($digits, 1); // Supprimer le 0 initial
    }
    
    // Si 8 chiffres (sans indicatif, sans 0 initial)
    if (preg_match('/^[1-9][0-9]{7}$/', $digits)) {
        return '+241' . $digits;
    }
    
    // Si 9 chiffres commençant par 0 (sans indicatif)
    if (preg_match('/^0[1-9][0-9]{7}$/', $digits)) {
        return '+241' . substr($digits, 1); // Supprimer le 0 initial
    }
    
    Log::warning('Could not format phone number properly', [
        'original' => $phoneNumber,
        'cleaned' => $phone,
        'digits' => $digits
    ]);
    
    // En dernier recours, essayer de nettoyer un éventuel 0 initial
    if (preg_match('/0([1-9][0-9]{7,8})$/', $digits, $matches)) {
        $cleanDigits = $matches[1];
        if (strlen($cleanDigits) === 8) {
            return '+241' . $cleanDigits;
        }
    }
    
    return $phoneNumber;
}
    /**
     * Test direct de l'API avec la nouvelle méthode
     */
    public function testSmsApi($phoneNumber = null)
    {
        $testPhone = $phoneNumber ?? '+241077777777';
        $testCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        Log::info('Testing SMS API with corrected URL', [
            'base_url' => $this->baseUrl,
            'test_phone' => $testPhone,
            'test_code' => $testCode
        ]);
        
        return $this->sendVerificationCode($testPhone, $testCode);
    }

    /**
     * Vérification de configuration mise à jour
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
        
        if (empty($this->config['affiliate'])) {
            $issues[] = 'Paramètre affiliate manquant (devrait être 999)';
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'config' => [
                'client' => $this->config['client'] ?? 'MISSING',
                'password' => $this->config['password'] ? '***SET***' : 'MISSING',
                'from' => $this->config['from'] ?? 'MISSING',
                'url' => $this->baseUrl ?? 'MISSING',
                'affiliate' => $this->config['affiliate'] ?? 'MISSING'
            ]
        ];
    }



    // Ajoutez cette méthode dans SmsServices.php
public function sendCustomMessage($phoneNumber, $message)
{
    try {
        $formattedPhone = $this->formatPhoneNumber($phoneNumber);
        
        $params = [
            'client' => $this->config['client'],
            'password' => $this->config['password'],
            'phone' => $formattedPhone,
            'text' => $message,
            'from' => $this->config['from'],
            'affiliate' => $this->config['affiliate'] ?? '999'
        ];

        $fullUrl = $this->baseUrl . '?' . http_build_query($params);
        
        Log::info('Custom SMS Request', [
            'phone' => $formattedPhone,
            'message' => $message,
            'url' => $fullUrl
        ]);

        $response = Http::timeout(45)->get($fullUrl);

        if ($response->successful()) {
            $responseText = $response->body();
            
            if ($this->isSuccessResponse($responseText) || $this->isXmlResponse($responseText)) {
                return [
                    'success' => true,
                    'message' => 'SMS personnalisé envoyé avec succès',
                    'response' => $responseText
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Échec envoi SMS personnalisé',
            'response' => $response->body()
        ];
        
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ];
    }
}
}