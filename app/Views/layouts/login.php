<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($titulo ?? 'Login') ?> · Procuradoria da Mulher</title>
    <meta name="theme-color" content="#a179c3">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Procuradoria da Mulher">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/pwa/apple-touch-icon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --lilas: #a179c3;
            --lilas-claro: #c5a8df;
            --roxo-medio: #7e51a5;
            --roxo-forte: #63348c;
            --texto-primario: #1f2937;
            --texto-secundario: #6b7280;
            --borda-suave: #e5e7eb;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; }

        body {
            font-family: "Inter", system-ui, -apple-system, "Segoe UI", sans-serif;
            color: var(--texto-primario);
            background: #fff;
        }

        .pagina-login {
            display: flex;
            min-height: 100vh;
            min-height: 100dvh;
        }

        /* ---------- PAINEL ESQUERDO ---------- */
        .painel-esquerdo {
            width: 58%;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
            background:
                radial-gradient(120% 90% at 85% 10%, rgba(197, 168, 223, 0.55) 0%, rgba(197, 168, 223, 0) 55%),
                radial-gradient(100% 80% at 10% 100%, rgba(99, 52, 140, 0.55) 0%, rgba(99, 52, 140, 0) 50%),
                linear-gradient(150deg, #7e51a5 0%, #a179c3 48%, #c5a8df 100%);
        }

        .painel-esquerdo::before,
        .painel-esquerdo::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            pointer-events: none;
        }

        .painel-esquerdo::before {
            width: 420px;
            height: 420px;
            top: -120px;
            right: -90px;
            background: rgba(255, 255, 255, 0.16);
        }

        .painel-esquerdo::after {
            width: 380px;
            height: 380px;
            bottom: -140px;
            left: -110px;
            background: rgba(99, 52, 140, 0.35);
        }

        .painel-esquerdo .decoracao-1,
        .painel-esquerdo .decoracao-2,
        .painel-esquerdo .decoracao-3 {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .painel-esquerdo .decoracao-1 {
            width: 220px;
            height: 220px;
            top: 16%;
            left: 8%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0) 70%);
            filter: blur(10px);
        }

        .painel-esquerdo .decoracao-2 {
            width: 160px;
            height: 160px;
            bottom: 22%;
            right: 12%;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .painel-esquerdo .decoracao-3 {
            width: 90px;
            height: 90px;
            top: 12%;
            right: 20%;
            background: rgba(255, 255, 255, 0.12);
            filter: blur(6px);
        }

        .identidade {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 1.75rem;
            max-width: 420px;
        }

        .identidade .logo-branca {
            width: min(230px, 60vw);
            height: auto;
            filter: drop-shadow(0 14px 30px rgba(63, 31, 90, 0.35));
        }

        .identidade .nome-instituicao {
            color: #fff;
            font-size: clamp(1.1rem, 1.6vw, 1.45rem);
            font-weight: 700;
            line-height: 1.35;
            letter-spacing: 0.01em;
        }

        /* ---------- PAINEL DIREITO ---------- */
        .painel-direito {
            width: 42%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 2rem;
            background: #fff;
        }

        .cartao-formulario {
            width: 100%;
            max-width: 400px;
        }

        .cartao-formulario .boas-vindas {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--texto-primario);
            line-height: 1.2;
        }

        .cartao-formulario .subtitulo {
            margin-top: 0.8rem;
            color: var(--texto-secundario);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .cartao-formulario .grupo-formulario {
            margin-top: 1.8rem;
        }

        .cartao-formulario .grupo-campo {
            margin-bottom: 1.6rem;
        }

        .cartao-formulario label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
        }

        .cartao-formulario input {
            width: 100%;
            height: 2.85rem;
            padding: 0 0.9rem;
            font-size: 0.95rem;
            color: var(--texto-primario);
            background: #fff;
            border: 1px solid var(--borda-suave);
            border-radius: 0.55rem;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            font-family: inherit;
        }

        .cartao-formulario input:focus {
            border-color: var(--lilas);
            box-shadow: 0 0 0 3px rgba(161, 121, 195, 0.18);
        }

        .cartao-formulario input::placeholder {
            color: #9ca3af;
        }

        .botao-entrar {
            width: 100%;
            height: 2.85rem;
            margin-top: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #8a5fb0 0%, #a179c3 100%);
            border: none;
            border-radius: 0.55rem;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.1s ease;
            font-family: inherit;
        }

        .botao-entrar:hover {
            background: linear-gradient(135deg, #7e51a5 0%, #9669b8 100%);
        }

        .botao-entrar:active {
            transform: translateY(1px);
        }

        .rodape-formulario {
            margin-top: 2.5rem;
            text-align: center;
            color: var(--texto-secundario);
            font-size: 0.88rem;
        }

        .rodape-formulario a {
            color: #8a5fb0;
            font-weight: 600;
            text-decoration: none;
        }

        .rodape-formulario a:hover {
            text-decoration: underline;
        }

        .alerta {
            margin-top: 1.25rem;
            padding: 0.75rem 1rem;
            border-radius: 0.55rem;
            font-size: 0.85rem;
            line-height: 1.45;
        }

        .alerta-erro {
            color: #9b1c1c;
            background: #fdecec;
            border: 1px solid #f5c6c6;
        }

        .alerta-sucesso {
            color: #14663a;
            background: #e9f7ef;
            border: 1px solid #b7e4c9;
        }

        /* ---------- RESPONSIVO ---------- */
        @media (max-width: 900px) {
            .pagina-login {
                flex-direction: column;
            }

            .painel-esquerdo {
                width: 100%;
                min-height: 280px;
                padding: 2.5rem 1.5rem;
            }

            .identidade .logo-branca {
                width: min(150px, 50vw);
            }

            .identidade .nome-instituicao {
                font-size: 1.05rem;
            }

            .painel-direito {
                width: 100%;
                flex: 1;
                padding: 2rem 1.25rem 2.5rem;
            }
        }

        @media (max-width: 480px) {
            .painel-esquerdo {
                min-height: 240px;
            }

            .cartao-formulario .boas-vindas {
                font-size: 1.35rem;
            }
        }
    </style>
</head>
<body>
    <?php
    $configLogin = (new \App\Models\ConfiguracaoModel())->obterTodos();
    $nomeLogin   = (string) ($configLogin['nome_procuradoria'] ?? 'Procuradoria da Mulher');
    ?>

    <div class="pagina-login">
        <!-- Painel visual / identidade -->
        <div class="painel-esquerdo" aria-hidden="true">
            <span class="decoracao-1"></span>
            <span class="decoracao-2"></span>
            <span class="decoracao-3"></span>

            <div class="identidade">
                <img src="<?= base_url('assets/pwa/logo-branca.png') ?>" alt="Logo de <?= esc($nomeLogin) ?>" class="logo-branca">
                <p class="nome-instituicao"><?= esc($nomeLogin) ?></p>
            </div>
        </div>

        <!-- Painel do formulário -->
        <div class="painel-direito">
            <div class="cartao-formulario">
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker
                    .register('<?= base_url('sw.js') ?>', { scope: '<?= base_url() ?>' })
                    .catch(function () {});
            });
        }
    </script>
</body>
</html>