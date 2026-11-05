<?php

namespace App\Controllers;

use App\Models\AuditoriaModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Grava uma entrada na trilha de auditoria do sistema.
     *
     * Chamada de uma única linha nos controllers, sempre após a ação real já
     * ter sido persistida. O usuário autenticado é capturado da sessão por
     * padrao; IP/rota/método vêm automaticamente da requisição atual.
     */
    protected function auditar(
        string $acao,
        string $modulo,
        ?int $registroId = null,
        ?string $registroTipo = null,
        ?string $registroRef = null,
        array $informacoes = [],
        ?int $usuarioId = null
    ): bool {
        $usuarioId = $usuarioId ?? (session()->has('usuario_id') ? (int) session()->get('usuario_id') : null);

        return (new AuditoriaModel())->registrar(
            $acao,
            $modulo,
            $usuarioId,
            $registroTipo,
            $registroId,
            $registroRef,
            $informacoes
        );
    }
}
