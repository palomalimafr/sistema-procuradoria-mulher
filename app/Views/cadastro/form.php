<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="card card-wide">
    <div class="card-body p-4">
        <div class="text-center mb-3">
            <i class="bi bi-house-heart" style="font-size: 2.5rem; color: #a179c3;"></i>
            <h4 class="fw-bold text-primary mt-2">Cadastro da Mulher</h4>
            <p class="text-muted small">Preencha os dados abaixo para criar sua conta</p>
        </div>

        <?php $flashError = session()->getFlashdata('erro'); ?>
        <?php if ($flashError): $flashError = is_array($flashError) ? implode(' ', $flashError) : $flashError; ?>
            <div class="alert alert-danger"><?= esc($flashError) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('sucesso')): ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('sucesso')) ?></div>
        <?php endif; ?>

        <?php $erros = session()->getFlashdata('erros'); ?>
        <?php if (!empty($erros)): ?>
            <div class="alert alert-danger">
                <?php if (is_array($erros)): ?>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($erros as $e): ?>
                            <li><?= esc($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <?= esc($erros) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('cadastro/registrar') ?>" method="POST">
            <?= csrf_field() ?>

            <!-- DADOS PESSOAIS -->
            <h6 class="text-primary fw-bold mb-2 mt-3"><i class="bi bi-person me-1"></i> Dados Pessoais</h6>
            <hr class="my-2">
            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <label for="nome_completo" class="form-label small fw-semibold">Nome completo *</label>
                    <input type="text" class="form-control form-control-sm" id="nome_completo" name="nome_completo" value="<?= esc(old('nome_completo')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="nome_social" class="form-label small fw-semibold">Nome social</label>
                    <input type="text" class="form-control form-control-sm" id="nome_social" name="nome_social" value="<?= esc(old('nome_social')) ?>">
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label for="cpf" class="form-label small fw-semibold">CPF</label>
                    <input type="text" class="form-control form-control-sm" id="cpf" name="cpf" value="<?= esc(old('cpf')) ?>" maxlength="14" placeholder="000.000.000-00">
                </div>
                <div class="col-md-3">
                    <label for="rg" class="form-label small fw-semibold">RG</label>
                    <input type="text" class="form-control form-control-sm" id="rg" name="rg" value="<?= esc(old('rg')) ?>" maxlength="20">
                </div>
                <div class="col-md-3">
                    <label for="data_nascimento" class="form-label small fw-semibold">Data de nascimento</label>
                    <input type="date" class="form-control form-control-sm" id="data_nascimento" name="data_nascimento" value="<?= esc(old('data_nascimento')) ?>">
                </div>
                <div class="col-md-3">
                    <label for="sexo" class="form-label small fw-semibold">Sexo</label>
                    <select class="form-select form-select-sm" id="sexo" name="sexo">
                        <option value="">Selecione</option>
                        <option value="F" <?= old('sexo') === 'F' ? 'selected' : '' ?>>Feminino</option>
                        <option value="M" <?= old('sexo') === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="O" <?= old('sexo') === 'O' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label for="estado_civil" class="form-label small fw-semibold">Estado civil</label>
                    <select class="form-select form-select-sm" id="estado_civil" name="estado_civil">
                        <option value="">Selecione</option>
                        <option value="solteira" <?= old('estado_civil') === 'solteira' ? 'selected' : '' ?>>Solteira</option>
                        <option value="casada" <?= old('estado_civil') === 'casada' ? 'selected' : '' ?>>Casada</option>
                        <option value="divorciada" <?= old('estado_civil') === 'divorciada' ? 'selected' : '' ?>>Divorciada</option>
                        <option value="viuva" <?= old('estado_civil') === 'viuva' ? 'selected' : '' ?>>Viúva</option>
                        <option value="uniao_estavel" <?= old('estado_civil') === 'uniao_estavel' ? 'selected' : '' ?>>União estável</option>
                        <option value="outra" <?= old('estado_civil') === 'outra' ? 'selected' : '' ?>>Outra</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="escolaridade" class="form-label small fw-semibold">Escolaridade</label>
                    <select class="form-select form-select-sm" id="escolaridade" name="escolaridade">
                        <option value="">Selecione</option>
                        <option value="alfabetizacao" <?= old('escolaridade') === 'alfabetizacao' ? 'selected' : '' ?>>Alfabetização</option>
                        <option value="fundamental_incompleto" <?= old('escolaridade') === 'fundamental_incompleto' ? 'selected' : '' ?>>Fundamental incompleto</option>
                        <option value="fundamental_completo" <?= old('escolaridade') === 'fundamental_completo' ? 'selected' : '' ?>>Fundamental completo</option>
                        <option value="medio_incompleto" <?= old('escolaridade') === 'medio_incompleto' ? 'selected' : '' ?>>Médio incompleto</option>
                        <option value="medio_completo" <?= old('escolaridade') === 'medio_completo' ? 'selected' : '' ?>>Médio completo</option>
                        <option value="superior_incompleto" <?= old('escolaridade') === 'superior_incompleto' ? 'selected' : '' ?>>Superior incompleto</option>
                        <option value="superior_completo" <?= old('escolaridade') === 'superior_completo' ? 'selected' : '' ?>>Superior completo</option>
                        <option value="pos_graduacao" <?= old('escolaridade') === 'pos_graduacao' ? 'selected' : '' ?>>Pós-graduação</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="profissao" class="form-label small fw-semibold">Profissão</label>
                    <input type="text" class="form-control form-control-sm" id="profissao" name="profissao" value="<?= esc(old('profissao')) ?>">
                </div>
                <div class="col-md-3">
                    <label for="ocupacao" class="form-label small fw-semibold">Ocupação</label>
                    <input type="text" class="form-control form-control-sm" id="ocupacao" name="ocupacao" value="<?= esc(old('ocupacao')) ?>">
                </div>
            </div>

            <!-- CONTATO -->
            <h6 class="text-primary fw-bold mb-2 mt-3"><i class="bi bi-telephone me-1"></i> Contato</h6>
            <hr class="my-2">
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label for="telefone" class="form-label small fw-semibold">Telefone</label>
                    <input type="text" class="form-control form-control-sm" id="telefone" name="telefone" value="<?= esc(old('telefone')) ?>" maxlength="20" placeholder="(00) 00000-0000">
                </div>
                <div class="col-md-4">
                    <label for="whatsapp" class="form-label small fw-semibold">WhatsApp</label>
                    <input type="text" class="form-control form-control-sm" id="whatsapp" name="whatsapp" value="<?= esc(old('whatsapp')) ?>" maxlength="20" placeholder="(00) 00000-0000">
                </div>
                <div class="col-md-4">
                    <label for="email_contato" class="form-label small fw-semibold">E-mail (usado para login) *</label>
                    <input type="email" class="form-control form-control-sm" id="email_contato" name="email" value="<?= esc(old('email')) ?>" required>
                </div>
            </div>

            <!-- ENDEREÇO -->
            <h6 class="text-primary fw-bold mb-2 mt-3"><i class="bi bi-geo-alt me-1"></i> Endereço</h6>
            <hr class="my-2">
            <div class="row g-2 mb-2">
                <div class="col-md-2">
                    <label for="cep" class="form-label small fw-semibold">CEP</label>
                    <input type="text" class="form-control form-control-sm" id="cep" name="cep" value="<?= esc(old('cep')) ?>" maxlength="10" placeholder="00000-000">
                </div>
                <div class="col-md-6">
                    <label for="logradouro" class="form-label small fw-semibold">Logradouro</label>
                    <input type="text" class="form-control form-control-sm" id="logradouro" name="logradouro" value="<?= esc(old('logradouro')) ?>">
                </div>
                <div class="col-md-2">
                    <label for="numero" class="form-label small fw-semibold">Número</label>
                    <input type="text" class="form-control form-control-sm" id="numero" name="numero" value="<?= esc(old('numero')) ?>">
                </div>
                <div class="col-md-2">
                    <label for="complemento" class="form-label small fw-semibold">Complemento</label>
                    <input type="text" class="form-control form-control-sm" id="complemento" name="complemento" value="<?= esc(old('complemento')) ?>">
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label for="bairro" class="form-label small fw-semibold">Bairro</label>
                    <input type="text" class="form-control form-control-sm" id="bairro" name="bairro" value="<?= esc(old('bairro')) ?>">
                </div>
                <div class="col-md-3">
                    <label for="municipio" class="form-label small fw-semibold">Município</label>
                    <input type="text" class="form-control form-control-sm" id="municipio" name="municipio" value="<?= esc(old('municipio')) ?>">
                </div>
                <div class="col-md-2">
                    <label for="estado_end" class="form-label small fw-semibold">Estado</label>
                    <select class="form-select form-select-sm" id="estado_end" name="estado">
                        <option value="">UF</option>
                        <?php $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO']; ?>
                        <?php foreach ($ufs as $uf): ?>
                            <option value="<?= $uf ?>" <?= old('estado') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="ponto_referencia" class="form-label small fw-semibold">Ponto de referência</label>
                    <input type="text" class="form-control form-control-sm" id="ponto_referencia" name="ponto_referencia" value="<?= esc(old('ponto_referencia')) ?>">
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label for="zona" class="form-label small fw-semibold">Zona</label>
                    <select class="form-select form-select-sm" id="zona" name="zona">
                        <option value="">Selecione</option>
                        <option value="urbana" <?= old('zona') === 'urbana' ? 'selected' : '' ?>>Urbana</option>
                        <option value="rural" <?= old('zona') === 'rural' ? 'selected' : '' ?>>Rural</option>
                    </select>
                </div>
            </div>

            <!-- INFORMAÇÕES ADICIONAIS -->
            <h6 class="text-primary fw-bold mb-2 mt-3"><i class="bi bi-info-circle me-1"></i> Informações Adicionais</h6>
            <hr class="my-2">
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="possui_filhos" name="possui_filhos" value="1" <?= old('possui_filhos') ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="possui_filhos">Possui filhos/dependentes</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="esta_gravida" name="esta_gravida" value="1" <?= old('esta_gravida') ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="esta_gravida">Está grávida</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="possui_deficiencia" name="possui_deficiencia" value="1" <?= old('possui_deficiencia') ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="possui_deficiencia">Possui deficiência</label>
                    </div>
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="e_idosa" name="e_idosa" value="1" <?= old('e_idosa') ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="e_idosa">É idosa</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="situacao_vulnerabilidade" name="situacao_vulnerabilidade" value="1" <?= old('situacao_vulnerabilidade') ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="situacao_vulnerabilidade">Situação de vulnerabilidade</label>
                    </div>
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-12">
                    <label for="observacoes" class="form-label small fw-semibold">Observações gerais</label>
                    <textarea class="form-control form-control-sm" id="observacoes" name="observacoes" rows="2"><?= esc(old('observacoes')) ?></textarea>
                </div>
            </div>

            <!-- DADOS DA CONTA -->
            <h6 class="text-primary fw-bold mb-2 mt-3"><i class="bi bi-shield-lock me-1"></i> Dados da Conta</h6>
            <hr class="my-2">
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label for="senha" class="form-label small fw-semibold">Senha *</label>
                    <input type="password" class="form-control form-control-sm" id="senha" name="senha" required minlength="6">
                    <div class="form-text">Mínimo de 6 caracteres.</div>
                </div>
                <div class="col-md-6">
                    <label for="confirma_senha" class="form-label small fw-semibold">Confirmar senha *</label>
                    <input type="password" class="form-control form-control-sm" id="confirma_senha" name="confirma_senha" required>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1" style="background: #a179c3; border-color: #a179c3;">
                    <i class="bi bi-check-circle me-1"></i> Criar conta
                </button>
                <a href="<?= base_url('login') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Voltar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara CPF
    const cpf = document.getElementById('cpf');
    if (cpf) {
        cpf.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '').substring(0, 11);
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = v;
        });
    }

    // Máscara CEP
    const cep = document.getElementById('cep');
    if (cep) {
        cep.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '').substring(0, 8);
            v = v.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = v;
        });
    }
});
</script>
<?= $this->endSection() ?>
