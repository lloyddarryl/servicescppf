{{-- resources/views/emails/reclamation-deleted.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réclamation supprimée</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 10px;
        }
        .alert-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #ef4444;
        }
        .user-info {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">⚠️ CPPF</div>
            <h1 style="color: #dc2626;">Réclamation Supprimée</h1>
        </div>

        <div class="alert-box">
            <h3 style="margin-top: 0; color: #dc2626;">🗑️ Réclamation supprimée par l'utilisateur</h3>
            <p><strong>N° {{ $numeroReclamation }}</strong> - {{ $typeReclamation }}</p>
            @if($motifSuppression)
            <p><strong>Motif :</strong> {{ $motifSuppression }}</p>
            @endif
        </div>

        <div class="user-info">
            <h4>👤 Informations de l'utilisateur</h4>
            <p><strong>Nom :</strong> {{ $user->prenoms }} {{ $user->nom }}</p>
            <p><strong>Email :</strong> {{ $user->email }}</p>
            @if($user->telephone)
            <p><strong>Téléphone :</strong> {{ $user->telephone }}</p>
            @endif
        </div>

        <p><em>Cette réclamation a été supprimée définitivement de notre système.</em></p>

        <div class="footer">
            <p>📧 Notification envoyée le {{ now()->format('d/m/Y à H:i') }}</p>
            <p>CPPF - Caisse de Pension des Fonctionnaires</p>
        </div>
    </div>
</body>
</html>