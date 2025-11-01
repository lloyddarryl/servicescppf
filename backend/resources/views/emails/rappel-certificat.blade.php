<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel Certificat de Vie</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #10B981;
        }
        .logo {
            font-size: 32px;
            color: #10B981;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .title {
            font-size: 24px;
            color: #1F2937;
            margin: 20px 0;
        }
        .content {
            font-size: 16px;
            color: #4B5563;
            margin-bottom: 20px;
        }
        .highlight {
            background-color: #FEF3C7;
            padding: 15px;
            border-left: 4px solid #F59E0B;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-box {
            background-color: #EFF6FF;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-box strong {
            color: #1E40AF;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #10B981;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background-color: #059669;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            font-size: 14px;
            color: #6B7280;
            text-align: center;
        }
        .urgent {
            color: #DC2626;
            font-weight: bold;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">📋 CPPF</div>
            <div style="color: #6B7280;">Caisse des Pensions et Prestations Familiales</div>
        </div>

        <h1 class="title">🚨 Rappel Important</h1>

        <div class="content">
            <p>Bonjour {{ $retraite->civilite ?? '' }} {{ $retraite->prenoms }} {{ $retraite->nom }},</p>

            <div class="highlight">
                <strong>⚠️ Votre certificat de vie n'a pas été déposé ou a expiré.</strong>
            </div>

            <p>Nous vous rappelons qu'il est <strong class="urgent">obligatoire</strong> de déposer votre certificat de vie annuel pour continuer à percevoir votre pension de retraite.</p>
        </div>

        <div class="info-box">
            <strong>Vos informations :</strong><br>
            <strong>Numéro de pension :</strong> {{ $retraite->numero_pension }}<br>
            <strong>Email :</strong> {{ $retraite->email }}<br>
            <strong>Téléphone :</strong> {{ $retraite->telephone ?? 'Non renseigné' }}
        </div>

        <div class="content">
            <h3 style="color: #1F2937;">📄 Comment déposer votre certificat :</h3>
            <ul>
                <li>Connectez-vous à votre espace personnel</li>
                <li>Cliquez sur "Documents" dans le menu</li>
                <li>Sélectionnez "Déposer un document"</li>
                <li>Choisissez le type "Certificat de vie"</li>
                <li>Téléchargez votre certificat (PDF, JPG ou PNG)</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/connexion" class="button">
                Se connecter à mon espace
            </a>
        </div>

        <div class="highlight">
            <strong>📌 Important :</strong>
            <ul style="margin: 10px 0;">
                <li>Le certificat doit être récent (moins de 3 mois)</li>
                <li>Il doit être signé par une autorité compétente</li>
                <li>Le document doit être lisible et en bon état</li>
            </ul>
        </div>

        <div class="content">
            <h3 style="color: #1F2937;">🏢 Dépôt physique :</h3>
            <p>Vous pouvez également déposer votre certificat directement à nos bureaux :</p>
            <p style="margin-left: 20px;">
                <strong>CPPF - Siège social</strong><br>
                [Adresse complète]<br>
                Horaires : Lundi - Vendredi, 8h - 17h
            </p>
        </div>

        <div class="content">
            <p><strong>Besoin d'aide ?</strong></p>
            <p>Notre équipe est disponible pour vous accompagner :</p>
            <p style="margin-left: 20px;">
                📞 Téléphone : [Numéro de téléphone]<br>
                📧 Email : support@cppf.ga<br>
                🕐 Horaires : Lundi - Vendredi, 8h - 17h
            </p>
        </div>

        <div class="footer">
            <p>Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p><strong>Caisse des Pensions et Prestations Familiales (CPPF)</strong></p>
            <p style="font-size: 12px; color: #9CA3AF;">
                © {{ date('Y') }} CPPF - Tous droits réservés
            </p>
        </div>
    </div>
</body>
</html>