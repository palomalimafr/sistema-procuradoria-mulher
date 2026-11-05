<?= $this->extend('layouts/login') ?>

<?= $this->section('content') ?>

<?php
$conviteInvalido = $conviteInvalido ?? null;
$erros           = session()->getFlashdata('erros');
$erros           = is_array($erros) ? $erros : ($erros === null ? [] : ['geral' => (string) $erros]);
?>

<?php if ($conviteInvalido !== null): ?>
    <!-- Convite inexistente, expirado ou já utilizado: nada é exibido além do motivo. -->
    <h1 class="boas-vindas">Convite indisponível</h1>
    <p class="subtitulo">Não foi possível continuar com o primeiro acesso.</p>

    <div class="alerta alerta-erro" role="alert"><?= esc($conviteInvalido) ?></div>

    <div class="rodape-formulario">
        Já possui senha? <a href="<?= base_url('login') ?>">Fazer login</a>
    </div>

<?php else: ?>
    <h1 class="boas-vindas">Primeiro acesso</h1>
    <p class="subtitulo">
        Crie a sua senha de acesso ao sistema. Ela será usada a partir de agora
        em todas as suas entradas.
    </p>

    <?php if (session()->getFlashdata('sucesso')): ?>
        <div class="alerta alerta-sucesso" role="alert"><?= esc(session()->getFlashdata('sucesso')) ?></div>
    <?php endif; ?>

    <?php if (! empty($erros)): ?>
        <div class="alerta alerta-erro" role="alert">
            <?php if (is_array($erros)): ?>
                <ul style="margin: 0; padding-left: 1.1rem;">
                    <?php foreach ($erros as $erro): ?>
                        <li><?= esc(is_string($erro) ? $erro : '') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <?= esc($erros) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="grupo-formulario">
        <div style="background:#f9fafb;border:1px solid var(--borda-suave);border-radius:0.55rem;padding:0.8rem 0.9rem;">
            <p style="font-size:0.78rem;color:var(--texto-secundario);margin:0 0 0.2rem;">Conta que será criada</p>
            <p style="font-size:0.95rem;color:var(--texto-primario);font-weight:600;margin:0;word-break:break-all;"><?= esc($email ?? '—') ?></p>
        </div>
        <?php if (! empty($expiraEm)): ?>
            <p style="font-size:0.78rem;color:var(--texto-secundario);margin:0.5rem 0 0;">
                Convite válido até <?= esc(date('d/m/Y \à\s H:i', strtotime((string) $expiraEm))) ?>.
            </p>
        <?php endif; ?>
    </div>

    <form action="<?= base_url('primeiro-acesso/' . rawurlencode((string) ($token ?? ''))) ?>" method="POST">
        <?= csrf_field() ?>

        <div class="grupo-campo">
            <label for="senha">Crie sua senha *</label>
            <input type="password" id="senha" name="senha" placeholder="Mínimo de 6 caracteres" required minlength="6" maxlength="72" autofocus>
        </div>

        <div class="grupo-campo">
            <label for="confirma_senha">Confirme sua senha *</label>
            <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Repita a senha" required minlength="6" maxlength="72">
        </div>

        <div id="forca-senha" style="display:none;margin-top:-0.9rem;margin-bottom:1.1rem;">
            <div style="height:4px;border-radius:2px;background:var(--borda-suave);overflow:hidden;">
                <div id="forca-barra" style="height:100%;width:0%;transition:width 0.2s ease;"></div>
            </div>
            <p id="forca-texto" style="font-size:0.78rem;color:var(--texto-secundario);margin:0.35rem 0 0;"></p>
        </div>

        <button type="submit" class="botao-entrar">
            Criar minha conta
        </button>
    </form>

    <div class="rodape-formulario">
        Já possui senha? <a href="<?= base_url('login') ?>">Fazer login</a>
    </div>

    <script>
        (function () {
            var campoSenha = document.getElementById('senha');
            var campoConf  = document.getElementById('confirma_senha');
            var painel     = document.getElementById('forca-senha');
            var barra      = document.getElementById('forca-barra');
            var texto      = document.getElementById('forca-texto');
            if (!campoSenha || !painel) { return; }

            var cores = { fraca: '#c2410c', media: '#b45309', forte: '#14663a' };

            function avaliar() {
                var v = campoSenha.value;
                if (!v) { painel.style.display = 'none'; return; }

                painel.style.display = 'block';

                var pontos = 0;
                if (v.length >= 6)  { pontos++; }
                if (v.length >= 10) { pontos++; }
                if (/[a-z]/.test(v) && /[A-Z]/.test(v)) { pontos++; }
                if (/[0-9]/.test(v)) { pontos++; }
                if (/[^A-Za-z0-9]/.test(v)) { pontos++; }

                var nivel = pontos <= 2 ? 'fraca' : (pontos <= 3 ? 'media' : 'forte');
                barra.style.width = (pontos / 5 * 100) + '%';
                barra.style.background = cores[nivel];
                texto.textContent = 'Senha ' + nivel + (nivel === 'forte' ? ' — boa para usar.' : ' — considere usar letras, números e símbolos.');

                if (campoConf.value) {
                    texto.textContent += campoConf.value === v ? ' As senhas conferem.' : ' As senhas não conferem.';
                }
            }

            campoSenha.addEventListener('input', avaliar);
            if (campoConf) { campoConf.addEventListener('input', avaliar); }
        })();
    </script>
<?php endif; ?>
<?= $this->endSection() ?>
