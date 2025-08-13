<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour de votre réclamation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { background: #1E3A8A; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; margin: -20px -20px 20px -20px; }
        .info-box { background: #f0f9ff; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #3B82F6; }
        .footer { text-align: center; margin-top: 30px; padding: 20px; border-top: 1px solid #eee; color: #666; }
        .status-change { background: #E3F2FD; border: 2px solid #2196F3; padding: 15px; border-radius: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Mise à jour de votre réclamation</h1>
            <p>Le statut de votre réclamation a été modifié</p>
        </div>

        <div class="info-box">
            <h3>Réclamation N° {{ $reclamation->numero_reclamation }}</h3>
            <p><strong>Type :</strong> {{ $reclamation->type_reclamation_info['nom'] ?? $reclamation->type_reclamation }}</p>
            @if($reclamation->sujet_personnalise)
                <p><strong>Sujet :</strong> {{ $reclamation->sujet_personnalise }}</p>
            @endif
        </div>

        <div class="status-change">
            <h3> Changement de statut</h3>
            @if($ancienStatut)
                <p><strong>Ancien statut :</strong> <span style="color: #666;">{{ $ancienStatut }}</span></p>
                <p style="font-size: 1.5em;">⬇️</p>
            @endif
            <p><strong>Nouveau statut :</strong> <span style="color: #2196F3; font-weight: bold;">{{ $nouveauStatut }}</span></p>
            <p><strong>Date :</strong> {{ $historique->created_at->format('d/m/Y à H:i') }}</p>
        </div>

        @if($historique->commentaire)
            <div class="info-box">
                <h3> Commentaire</h3>
                <p style="white-space: pre-wrap; background: white; padding: 10px; border-radius: 4px;">{{ $historique->commentaire }}</p>
            </div>
        @endif

        <div class="footer">
            <p>Connectez-vous à votre espace e-Services pour suivre l'évolution complète de votre réclamation.</p>
            <p><a href="{{ config('app.frontend_url', 'http://localhost:3000') }}" style="color: #1E3A8A; text-decoration: none; font-weight: bold;">🔗 Accéder à mon espace</a></p>
        </div>
    </div>
</body>
</html>