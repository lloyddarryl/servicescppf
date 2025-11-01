<?php
// File: backend/app/Exceptions/Handler.php
// Corriger l'erreur "Route [login] not defined"

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * ✅ CORRECTION : Gérer les exceptions d'authentification pour les API
     * Cette méthode empêche la redirection vers la route 'login' qui n'existe pas
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Si c'est une requête API, retourner une réponse JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié. Veuillez vous connecter.',
                'error' => 'Unauthenticated'
            ], 401);
        }

        // ✅ CORRECTION CRITIQUE: Ne pas essayer de rediriger vers route('login')
        // Pour une application 100% API, on retourne toujours du JSON
        return response()->json([
            'success' => false,
            'message' => 'Non authentifié',
            'error' => 'Unauthenticated'
        ], 401);
    }
}