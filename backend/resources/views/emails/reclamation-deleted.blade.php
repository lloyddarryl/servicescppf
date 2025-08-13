<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réclamation supprimée</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { background: #DC2626; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; margin: -20px -20px 20px -20px; }
        .info-box { background: #fef2f2; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #DC2626; }
        .footer { text-align: center; margin-top: 30px; padding: 20px; border-top: 1px solid #eee; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Réclamation Supprimée</h1>
            <p>Une réclamation a été supprimée par l'utilisateur</p>
        </div>

        <div class="info-box">
            <h3>Réclamation supprimée</h3>
            <p><strong>Numéro :</strong> {{ $numeroReclamation }}</p>
            <p><strong>Type :</strong> {{ $typeReclamation }}</p>
            <p><strong>Date de suppression :</strong> {{ now()->format('d/m/Y à H:i') }}</p>
        </div>

        <div class="info-box">
            <h3>Utilisateur</h3>
            <p><strong>Nom :</strong> {{ $user->prenoms }} {{ $user->nom }}</p>
            <p><strong>Email :</strong> {{ $user->email }}</p>
        </div>

        @if($motifSuppression)
            <div class="info-box">
                <h3>Motif de suppression</h3>
                <p style="white-space: pre-wrap; background: white; padding: 10px; border-radius: 4px;">{{ $motifSuppression }}</p>
            </div>
        @endif

        <div class="footer">
            <p>Cette notification a été générée automatiquement par le système e-Services CPPF.</p>
        </div>
    </div>
</body>
</html>