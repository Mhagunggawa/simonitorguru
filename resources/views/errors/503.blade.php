<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Sedang Dalam Pemeliharaan - SiMonitorGuru</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-amber: #f59e0b;
            --accent-amber-glow: rgba(245, 158, 11, 0.15);
            --accent-emerald: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, rgba(245, 158, 11, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.05) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(30, 41, 59, 0.5) 0px, transparent 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-main);
        }

        .container {
            width: 100%;
            max-w: 640px;
            max-width: 600px;
            text-align: center;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 48px 36px;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px var(--border-color);
        }

        .icon-wrapper {
            width: 88px;
            height: 88px;
            margin: 0 auto 24px;
            background: var(--accent-amber-glow);
            border: 2px solid rgba(245, 158, 11, 0.3);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: pulse-glow 3s infinite ease-in-out;
        }

        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(245, 158, 11, 0.2);
                transform: scale(1);
            }
            50% {
                box-shadow: 0 0 35px rgba(245, 158, 11, 0.4);
                transform: scale(1.03);
            }
        }

        .icon-wrapper svg {
            width: 44px;
            height: 44px;
            color: var(--accent-amber);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 9999px;
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgba(245, 158, 11, 0.3);
            margin-bottom: 20px;
        }

        .badge-dot {
            width: 8px;
            height: 8px;
            background-color: var(--accent-amber);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px var(--accent-amber);
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        h1 {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        p.description {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .divider {
            height: 1px;
            background: var(--border-color);
            margin: 28px 0;
        }

        .btn-refresh {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #ffffff;
            color: #0f172a;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 28px;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
        }

        .btn-refresh:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.25);
        }

        .footer-note {
            margin-top: 24px;
            font-size: 12px;
            color: var(--text-muted);
            opacity: 0.8;
        }

        @media (max-width: 480px) {
            .card {
                padding: 32px 20px;
                border-radius: 20px;
            }
            h1 {
                font-size: 22px;
            }
            p.description {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="icon-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
            </div>

            <div class="badge">
                <span class="badge-dot"></span>
                TERJADI KESALAHAN SISTEM
            </div>

            <h1>Sistem Terjadi Kesalahan - ERROR 503</h1>

        

            <button onclick="window.location.reload();" class="btn-refresh">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                Coba Muat Ulang Halaman
            </button>

            <div class="divider"></div>

            <p class="footer-note">
                Sistem Informasi Monitoring Guru (SiMonitorGuru) &bull; Silakan hubungi Administrator jika terdapat keperluan mendesak.
            </p>
        </div>
    </div>
</body>
</html>

