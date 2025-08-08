{{-- resources/views/emails/reclamation-status-changed.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour de votre réclamation</title>
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
            color: #1e40af;
            margin-bottom: 10px;
        }
        .status-change {
            background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
            border: 1px solid #0ea5e9;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        .status-arrow {
            font-size: 24px;
            margin: 0 15px;
            color: #0ea5e9;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        .old-status {
            background: #f3f4f6;
            color: #6b7280;
            text-decoration: line-through;
        }
        .new-status {
            background: #dcfce7;
            color: #166534;
        }
        .comment-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
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
            <div class="logo">🏛️ CPPF</div>
            <h1>Mise à jour de votre réclamation</h1>
        </div>

        <p>Bonjour <strong>{{ $user->prenoms }} {{ $user->nom }}</strong>,</p>
        
        <p>Le statut de votre réclamation <strong>N° {{ $reclamation->numero_reclamation }}</strong> a été mis à jour.</p>

        <div class="status-change">
            <div style="margin-bottom: 15px; color: #0f172a; font-weight: 600;">
                Changement de statut
            </div>
            <div>
                <span class="status-badge old-status">{{ $ancienStatut }}</span>
                <span class="status-arrow">→</span>
                <span class="status-badge new-status">{{ $nouveauStatut }}</span>
            </div>
        </div>

        @if($historique->commentaire)
        <div class="comment-box">
            <h4 style="margin-top: 0; color: #92400e;">💬 Commentaire</h4>
            <p style="margin-bottom: 0;">{{ $historique->commentaire }}</p>
        </div>
        @endif

        <p>Vous pouvez consulter le détail complet de votre réclamation et son suivi en vous connectant à votre espace personnel.</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.frontend_url') }}/dashboard" 
               style="background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 600;">
                Consulter ma réclamation
            </a>
        </div>

        <div class="footer">
            <p>📧 Notification envoyée le {{ now()->format('d/m/Y à H:i') }}</p>
            <p>CPPF - Caisse de Pension des Fonctionnaires</p>
        </div>
    </div>
</body>
</html>