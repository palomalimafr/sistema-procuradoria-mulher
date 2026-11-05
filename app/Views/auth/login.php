<?= $this->extend('layouts/login') ?>

<?= $this->section('content') ?>
<h1 class="boas-vindas">Bem-vindo(a)</h1>
<p class="subtitulo">Painel Administrativo</p>

<?php $flashError = session()->getFlashdata('erro'); ?>
<?php if ($flashError): $flashError = is_array($flashError) ? implode(' ', $flashError) : $flashError; ?>
    <div class="alerta alerta-erro" role="alert"><?= esc($flashError) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('sucesso')): ?>
    <div class="alerta alerta-sucesso" role="alert"><?= esc(session()->getFlashdata('sucesso')) ?></div>
<?php endif; ?>

<form action="<?= base_url('login/autenticar') ?>" method="POST">
    <div class="grupo-campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="seuemail@exemplo.com" requisred autofocus>
    </div>
    <div class="grupo-campo">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="Sua senha" required>
    </div>
    <button type="submit" class="botao-entrar">
        Entrar
    </button>
</form>

<div class="rodape-formulario">
    Ainda não possui cadastro? <a href="<?= base_url('cadastro') ?>">Cadastre-se</a>
</div>
<?= $this->endSection() ?>