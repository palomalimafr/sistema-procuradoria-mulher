<?php
// Probe: columnas reais das tabelas medidas (migration) e campos que detalhes.php/descumprimento.php usam
$root = 'C:/Users/Paloma Lima/Desktop/Projetos/procuradoria da mulher/app';
$migPath = glob($root . '/Database/Migrations/*Medidas*')[0] ?? null;
if (!$migPath) die("MIGRACAO NAO ENCONTRADA\n");
echo "migração: " . basename($migPath) . "\n\n";
$src = (string) file_get_contents($migPath);

// Extrair arrays de addField: colunas de cada tabela
preg_match_all("/\/\* create table\s+(\w+).*?\*\//s", $src, $tbls); // comentarios podem nao existir

// fallback: procurar java-style: addField([ ... createTable('name') ... 
// Simples: pegar todos os tokens 'coluna_name' => [...]
echo "=== colunas declaradas (via strings de chave seguido de => [ ) ===\n";
preg_match_all("/^\s*'([a-z0-9_]+)'\s*=>\s*\[/m", $src, $m1);
$cols = array_unique($m1[1]);
echo implode(', ', $cols) . "\n\n";

echo "=== createTable(...) nomes ===\n";
preg_match_all("/createTable\(\s*['\"]([a-z_]+)['\"]/", $src, $m2);
echo implode(', ', array_unique($m2[1])) . "\n\n";

// Chaves/FKs
preg_match_all("/'([a-z_]+)',\s*'CASCADE'/", $src, $m3);
echo "=== FKs com CASCADE ===\n";
echo implode(', ', array_unique($m3[1])) . "\n";
