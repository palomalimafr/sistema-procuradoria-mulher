<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($titulo ?? 'Procuradoria da Mulher') ?> · Procuradoria da Mulher</title>
    <meta name="theme-color" content="#a179c3">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Procuradoria da Mulher">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/pwa/apple-touch-icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bs-primary: #a179c3;
            --bs-primary-rgb: 161, 121, 195;
            --bs-link-color-rgb: 161, 121, 195;
            --bs-link-hover-color-rgb: 137, 103, 166;
        }
        body {
            background: linear-gradient(135deg, #a179c3 0%, #c9aee0 100%);
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        .card {
            max-width: 500px;
            margin: 2rem auto;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card.card-wide {
            max-width: 750px;
        }
        .card-body { padding: 2rem; }
        .form-group { margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <?= $this->renderSection('content') ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php $flashError = session()->getFlashdata('erro'); ?>
    <?php if ($flashError): $flashError = is_array($flashError) ? implode(' ', $flashError) : $flashError; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alert.style.zIndex = '9999';
            alert.textContent = '<?= esc($flashError) ?>';
            alert.innerHTML += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            document.body.appendChild(alert);
        });
    </script>
    <?php endif; ?>
    <?php if (session()->getFlashdata('sucesso')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alert.style.zIndex = '9999';
            alert.textContent = '<?= esc(session()->getFlashdata('sucesso')) ?>';
            alert.innerHTML += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            document.body.appendChild(alert);
        });
    </script>
    <?php endif; ?>

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
