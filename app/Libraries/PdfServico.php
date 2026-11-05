<?php

namespace App\Libraries;

use Config\Mimes;
use Dompdf\Dompdf;

/**
 * Serviço único de geração de PDF do sistema (Dompdf).
 *
 * Concentra a renderização A4 retrato, o rodapé paginado desenhado no canvas
 * (sem depender da extensão GD) e a incorporação do logo institucional como
 * data URI JPEG. É usado tanto pelo relatório geral de indicadores quanto pelo
 * relatório individual da mulher, de forma que o mecanismo de PDF não é
 * duplicado nos controllers.
 */
final class PdfServico
{
    /**
     * Renderiza um documento HTML em PDF (A4 retrato, fonte DejaVu Sans) e
     * devolve os bytes do arquivo, já com o rodapé identificando o sistema e a
     * paginação em todas as páginas.
     *
     * @param array<string, mixed> $config Configurações do sistema (nome_sistema
     *                                      e nome_procuradoria para o rodapé).
     */
    public function renderizar(string $html, array $config = []): string
    {
        $dompdf = new Dompdf();
        $dompdf->getOptions()->set('defaultFont', 'dejavu sans');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $this->desenharRodape($dompdf, $config);

        return $dompdf->output();
    }

    /**
     * Rodapé desenhado no canvas: identificação à esquerda e páginas à direita,
     * repetido em todas as páginas (sem depender da extensão GD).
     */
    private function desenharRodape(Dompdf $dompdf, array $config): void
    {
        $canvas      = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $fonte       = $fontMetrics->getFont('dejavu sans', 'normal');
        $corRodape   = [107, 114, 128]; // #6b7280
        $corLinha    = [201, 184, 222]; // #c9b8de
        $esq         = (string) ($config['nome_sistema'] ?? 'Sistema de Gestão')
            . ' · '
            . (string) ($config['nome_procuradoria'] ?? 'Procuradoria da Mulher');

        $canvas->page_line(40, 797, 556, 797, $corLinha, 0.6);
        $canvas->page_text(40, 805, $esq, $fonte, 8, $corRodape);
        $canvas->page_text(462, 805, 'Página {PAGE_NUM} de {PAGE_COUNT}', $fonte, 8, $corRodape);
    }

    /**
     * Logo a ser incorporado no PDF como data URI.
     *
     * Usa o logo institucional cadastrado nas configurações; na ausência dele,
     * cai para o logo padrão do projeto (public/assets/pwa/logo-roxa.jpeg).
     * A incorporação é limitada a JPEG: sem a extensão GD, o Dompdf só embute
     * JPEG de forma confiável (PNG/GIF/WEBP exigem GD). O cabeçalho do PDF
     * trata o retorno null exibindo apenas a marca em texto.
     */
    public function logoParaPdf(): ?string
    {
        $relativo = (new \App\Models\ConfiguracaoModel())->obter('logo');
        $caminho  = '';

        if ($relativo !== null && str_starts_with($relativo, 'logo/')) {
            $candidato = WRITEPATH . 'uploads/' . ltrim($relativo, '/');
            if (is_file($candidato)) {
                $caminho = $candidato;
            }
        }

        if ($caminho === '') {
            $padrao = ROOTPATH . 'public/assets/pwa/logo-roxa.jpeg';
            if (is_file($padrao)) {
                $caminho = $padrao;
            }
        }

        if ($caminho === '') {
            return null;
        }

        $mime = Mimes::guessTypeFromExtension(pathinfo($caminho, PATHINFO_EXTENSION)) ?? null;
        if (! is_string($mime) || ! str_starts_with(mb_strtolower($mime), 'image/jpeg')) {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents($caminho));
    }
}