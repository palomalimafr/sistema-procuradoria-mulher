# Sistema Modelo (Base de Desenvolvimento)

Projeto base construído a partir do **smartcac**, mantendo apenas a estrutura e os
padrões essenciais para servir de ponto de partida de novos sistemas:

- **Autenticação** (login/logout com sessão + filtros `auth` e `admin`)
- **Dashboard** com métricas simples
- **Um CRUD completo de exemplo** (Crianças + Responsáveis) para servir de referência
- **Módulo de Usuários** (restrito a administradores)
- **Backup automático do banco SQLite** (filtro + comando `spark backup:banco`)
- **Migrações, seeds e testes** configurados

## Stack

- PHP 8.2+
- CodeIgniter 4.7
- Banco de dados SQLite (arquivo em `writable/cac.db`)
- PHPUnit 10 para testes

## Instalação

```bash
composer install
cp env .env   # ou use o .env já incluído
```

O arquivo `.env` já vem configurado para desenvolvimento (SQLite em `writable/cac.db`).
Ajuste `app.baseURL` se necessário.

## Rodando

```bash
php spark serve
```

Acesse `http://localhost:8080/`.

### Banco de dados

```bash
# Criar as tabelas
php spark migrate

# Popular com dados de exemplo (admin + crianças)
php spark db:seed AdminSeeder
```

Usuários padrão criados pelo seeder:

| Perfil      | E-mail                   | Senha     |
|-------------|--------------------------|-----------|
| Admin       | admin@modelo.com.br      | admin123  |
| Funcionário | funcionario@modelo.com.br| func123   |

### Backup do banco

- Automático: roda via filtro global a cada `BACKUP_INTERVALO_HORAS` (padrão 24h), mantendo `BACKUP_RETENCAO` backups.
- Manual: `php spark backup:banco`
- Configuração em `app/Config/Backup.php` (sobrescrevível pelo `.env`).

### Testes

```bash
composer test
# ou
vendor/bin/phpunit
```

Os testes usam um banco SQLite em memória (`Config\Database::$tests`) e nunca tocam
nos dados reais.

## Estrutura e padrões

```
app/
├── Config/          # Configurações (Routes, Filters, Database, Backup, ...)
├── Controllers/     # Controllers estendem BaseController
│   ├── Auth.php     # Login/logout (sessão)
│   ├── Dashboard.php
│   ├── Criancas.php # Exemplo de CRUD completo
│   └── Usuarios.php # CRUD restrito ao perfil admin
├── Database/
│   ├── Migrations/  # Uma migração por tabela (YYYY-MM-DD-HHMMSS_Nome.php)
│   └── Seeds/       # AdminSeeder (dados de exemplo)
├── Filters/         # AuthFilter e AdminFilter (registrados em Config/Filters)
├── Libraries/       # BackupBanco (backup do SQLite via VACUUM INTO)
├── Models/          # Models do CodeIgniter (validação + timestamps)
└── Views/
    ├── layouts/     # admin.php (painel) e auth.php (login)
    ├── auth/        # login.php
    ├── criancas/    # CRUD de exemplo (index, form, detalhes)
    ├── dashboard/
    └── usuarios/
```

### Como criar um novo módulo (siga o padrão de Crianças)

1. **Migração** — crie a tabela em `app/Database/Migrations/`.
2. **Model** — crie em `app/Models/` seguindo `CriancaModel` (allowedFields, validação, timestamps).
3. **Controller** — crie em `app/Controllers/` estendendo `BaseController` com os métodos:
   `index` (lista paginada), `novo`/`criar`, `editar`/`atualizar`, `excluir`.
4. **Views** — crie a pasta em `app/Views/` com `index.php` (tabela + paginação),
   `form.php` (criar/editar) e, se necessário, `detalhes.php`.
5. **Rotas** — registre em `app/Config/Routes.php` dentro do grupo com filtro `auth`
   (e `admin` se for restrito).
6. **Menu** — adicione o item em `app/Views/layouts/admin.php` (`$menuSections`).
7. **Testes** — adicione casos em `tests/database/ModelsTest.php` e `tests/database/ControllersTest.php`.

### Convenções

- Controllers sempre retornam `redirect()->to(...)->with('sucesso'|'erro', ...)` após ações de escrita.
- Views de listagem usam `$pager->links()` (template em `app/Views/Pagers/cac_paginacao.php`).
- Feedback ao usuário via flashdata (`sucesso`, `erro`, `erros`) renderizado como toasts no layout.
- Confirmação de exclusão via atributo `data-confirm` nos links.
- Português (pt-BR) em toda a interface; classes e identificadores em inglês.
