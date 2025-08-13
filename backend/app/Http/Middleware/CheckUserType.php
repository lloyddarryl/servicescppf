<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();
        
        // Vérifier si l'utilisateur correspond au type demandé
        if (($type === 'actif' && !$user instanceof \App\Models\Agent) ||
            ($type === 'retraite' && !$user instanceof \App\Models\Retraite)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé pour ce type d\'utilisateur'
            ], 403);
        }

        return $next($request);
    }
}