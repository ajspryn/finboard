<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode PIN Login - Finboard</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .pin-code {
            background-color: #f8f9fa;
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .pin-number {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 5px;
            font-family: 'Courier New', monospace;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }
        .security-note {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🏦 Finboard</div>
            <h2>Kode PIN Login</h2>
        </div>

        <p>Halo,</p>

        <p>Anda telah meminta kode PIN untuk login ke akun Finboard. Berikut adalah kode PIN Anda:</p>

        <div class="pin-code">
            <div class="pin-number">{{ $pinCode }}</div>
        </div>

        <div class="security-note">
            <strong>🔒 Catatan Keamanan:</strong><br>
            • Kode PIN ini bersifat rahasia dan hanya untuk Anda<br>
            • Kode PIN akan kadaluarsa dalam <strong>10 menit</strong><br>
            • Jangan bagikan kode ini dengan siapapun
        </div>

        <div class="warning">
            <strong>⚠️ Peringatan:</strong> Jika Anda tidak meminta kode PIN ini, segera hubungi administrator sistem.
        </div>

        <p>
            Masukkan kode PIN di halaman login untuk melanjutkan.<br>
            Jika kode PIN sudah kadaluarsa, Anda dapat meminta kode baru.
        </p>

        <p>Terima kasih,<br><strong>Tim Finboard</strong></p>

        <div class="footer">
            <p>
                Email ini dikirim secara otomatis oleh sistem Finboard.<br>
                Jika Anda memiliki pertanyaan, hubungi administrator sistem.
            </p>
        </div>
    </div>
</body>
</html>
