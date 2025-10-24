<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier que l'utilisateur est authentifié
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // Vérifier que c'est un admin (table admins)
        if (!($request->user() instanceof \App\Models\Admin)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé - Admin requis'
            ], 403);
        }

        // Vérifier que l'admin est actif
        if ($request->user()->statut !== 'actif') {
            return response()->json([
                'success' => false,
                'message' => 'Compte administrateur inactif'
            ], 403);
        }

        return $next($request);
    }
}