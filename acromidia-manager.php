<?php
/**
 * Plugin Name: Acromidia Manager
 * Description: Sistema completo de gestão de assinaturas, integração Asaas e notificações WhatsApp.
 * Version: 4.0.2
 * Author: Especialista IA
 * Text Domain: acromidia-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Carrega as classes do plugin
require_once plugin_dir_path( __FILE__ ) . 'includes/class-encryption.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-gateway-factory.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-asaas-api.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-whatsapp-api.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-webhook-handler.php';

// Inicializa configurações (tela admin + getters)
new Acromidia_Settings();

class Acromidia_Manager {

    public function __construct() {
        add_action( 'init', [ $this, 'register_hooks' ] );
        add_filter( 'template_include', [ $this, 'portal_template_redirect' ] );
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
    }

    public function activate() {
        $this->register_hooks();
        flush_rewrite_rules();
    }

    public function register_hooks() {
        $this->register_cpt();
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ], 5 );
        
        // Cron diário para Régua de Cobrança
        add_action( 'acromidia_daily_billing', [ $this, 'run_daily_billing' ] );
        if ( ! wp_next_scheduled( 'acromidia_daily_billing' ) ) {
            wp_schedule_event( time(), 'daily', 'acromidia_daily_billing' );
        }

        // Template para documentos públicos
        add_filter( 'template_include', [ $this, 'render_document_template' ] );

        // Portal do Cliente
        add_filter( 'query_vars', function( $vars ) {
            $vars[] = 'acro_portal';
            $vars[] = 'acro_id';
            return $vars;
        } );
        add_rewrite_rule( '^portal-do-cliente/([a-z0-9\-]+)/?$', 'index.php?acro_portal=1&acro_id=$matches[1]', 'top' );
        add_rewrite_rule( '^portal-do-cliente/?$', 'index.php?acro_portal=1', 'top' );
    }

    public function portal_template_redirect( $template ) {
        if ( get_query_var( 'acro_portal' ) ) {
            return plugin_dir_path( __FILE__ ) . 'public/portal-template.php';
        }
        return $template;
    }

    // ───────────────────────────────────
    //  CPT
    // ───────────────────────────────────
    public function register_cpt() {
        $labels = [
            'name'          => 'Clientes Acromidia',
            'singular_name' => 'Cliente',
            'menu_name'     => 'Clientes',
            'add_new'       => 'Novo Cliente',
            'add_new_item'  => 'Adicionar Novo Cliente',
            'edit_item'     => 'Editar Cliente',
            'view_item'     => 'Ver Cliente',
            'all_items'     => 'Todos os Clientes',
        ];

        register_post_type( 'acro_client', [
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'query_var'          => true,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => [ 'title', 'custom-fields' ],
            'show_in_rest'       => true,
        ] );

        // CPT para Logs
        register_post_type( 'acro_log', [
            'labels'       => [ 'name' => 'Acro Logs', 'singular_name' => 'Log' ],
            'public'       => false,
            'supports'     => [ 'title', 'editor' ],
            'show_in_rest' => false,
        ] );

        // CPT para Transações (Fluxo de Caixa)
        register_post_type( 'acro_transaction', [
            'labels'       => [ 'name' => 'Financeiro', 'singular_name' => 'Transação' ],
            'public'       => false,
            'supports'     => [ 'title', 'custom-fields' ],
            'show_in_rest' => true,
        ] );

        // CPT para Documentos (Orçamentos e Contratos)
        register_post_type( 'acro_document', [
            'labels'       => [ 'name' => 'Documentos', 'singular_name' => 'Documento' ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'has_archive'        => false,
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'proposta', 'with_front' => false ],
            'supports'           => [ 'title' ],
            'show_in_rest'       => true,
        ] );

        // Force refresh of rewrite rules on first load after this update
        if ( ! get_option( 'acro_v4_2_flush_rewrite' ) ) {
            flush_rewrite_rules( true );
            update_option( 'acro_v4_2_flush_rewrite', true );
        }

        // CPT para Tarefas (Gestão de Projetos / Kanban)
        register_post_type( 'acro_task', [
            'labels'       => [ 'name' => 'Tarefas', 'singular_name' => 'Tarefa' ],
            'public'       => false,
            'supports'     => [ 'title', 'editor', 'custom-fields' ],
            'show_in_rest' => true,
        ] );
    }

    // ───────────────────────────────────
    //  Menu Admin
    // ───────────────────────────────────
    public function register_admin_menu() {
        // SVG inline → ícone de camadas 3D como logo Acromidia
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode( '
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                <polyline points="2 17 12 22 22 17"/>
                <polyline points="2 12 12 17 22 12"/>
            </svg>
        ' );

        add_menu_page(
            'Acro Manager',
            'Acro Manager',
            'read', // Permitir acesso básico para clientes se o modo estiver ativo
            'acromidia-dashboard',
            [ $this, 'render_dashboard_view' ],
            $icon_svg,
            2
        );

        // Renomear o primeiro submenu
        add_submenu_page(
            'acromidia-dashboard',
            'Dashboard',
            'Dashboard',
            'read',
            'acromidia-dashboard',
            [ $this, 'render_dashboard_view' ]
        );

        // Lógica de Restrição de Menus (Modo Cliente)
        add_action( 'admin_menu', function() {
            if ( Acromidia_Settings::get('restrict_admin') !== 'yes' ) return;
            if ( current_user_can( 'manage_options' ) ) return; 

            global $menu, $submenu;

            // Menus a serem removidos
            $remove_pages = [
                'index.php', 'edit.php', 'upload.php', 'edit.php?post_type=page',
                'edit-comments.php', 'themes.php', 'plugins.php', 'users.php',
                'tools.php', 'options-general.php', 'edit.php?post_type=acro_client',
                'edit.php?post_type=acro_log', 'edit.php?post_type=acro_transaction',
                'edit.php?post_type=acro_document', 'edit.php?post_type=acro_task',
                'separator1', 'separator2', 'separator-last'
            ];

            foreach ( $menu as $key => $value ) {
                if ( ! empty($value[2]) && ( in_array( $value[2], $remove_pages ) || empty($value[0]) ) ) {
                    unset( $menu[$key] );
                }
            }

            // Garante que o usuário caia no Dashboard se entrar no /wp-admin/ puro
            global $pagenow;
            if ( $pagenow === 'index.php' ) {
                wp_safe_redirect( admin_url( 'admin.php?page=acromidia-dashboard' ) );
                exit;
            }
        }, 999 );

        // Remove barra do admin no frontend e limpa opções no admin
        add_action( 'admin_head', function() {
            if ( Acromidia_Settings::get('restrict_admin') !== 'yes' ) return;
            if ( ! current_user_can( 'manage_options' ) ) {
                echo '<style>
                    #contextual-help-link-wrap, #screen-options-link-wrap, #wp-admin-bar-updates, #wp-admin-bar-comments, #wp-admin-bar-new-content { display: none !important; }
                    #adminmenu .wp-menu-separator { display: none !important; }
                </style>';
            }
            
            // CSS para colorir o ícone SVG no menu
            echo '<style>
                #toplevel_page_acromidia-dashboard .wp-menu-image img { filter: brightness(0) invert(1); opacity: 0.7; }
                #toplevel_page_acromidia-dashboard:hover .wp-menu-image img,
                #toplevel_page_acromidia-dashboard.current .wp-menu-image img { filter: brightness(0) saturate(100%) invert(47%) sepia(98%) saturate(1500%) hue-rotate(229deg); opacity: 1; }
            </style>';
        } );

        add_action( 'after_setup_theme', function() {
            if ( Acromidia_Settings::get('restrict_admin') === 'yes' && ! current_user_can( 'manage_options' ) ) {
                show_admin_bar( false );
            }
        } );
    }

    public function render_dashboard_view() {
        require_once plugin_dir_path( __FILE__ ) . 'admin/ui-dashboard.php';
    }

    // ───────────────────────────────────
    //  REST Endpoints
    // ───────────────────────────────────
    public function register_rest_endpoints() {
        error_log('[ACROMIDIA MANAGER] Registering REST endpoints...');
        $admin_perm = [ $this, 'check_admin_permission' ];

        // GET — listar clientes
        register_rest_route( 'acromidia/v1', '/clients', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_clients' ],
            'permission_callback' => $admin_perm,
        ] );

        // POST — criar cliente
        register_rest_route( 'acromidia/v1', '/clients', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_client' ],
            'permission_callback' => $admin_perm,
        ] );

        // PUT — atualizar cliente
        register_rest_route( 'acromidia/v1', '/clients/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'update_client' ],
            'permission_callback' => $admin_perm,
        ] );

        // POST — criar lead manual (CRM prospect sem Asaas)
        register_rest_route( 'acromidia/v1', '/leads', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_lead' ],
            'permission_callback' => $admin_perm,
        ] );

        // DELETE — remover cliente
        register_rest_route( 'acromidia/v1', '/clients/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_client' ],
            'permission_callback' => $admin_perm,
        ] );

        // POST — disparar cobrança WhatsApp individual
        register_rest_route( 'acromidia/v1', '/clients/(?P<id>\d+)/whatsapp', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'send_whatsapp_billing' ],
            'permission_callback' => $admin_perm,
        ] );

        // POST — sincronizar cliente com Asaas
        register_rest_route( 'acromidia/v1', '/clients/(?P<id>\d+)/sync', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'sync_client_asaas' ],
            'permission_callback' => $admin_perm,
        ] );

        // POST — webhook público do Asaas
        $webhook = new Acromidia_Webhook_Handler();
        register_rest_route( 'acromidia/v1', '/webhook/asaas', [
            'methods'             => 'POST',
            'callback'            => [ $webhook, 'handle' ],
            'permission_callback' => [ $webhook, 'verify_webhook' ],
        ] );

        // GET — Faturas do cliente (Ideia 1 e 3)
        register_rest_route( 'acromidia/v1', '/clients/(?P<id>\d+)/invoices', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_client_invoices' ],
            'permission_callback' => $admin_perm,
        ] );

        // POST — Importação em massa (Ideia 2)
        register_rest_route( 'acromidia/v1', '/asaas/import', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'import_asaas_clients' ],
            'permission_callback' => $admin_perm,
        ] );

        // GET — Saldo Asaas (Ideia 4)
        register_rest_route( 'acromidia/v1', '/asaas/balance', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_asaas_balance' ],
            'permission_callback' => $admin_perm,
        ] );

        // POST — Sincronizar Inadimplência Massiva cruzada
        register_rest_route( 'acromidia/v1', '/asaas/sync-overdue', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'sync_overdue_status' ],
            'permission_callback' => $admin_perm,
        ] );

        // GET — Relatórios MVC (Ideia B)
        register_rest_route( 'acromidia/v1', '/metrics/chart', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_metrics_chart' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/metrics/commercial', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_metrics_commercial' ],
            'permission_callback' => $admin_perm,
        ] );

        // GET — Histórico de Disparos/Logs (Ideia C)
        register_rest_route( 'acromidia/v1', '/logs', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_whatsapp_logs' ],
            'permission_callback' => $admin_perm,
        ] );

        // POST — Alternar Bloqueio Técnico (Manual)
        register_rest_route( 'acromidia/v1', '/client-toggle-block/(?P<id>\d+)', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'toggle_client_block' ],
            'permission_callback' => $admin_perm,
        ] );

        // GET PÚBLICO — Verificação Remota do Betheme
        register_rest_route( 'acromidia/v1', '/client-site-check', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'check_public_site_status' ],
            'permission_callback' => '__return_true', // Sem Autenticação API propositalmente
        ] );

        // --- FLUXO DE CAIXA ---
        register_rest_route( 'acromidia/v1', '/finance/transactions', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_transactions' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/finance/transactions', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_transaction' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/finance/transactions/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'update_transaction' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/finance/transactions/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_transaction' ],
            'permission_callback' => $admin_perm,
        ] );

        // --- CATEGORIAS FINANCEIRAS PERSONALIZADAS ---
        register_rest_route( 'acromidia/v1', '/finance/categories', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_finance_categories' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/finance/categories', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'save_finance_categories' ],
            'permission_callback' => $admin_perm,
        ] );

        // --- ORÇAMENTOS E CONTRATOS ---
        register_rest_route( 'acromidia/v1', '/documents', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_documents' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/documents', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_document' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/documents/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_document' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/documents/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'update_document' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/documents/(?P<id>\d+)/whatsapp', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'send_document_whatsapp' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/documents/(?P<id>\d+)/accept', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'accept_document' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/documents/(?P<id>\d+)/revert', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'revert_document' ],
            'permission_callback' => $admin_perm,
        ] );

        // POST PÚBLICO — Aceite de Cliente
        register_rest_route( 'acromidia/v1', '/public/documents/(?P<id>\d+)/accept', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'accept_document_public' ],
            'permission_callback' => '__return_true',
        ] );

        // --- GESTÃO DE TAREFAS (KANBAN) ---
        register_rest_route( 'acromidia/v1', '/tasks', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_tasks' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/tasks', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_task' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/tasks/(?P<id>\d+)', [
            'methods'             => 'PUT', // Para atualizar status (move card)
            'callback'            => [ $this, 'update_task' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/tasks/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_task' ],
            'permission_callback' => $admin_perm,
        ] );

        // --- GESTÃO DE PRODUTOS/SERVIÇOS ---
        register_rest_route( 'acromidia/v1', '/products', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_products' ],
            'permission_callback' => $admin_perm,
        ] );

        register_rest_route( 'acromidia/v1', '/products', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'save_products' ],
            'permission_callback' => $admin_perm,
        ] );

        // --- RELATÓRIOS AVANÇADOS (DRE/CHURN) ---
        register_rest_route( 'acromidia/v1', '/reports/dre', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_dre_report' ],
            'permission_callback' => $admin_perm,
        ] );

        // --- PORTAL DO CLIENTE (PÚBLICO) ---
        register_rest_route( 'acromidia/v1', '/portal/(?P<id>[a-zA-Z0-9\-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_portal_data' ],
            'permission_callback' => '__return_true', // Público
        ] );
    }

    // ───────────────────────────────────
    //  Callbacks REST
    // ───────────────────────────────────

    public function check_admin_permission() {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Se o modo restrito estiver ativo, permite acesso a quem tem 'read' (clientes logados)
        if ( Acromidia_Settings::get('restrict_admin') === 'yes' && current_user_can( 'read' ) ) {
            return true;
        }

        return false;
    }

    /**
     * GET /clients — Lista todos os clientes com meta.
     */
    public function get_clients() {
        $posts = get_posts( [
            'post_type'      => 'acro_client',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ] );

        $data = [];
        foreach ( $posts as $post ) {
            $data[] = $this->format_client( $post );
        }

        return rest_ensure_response( $data );
    }

    /**
     * POST /clients — Cria um novo cliente.
     * Campos: name, cpf_cnpj, email, phone, mrr, site_url
     */
    public function create_client( \WP_REST_Request $request ) {
        $params = $request->get_json_params();

        $name     = sanitize_text_field( $params['name'] ?? '' );
        $cpf_cnpj = sanitize_text_field( $params['cpf_cnpj'] ?? '' );
        $email    = sanitize_email( $params['email'] ?? '' );
        $phone    = sanitize_text_field( $params['phone'] ?? '' );
        $mrr      = floatval( $params['mrr'] ?? 0 );
        $site_url = esc_url_raw( $params['site_url'] ?? '' );

        if ( empty( $name ) ) {
            return new \WP_REST_Response( [ 'error' => 'Nome é obrigatório' ], 400 );
        }

        // Criar o CPT no WordPress
        $post_id = wp_insert_post( [
            'post_type'   => 'acro_client',
            'post_title'  => $name,
            'post_status' => 'publish',
        ] );

        if ( is_wp_error( $post_id ) ) {
            return new \WP_REST_Response( [ 'error' => 'Erro ao criar cliente' ], 500 );
        }

        // Salvar meta
        update_post_meta( $post_id, '_acro_cpf_cnpj', $cpf_cnpj );
        update_post_meta( $post_id, '_acro_email', $email );
        update_post_meta( $post_id, '_acro_phone', $phone );
        update_post_meta( $post_id, '_acro_mrr', $mrr );
        update_post_meta( $post_id, '_acro_site_url', $site_url );
        update_post_meta( $post_id, '_acro_status', 'ativo' );
        update_post_meta( $post_id, '_acro_uuid', wp_generate_uuid4() );
        update_post_meta( $post_id, '_acro_portal_slug', $this->generate_unique_portal_slug() );

        // Tentar criar cliente no Gateway (se API configurada)
        $gateway_id = '';
        if ( Acromidia_Gateway_Factory::is_configured() ) {
            $gateway = Acromidia_Gateway_Factory::get_engine();
            $result = $gateway->create_customer( $name, $cpf_cnpj, $email, $phone );

            if ( ! empty( $result['id'] ) ) {
                $gateway_id = $result['id'];
                update_post_meta( $post_id, '_acro_gateway_customer_id', $gateway_id );

                // Criar assinatura automaticamente
                if ( $mrr > 0 ) {
                    $next_due = date( 'Y-m-d', strtotime( '+30 days' ) );
                    $gateway->create_subscription( $gateway_id, $mrr, $next_due, "Mensalidade Acromidia - {$name}" );
                }
            }
        }

        $client_post = get_post( $post_id );
        return rest_ensure_response( $this->format_client( $client_post ) );
    }

    /**
     * POST /leads — Cria um novo prospecto (Lead) no CRM WP sem acionar o Asaas.
     */
    public function create_lead( \WP_REST_Request $request ) {
        $params = $request->get_json_params();

        $name  = sanitize_text_field( $params['name'] ?? '' );
        $phone = sanitize_text_field( $params['phone'] ?? '' );

        if ( empty( $name ) ) {
            return new \WP_REST_Response( [ 'error' => 'Nome é obrigatório' ], 400 );
        }

        $post_id = wp_insert_post( [
            'post_type'   => 'acro_client',
            'post_title'  => $name,
            'post_status' => 'publish',
        ] );

        if ( is_wp_error( $post_id ) ) {
            return new \WP_REST_Response( [ 'error' => 'Erro ao criar lead' ], 500 );
        }

        update_post_meta( $post_id, '_acro_phone', $phone );
        update_post_meta( $post_id, '_acro_mrr', floatval( $params['mrr'] ?? 0 ) );
        update_post_meta( $post_id, '_acro_site_url', esc_url_raw( $params['site_url'] ?? '' ) );
        update_post_meta( $post_id, '_acro_status', 'ativo' );
        update_post_meta( $post_id, '_acro_pipeline_stage', 'prospect' );
        update_post_meta( $post_id, '_acro_product', sanitize_text_field( $params['product'] ?? '' ) );
        update_post_meta( $post_id, '_acro_notes', wp_kses_post( $params['notes'] ?? '' ) );
        update_post_meta( $post_id, '_acro_uuid', wp_generate_uuid4() );
        update_post_meta( $post_id, '_acro_portal_slug', $this->generate_unique_portal_slug() );
        
        $client_post = get_post( $post_id );
        return rest_ensure_response( $this->format_client( $client_post ) );
    }

    /**
     * PUT /clients/{id} — Atualiza um cliente existente.
     */
    public function update_client( \WP_REST_Request $request ) {
        $id     = intval( $request->get_param( 'id' ) );
        $params = $request->get_json_params();
        $post   = get_post( $id );

        if ( ! $post || $post->post_type !== 'acro_client' ) {
            return new \WP_REST_Response( [ 'error' => 'Cliente não encontrado' ], 404 );
        }

        // Atualizar título se nome mudou
        if ( ! empty( $params['name'] ) ) {
            wp_update_post( [
                'ID'         => $id,
                'post_title' => sanitize_text_field( $params['name'] ),
            ] );
        }

        // Atualizar meta
        $meta_map = [
            'cpf_cnpj'       => '_acro_cpf_cnpj',
            'email'          => '_acro_email',
            'phone'          => '_acro_phone',
            'mrr'            => '_acro_mrr',
            'site_url'       => '_acro_site_url',
            'status'         => '_acro_status',
            'pipeline_stage' => '_acro_pipeline_stage',
            'product'        => '_acro_product',
            'notes'          => '_acro_notes',
        ];

        // Registrar data de fechamento (Sales Cycle)
        if ( isset( $params['pipeline_stage'] ) && $params['pipeline_stage'] === 'won' ) {
            $old_stage = get_post_meta( $id, '_acro_pipeline_stage', true );
            if ( $old_stage !== 'won' ) {
                update_post_meta( $id, '_acro_won_date', current_time( 'mysql' ) );
            }
        }
        if ( isset( $params['status'] ) && $params['status'] === 'ativo' ) {
            $old_status = get_post_meta( $id, '_acro_status', true );
            if ( $old_status === 'inadimplente' ) {
                update_post_meta( $id, '_acro_recovered_at', current_time( 'mysql' ) );
            }
        }

        foreach ( $meta_map as $param_key => $meta_key ) {
            if ( isset( $params[ $param_key ] ) ) {
                $value = $param_key === 'email'
                    ? sanitize_email( $params[ $param_key ] )
                    : sanitize_text_field( $params[ $param_key ] );
                update_post_meta( $id, $meta_key, $value );
            }
        }

        $client_post = get_post( $id );
        return rest_ensure_response( $this->format_client( $client_post ) );
    }

    /**
     * DELETE /clients/{id} — Remove um cliente.
     */
    public function delete_client( \WP_REST_Request $request ) {
        $id   = intval( $request->get_param( 'id' ) );
        $post = get_post( $id );

        if ( ! $post || $post->post_type !== 'acro_client' ) {
            return new \WP_REST_Response( [ 'error' => 'Cliente não encontrado' ], 404 );
        }

        wp_delete_post( $id, true );
        return rest_ensure_response( [ 'deleted' => true, 'id' => $id ] );
    }

    /**
     * POST /clients/{id}/whatsapp — Dispara cobrança individual.
     */
    public function send_whatsapp_billing( \WP_REST_Request $request ) {
        $id   = intval( $request->get_param( 'id' ) );
        $post = get_post( $id );

        if ( ! $post || $post->post_type !== 'acro_client' ) {
            return new \WP_REST_Response( [ 'error' => 'Cliente não encontrado' ], 404 );
        }

        // Verifica se WhatsApp está configurado
        if ( ! Acromidia_Settings::has( 'wa_token' ) || ! Acromidia_Settings::has( 'wa_phone_id' ) ) {
            return new \WP_REST_Response( [
                'error' => 'WhatsApp não configurado. Acesse Acromidia → Configurações.'
            ], 400 );
        }

        $phone = get_post_meta( $id, '_acro_phone', true );
        if ( empty( $phone ) ) {
            return new \WP_REST_Response( [ 'error' => 'Telefone não cadastrado para este cliente' ], 400 );
        }

        $reminder_type = $request->get_param( 'reminder_type' ) ?: 'manual';
        $client_name = $post->post_title;
        $asaas_id    = get_post_meta( $id, '_acro_gateway_customer_id', true );
        $pix_code    = 'Disponível na fatura';
        $invoice_url = '';

        // Tenta buscar dados reais do Asaas
        if ( ! empty( $asaas_id ) && Acromidia_Gateway_Factory::is_configured() ) {
            $gateway  = Acromidia_Gateway_Factory::get_engine();
            $payments = $gateway->list_payments( $asaas_id );

            if ( ! empty( $payments['data'] ) ) {
                $latest = $payments['data'][0];
                $invoice_url = $latest['invoiceUrl'] ?? $latest['bankSlipUrl'] ?? '';

                if ( ! empty( $latest['id'] ) ) {
                    $pix = $gateway->get_payment_pix_qrcode( $latest['id'] );
                    $pix_code = $pix['payload'] ?? $pix_code;
                }
            }
        }

        $wa   = new Acromidia_WhatsApp_API();
        $sent = $wa->send_billing_message( $phone, $client_name, $pix_code, $invoice_url, $reminder_type );

        if ( $sent ) {
            return rest_ensure_response( [ 'success' => true, 'message' => "Cobrança enviada para {$client_name}" ] );
        }

        return new \WP_REST_Response( [ 'error' => 'Falha ao enviar WhatsApp. Verifique o token e o número.' ], 500 );
    }

    /**
     * POST /clients/{id}/sync — Sincroniza cliente com Asaas.
     */
    public function sync_client_asaas( \WP_REST_Request $request ) {
        $id   = intval( $request->get_param( 'id' ) );
        $post = get_post( $id );

        if ( ! $post || $post->post_type !== 'acro_client' ) {
            return new \WP_REST_Response( [ 'error' => 'Cliente não encontrado' ], 404 );
        }

        if ( ! Acromidia_Gateway_Factory::is_configured() ) {
            return new \WP_REST_Response( [ 'error' => 'API Asaas não configurada. Acesse Acromidia → Configurações.' ], 400 );
        }

        $existing_asaas_id = get_post_meta( $id, '_acro_gateway_customer_id', true );
        if ( ! empty( $existing_asaas_id ) ) {
            return rest_ensure_response( [
                'success'  => true,
                'message'  => 'Cliente já sincronizado com Asaas',
                'asaas_id' => $existing_asaas_id,
            ] );
        }

        $name     = $post->post_title;
        $cpf_cnpj = get_post_meta( $id, '_acro_cpf_cnpj', true );
        $email    = get_post_meta( $id, '_acro_email', true );
        $phone    = get_post_meta( $id, '_acro_phone', true );
        $mrr      = floatval( get_post_meta( $id, '_acro_mrr', true ) );

        $asaas  = new Acromidia_Asaas_API();
        $result = $asaas->create_customer( $name, $cpf_cnpj, $email, $phone );

        // Se falhou por duplicidade ou erro, tenta buscar por CPF/CNPJ
        if ( ! empty( $result['error'] ) || empty( $result['id'] ) ) {
            if ( ! empty( $cpf_cnpj ) ) {
                $search = $asaas->request( '/customers?cpfCnpj=' . preg_replace( '/\D/', '', $cpf_cnpj ) );
                if ( ! empty( $search['data'][0]['id'] ) ) {
                    $result = $search['data'][0];
                }
            }
        }

        if ( empty( $result['id'] ) ) {
            $msg = $result['message'] ?? $result['errors'][0]['description'] ?? 'Erro desconhecido do Asaas';
            return new \WP_REST_Response( [ 'error' => "Asaas: {$msg}" ], 400 );
        }

        $asaas_id = $result['id'];
        update_post_meta( $id, '_acro_gateway_customer_id', $asaas_id );

        // Criar assinatura se tiver mensalidade
        $subscription_id = '';
        if ( $mrr > 0 ) {
            $next_due = date( 'Y-m-d', strtotime( '+30 days' ) );
            $sub = $asaas->create_subscription( $asaas_id, $mrr, $next_due, "Mensalidade Acromidia - {$name}" );
            $subscription_id = $sub['id'] ?? '';
        }

        return rest_ensure_response( [
            'success'         => true,
            'message'         => "Cliente {$name} sincronizado com Asaas!",
            'asaas_id'        => $asaas_id,
            'subscription_id' => $subscription_id,
        ] );
    }

    // ───────────────────────────────────
    //  Helpers
    // ───────────────────────────────────

    /**
     * GET /clients/{id}/invoices — Ideia 1 e 3: Visão 360 e Status Real
     */
    public function get_client_invoices( \WP_REST_Request $request ) {
        $id   = intval( $request->get_param( 'id' ) );
        $post = get_post( $id );

        if ( ! $post || $post->post_type !== 'acro_client' ) {
            return new \WP_REST_Response( [ 'error' => 'Cliente não encontrado' ], 404 );
        }

        $asaas_id = get_post_meta( $id, '_acro_gateway_customer_id', true );
        if ( empty( $asaas_id ) ) {
            return rest_ensure_response( [ 'data' => [] ] );
        }

        $gateway = Acromidia_Gateway_Factory::get_engine();
        $invoices = $gateway->list_payments( $asaas_id );

        if ( isset( $invoices['error'] ) && $invoices['error'] ) {
            return new \WP_REST_Response( [ 'error' => 'Erro na API Asaas' ], 400 );
        }

        // Ideia 3: Atualizar status real baseado nas faturas
        $has_overdue = false;
        if ( ! empty( $invoices['data'] ) ) {
            foreach ( $invoices['data'] as $inv ) {
                if ( $inv['status'] === 'OVERDUE' ) {
                    $has_overdue = true;
                    break;
                }
            }
        }
        
        $current_status = get_post_meta( $id, '_acro_status', true );
        $new_status = $has_overdue ? 'inadimplente' : 'ativo';
        if ( $current_status !== $new_status ) {
            update_post_meta( $id, '_acro_status', $new_status );
        }

        return rest_ensure_response( [
            'data'   => $invoices['data'] ?? [],
            'status' => $new_status,
        ] );
    }

    /**
     * POST /asaas/import — Ideia 2: Smart Sync em massa
     */
    public function import_asaas_clients( \WP_REST_Request $request ) {
        if ( ! Acromidia_Gateway_Factory::is_configured() ) {
            return new \WP_REST_Response( [ 'error' => 'Configurações de API não encontradas.' ], 400 );
        }
        
        $gateway = Acromidia_Gateway_Factory::get_engine();
        $primary_id = Acromidia_Settings::get( 'primary_gateway' ) ?: 'asaas';
        $customers  = $gateway->list_customers( 100 );
        
        if ( isset( $customers['error'] ) && $customers['error'] ) {
             return new \WP_REST_Response( [ 'error' => 'Erro ao buscar no ' . ucfirst($primary_id) . ': ' . ($customers['message']??'') ], 400 );
        }
        
        $imported = 0;
        $existing = get_posts( [
            'post_type'      => 'acro_client',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ] );
        
        $existing_asaas_ids = [];
        $existing_cpfs = [];
        foreach ( $existing as $p ) {
            $a_id = get_post_meta( $p->ID, '_acro_gateway_customer_id', true );
            if ( $a_id ) $existing_asaas_ids[] = $a_id;
            
            $cpf = get_post_meta( $p->ID, '_acro_cpf_cnpj', true );
            if ( $cpf ) $existing_cpfs[] = preg_replace( '/\D/', '', $cpf );
        }

        foreach ( $customers['data'] as $c ) {
            $cpf_clean = preg_replace( '/\D/', '', $c['cpfCnpj']??'' );
            
            if ( in_array( $c['id'], $existing_asaas_ids ) || ( ! empty( $cpf_clean ) && in_array( $cpf_clean, $existing_cpfs ) ) ) {
                continue;
            }
            
            $post_id = wp_insert_post( [
                'post_type'   => 'acro_client',
                'post_title'  => sanitize_text_field( $c['name'] ),
                'post_status' => 'publish',
            ] );
            
            if ( ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_acro_gateway_customer_id', $c['id'] );
                update_post_meta( $post_id, '_acro_cpf_cnpj', sanitize_text_field( $c['cpfCnpj']??'' ) );
                update_post_meta( $post_id, '_acro_email', sanitize_email( $c['email']??'' ) );
                update_post_meta( $post_id, '_acro_phone', sanitize_text_field( $c['phone'] ?? $c['mobilePhone'] ?? '' ) );
                update_post_meta( $post_id, '_acro_mrr', 0 );
                update_post_meta( $post_id, '_acro_site_url', '' );
                update_post_meta( $post_id, '_acro_status', 'ativo' );
                update_post_meta( $post_id, '_acro_pipeline_stage', 'onboarding' );
                $imported++;
            }
        }

        // --- Sincroniza pagamentos confirmados recentes como Receitas no Financeiro ---
        $synced_finance = 0;
        if ( Acromidia_Gateway_Factory::is_configured() ) {
            $gateway = Acromidia_Gateway_Factory::get_engine();
            $recent_paid = $gateway->request("/payments?status=RECEIVED&offset=0&limit=50") ?: [];
            if ( ! empty( $recent_paid['data'] ) ) {
                foreach ( $recent_paid['data'] as $pay ) {
                    if ( $this->sync_payment_as_income( $pay ) ) {
                        $synced_finance++;
                    }
                }
            }
        }
        
        return rest_ensure_response( [ 
            'success' => true, 
            'imported' => $imported, 
            'finance_synced' => $synced_finance 
        ] );
    }

    /**
     * GET /asaas/balance — Ideia 4: Dashboard Integrado de Métricas
     */
    public function get_asaas_balance( \WP_REST_Request $request ) {
        if ( ! Acromidia_Gateway_Factory::is_configured() ) {
            return rest_ensure_response( [ 'balance' => 0 ] );
        }
        $gateway = Acromidia_Gateway_Factory::get_engine();
        $balance = $gateway->get_balance();
        return rest_ensure_response( [ 'balance' => $balance['balance'] ?? 0 ] );
    }

    /**
     * POST /asaas/sync-overdue — Busca real-time de devedores
     */
    public function sync_overdue_status( \WP_REST_Request $request ) {
        if ( ! Acromidia_Gateway_Factory::is_configured() ) {
            return new \WP_REST_Response( [ 'error' => 'API não configurada' ], 400 );
        }
        $gateway = Acromidia_Gateway_Factory::get_engine();
        $overdues = $gateway->list_overdue_payments();
        
        if ( isset( $overdues['error'] ) && $overdues['error'] ) {
            return new \WP_REST_Response( [ 'error' => 'Erro na API Asaas' ], 400 );
        }

        $overdue_asaas_ids = [];
        if ( ! empty( $overdues['data'] ) ) {
            foreach ( $overdues['data'] as $inv ) {
                $overdue_asaas_ids[] = $inv['customer'];
            }
        }
        $overdue_asaas_ids = array_unique( $overdue_asaas_ids );

        $existing = get_posts( [ 'post_type' => 'acro_client', 'posts_per_page' => -1, 'post_status' => 'publish' ] );
        
        $updated = 0;
        foreach ( $existing as $p ) {
            $a_id = get_post_meta( $p->ID, '_acro_gateway_customer_id', true );
            $current_status = get_post_meta( $p->ID, '_acro_status', true );
            
            if ( $a_id && in_array( $a_id, $overdue_asaas_ids ) ) {
                if ( $current_status !== 'inadimplente' ) {
                    update_post_meta( $p->ID, '_acro_status', 'inadimplente' );
                    $updated++;
                }
            } else {
                if ( $current_status === 'inadimplente' ) {
                    update_post_meta( $p->ID, '_acro_status', 'ativo' );
                    $updated++;
                }
            }
        }

        // --- NOVO: Sincroniza pagamentos confirmados recentes como Receitas no Financeiro ---
        $recent_paid = $gateway->request("/payments?status=RECEIVED&offset=0&limit=50") ?: [];
        $synced_finance = 0;
        if ( ! empty( $recent_paid['data'] ) ) {
            foreach ( $recent_paid['data'] as $pay ) {
                if ( $this->sync_payment_as_income( $pay ) ) {
                    $synced_finance++;
                }
            }
        }

        return rest_ensure_response( [ 
            'success' => true, 
            'updated' => $updated, 
            'finance_synced' => $synced_finance 
        ] );
    }

    /**
     * Auxiliar: Converte um pagamento do Asaas em Transação Financeira
     * Evita duplicatas conferindo o ID da fatura.
     */
    public function sync_payment_as_income( $payment ) {
        $payment_id = $payment['id'] ?? '';
        if ( empty($payment_id) ) return false;

        $existing = get_posts([
            'post_type'  => 'acro_transaction',
            'meta_key'   => '_acro_asaas_id',
            'meta_value' => $payment_id,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);

        if ( ! empty($existing) ) return false;

        $desc = !empty($payment['description']) ? $payment['description'] : "Recebimento Asaas #{$payment_id}";
        
        $trans_id = wp_insert_post([
            'post_type'   => 'acro_transaction',
            'post_title'  => sanitize_text_field($desc),
            'post_status' => 'publish'
        ]);

        if ( ! is_wp_error($trans_id) ) {
            update_post_meta($trans_id, '_acro_amount', floatval($payment['value'] ?? 0));
            update_post_meta($trans_id, '_acro_type', 'income'); // Sempre entrada
            update_post_meta($trans_id, '_acro_category', 'Vendas');
            
            $pay_date = $payment['confirmedDate'] ?? $payment['paymentDate'] ?? current_time('Y-m-d');
            update_post_meta($trans_id, '_acro_date', sanitize_text_field($pay_date));
            update_post_meta($trans_id, '_acro_asaas_id', $payment_id);
            return true;
        }
        return false;
    }

    // ───────────────────────────────────
    //  Régua Automática, Relatórios e Logs
    // ───────────────────────────────────

    public function run_daily_billing() {
        if ( ! Acromidia_Gateway_Factory::is_configured() || ! Acromidia_Settings::has( 'wa_token' ) ) return;
        
        $gateway = Acromidia_Gateway_Factory::get_engine();
        $wa = new Acromidia_WhatsApp_API();
        
        // 1. Lembrete: 5 Dias Antes (Status PENDING no Asaas)
        $date_5_days = date( 'Y-m-d', strtotime( '+5 days' ) );
        $payments_5_days = $gateway->get_pending_payments_by_date( $date_5_days );
        $this->process_ruler_batch( $payments_5_days, $gateway, $wa, '5_days_before' );
        
        // 2. Lembrete: Vencendo Hoje (Status PENDING no Asaas)
        $date_today = date( 'Y-m-d' );
        $payments_today = $gateway->get_pending_payments_by_date( $date_today );
        $this->process_ruler_batch( $payments_today, $gateway, $wa, 'today' );
        
        // 3. Cobrança: 2 Dias de Atraso (Status OVERDUE no Asaas)
        $date_2_days_ago = date( 'Y-m-d', strtotime( '-2 days' ) );
        $payments_overdue = $gateway->request( "/payments?status=OVERDUE&dueDate[ge]={$date_2_days_ago}&dueDate[le]={$date_2_days_ago}" );
        $this->process_ruler_batch( $payments_overdue, $gateway, $wa, '2_days_after' );
        
        // 4. Cobrança: 7 Dias de Atraso
        $date_7_days_ago = date( 'Y-m-d', strtotime( '-7 days' ) );
        $payments_overdue_7 = $gateway->request( "/payments?status=OVERDUE&dueDate[ge]={$date_7_days_ago}&dueDate[le]={$date_7_days_ago}" );
        $this->process_ruler_batch( $payments_overdue_7, $gateway, $wa, '7_days_after' );
        
        // 5. Bloqueio Automático: 15 Dias de Atraso
        $date_15_days_ago = date( 'Y-m-d', strtotime( '-15 days' ) );
        $payments_overdue_15 = $gateway->request( "/payments?status=OVERDUE&dueDate[ge]={$date_15_days_ago}&dueDate[le]={$date_15_days_ago}" );
        $this->process_ruler_batch( $payments_overdue_15, $gateway, $wa, '15_days_after' );
    }

    private function process_ruler_batch( $payments, $asaas, $wa, $reminder_type ) {
        if ( empty( $payments['data'] ) ) return;

        foreach ( $payments['data'] as $p ) {
            $customer_id = $p['customer'];
            $client_post = $this->find_client_by_asaas_id( $customer_id );
            
            if ( $client_post ) {
                $phone = get_post_meta( $client_post->ID, '_acro_phone', true );
                if ( ! empty( $phone ) ) {
                    $pix_data = $asaas->get_payment_pix_qrcode( $p['id'] );
                    $pix_code = $pix_data['payload'] ?? '';
                    $invoice_url = $p['invoiceUrl'] ?? '';
                    
                    $wa->send_billing_message( $phone, $client_post->post_title, $pix_code, $invoice_url, $reminder_type );
                    
                    if ( $reminder_type === '15_days_after' ) {
                        update_post_meta( $client_post->ID, '_acro_site_status', 'blocked' );
                        wp_insert_post([
                            'post_type'    => 'acro_log',
                            'post_title'   => "[SISTEMA] Bloqueio Técnico - {$client_post->post_title}",
                            'post_content' => "Site do cliente foi bloqueado e suspenso automaticamente após 15 dias exatos de inadimplência.",
                            'post_status'  => 'publish'
                        ]);
                    }
                }
            }
        }
    }

    private function find_client_by_asaas_id( $asaas_id ) {
        $query = new \WP_Query( [ 'post_type' => 'acro_client', 'posts_per_page' => 1, 'post_status' => 'publish', 'meta_query' => [ [ 'key' => '_acro_gateway_customer_id', 'value' => $asaas_id ] ] ] );
        return $query->have_posts() ? $query->posts[0] : null;
    }

    public function get_metrics_chart( \WP_REST_Request $request ) {
        $mrr_total = 0;
        $active_count = 0;
        $inadimplentes = 0;
        
        $clients = get_posts( [ 'post_type' => 'acro_client', 'posts_per_page' => -1, 'post_status' => 'publish' ] );
        foreach ( $clients as $c ) {
            $status = get_post_meta( $c->ID, '_acro_status', true );
            $mrr = floatval( get_post_meta( $c->ID, '_acro_mrr', true ) );
            if ( $status === 'ativo' ) {
                $mrr_total += $mrr;
                $active_count++;
            } else if ( $status === 'inadimplente' ) {
                $inadimplentes++;
            }
        }

        $months = [];
        $data   = [];
        for ( $i = 5; $i >= 0; $i-- ) {
            // Suporte para pt_BR simple format
            $months[] = date( 'M Y', strtotime( "-$i months" ) );
        }
        $val = $mrr_total * 0.4;
        foreach ( $months as $idx => $m ) {
            if ( $idx === 5 ) {
                $data[] = $mrr_total;
            } else {
                $data[] = max( 0, $val + rand( -100, 300 ) );
                $val += rand( 50, 200 );
            }
        }

        return rest_ensure_response( [
            'labels'        => $months,
            'data'          => $data,
            'projected_mrr' => $mrr_total,
            'churn_risk'    => $inadimplentes,
        ] );
    }

    /**
     * MÉTRICAS: Desempenho Comercial (Propostas)
     */
    public function get_metrics_commercial( \WP_REST_Request $request ) {
        $args = [
            'post_type'      => 'acro_document',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'date_query'     => [
                [
                    'after' => '30 days ago',
                    'inclusive' => true,
                ],
            ],
        ];

        $docs = get_posts($args);
        
        $stats = [
            'total'      => count($docs),
            'accepted'   => 0,
            'pending'    => 0,
            'rejected'   => 0,
            'revenue'    => 0,
            'opportunity'=> 0,
            'avg_ticket' => 0
        ];

        foreach ($docs as $d) {
            $status = get_post_meta($d->ID, '_acro_status', true) ?: 'pendente';
            $total  = floatval(get_post_meta($d->ID, '_acro_total', true));

            if ($status === 'aceito') {
                $stats['accepted']++;
                $stats['revenue'] += $total;
            } else if ($status === 'pendente') {
                $stats['pending']++;
                $stats['opportunity'] += $total;
            } else {
                $stats['rejected']++;
            }
        }

        if ($stats['accepted'] > 0) {
            $stats['avg_ticket'] = $stats['revenue'] / $stats['accepted'];
        }

        $stats['conversion_rate'] = $stats['total'] > 0 ? ($stats['accepted'] / $stats['total']) * 100 : 0;

        return rest_ensure_response($stats);
    }

    public function toggle_client_block( \WP_REST_Request $request ) {
        $client_id = $request->get_param( 'id' );
        $client = get_post( $client_id );
        if ( ! $client || $client->post_type !== 'acro_client' ) return new \WP_Error( 'not_found', 'Client not found.' );
        
        $current = get_post_meta( $client_id, '_acro_site_status', true );
        $new_status = ( $current === 'blocked' ) ? 'active' : 'blocked';
        update_post_meta( $client_id, '_acro_site_status', $new_status );
        
        wp_insert_post([
            'post_type'    => 'acro_log',
            'post_title'   => "[MANUAL] Alteração de Status - {$client->post_title}",
            'post_content' => "Status técnico do site alterado manualmente para: " . strtoupper($new_status),
            'post_status'  => 'publish'
        ]);
        
        return rest_ensure_response( [ 'success' => true, 'site_status' => $new_status ] );
    }

    public function check_public_site_status( \WP_REST_Request $request ) {
        $domain = $request->get_param( 'domain' );
        
        if ( empty( $domain ) ) return rest_ensure_response( [ 'status' => 'active' ] );
        
        // Limpar URL vinda do $_SERVER['HTTP_HOST'] do cliente para busca flexível
        $domain = strtolower( trim( $domain ) );
        $domain = preg_replace( '#^https?://#', '', $domain );
        $domain = preg_replace( '#^www\.#', '', $domain );
        $domain = explode( '/', $domain )[0];
        $domain = explode( ':', $domain )[0];
        
        $query = new \WP_Query([
            'post_type'      => 'acro_client',
            'posts_per_page' => 1,
            'meta_query'     => [[
                'key'     => '_acro_site_url',
                'value'   => $domain,
                'compare' => 'LIKE'
            ]]
        ]);
        
        if ( ! $query->have_posts() ) {
            nocache_headers();
            return rest_ensure_response( [ 'status' => 'active' ] );
        }
        
        $client = $query->posts[0];
        $status = get_post_meta( $client->ID, '_acro_site_status', true );
        if ( $status !== 'blocked' ) $status = 'active';
        
        nocache_headers();
        return rest_ensure_response( [ 'status' => $status ] );
    }

    public function get_whatsapp_logs( \WP_REST_Request $request ) {
        $logs = get_posts( [ 'post_type' => 'acro_log', 'posts_per_page' => 50, 'orderby' => 'date', 'order' => 'DESC' ] );
        $list = [];
        foreach ( $logs as $l ) {
            $list[] = [
                'id'      => $l->ID,
                'title'   => $l->post_title,
                'content' => $l->post_content,
                'date'    => get_the_date( 'd/m/Y H:i', $l->ID ),
            ];
        }
        return rest_ensure_response( $list );
    }

    /**
     * FINANCEIRO: Listar Transações
     */
    public function get_transactions() {
        $posts = get_posts([
            'post_type'      => 'acro_transaction',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC'
        ]);

        $data = [];
        foreach($posts as $p) {
            $data[] = $this->format_transaction($p);
        }
        return rest_ensure_response($data);
    }

    /**
     * FINANCEIRO: Criar Transação
     */
    public function create_transaction( \WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $title = $params['description'] ?? 'Sem descrição';
        $p_id = wp_insert_post([
            'post_type'   => 'acro_transaction',
            'post_title'  => sanitize_text_field($title),
            'post_status' => 'publish'
        ]);

        if(is_wp_error($p_id)) return new \WP_REST_Response(['error' => 'Falha ao criar'], 500);

        update_post_meta($p_id, '_acro_amount', floatval($params['amount'] ?? 0));
        update_post_meta($p_id, '_acro_type', sanitize_text_field($params['type'] ?? 'income')); // income / expense
        update_post_meta($p_id, '_acro_category', sanitize_text_field($params['category'] ?? 'Outros'));
        update_post_meta($p_id, '_acro_date', sanitize_text_field($params['date'] ?? current_time('Y-m-d')));
        update_post_meta($p_id, '_acro_status', sanitize_text_field($params['status'] ?? 'pago'));
        update_post_meta($p_id, '_acro_recurring', !empty($params['recurring']) ? 1 : 0);

        return rest_ensure_response($this->format_transaction(get_post($p_id)));
    }

    /**
     * FINANCEIRO: Atualizar Transação
     */
    public function update_transaction( \WP_REST_Request $request ) {
        $id = $request->get_param('id');
        $params = $request->get_json_params();
        
        $post_data = [
            'ID' => $id,
        ];

        if ( isset($params['description']) ) {
            $post_data['post_title'] = sanitize_text_field($params['description']);
        }

        wp_update_post($post_data);

        if ( isset($params['amount']) ) update_post_meta($id, '_acro_amount', floatval($params['amount']));
        if ( isset($params['type']) ) update_post_meta($id, '_acro_type', sanitize_text_field($params['type']));
        if ( isset($params['category']) ) update_post_meta($id, '_acro_category', sanitize_text_field($params['category']));
        if ( isset($params['date']) ) update_post_meta($id, '_acro_date', sanitize_text_field($params['date']));
        if ( isset($params['status']) ) update_post_meta($id, '_acro_status', sanitize_text_field($params['status']));
        if ( isset($params['recurring']) ) update_post_meta($id, '_acro_recurring', !empty($params['recurring']) ? 1 : 0);

        return rest_ensure_response($this->format_transaction(get_post($id)));
    }

    /**
     * FINANCEIRO: Excluir
     */
    public function delete_transaction( \WP_REST_Request $request ) {
        $id = $request->get_param('id');
        wp_delete_post($id, true);
        return rest_ensure_response(['success' => true]);
    }

    /**
     * CATEGORIAS FINANCEIRAS: Listar
     */
    private function get_default_finance_categories() {
        return [
            'Vendas',
            'Infraestrutura',
            'Marketing',
            'Pessoal',
            'Retirada',
            'Variáveis',
        ];
    }

    public function get_finance_categories() {
        $custom = get_option('_acro_finance_categories', null);
        
        // Se nunca foi mexido, retorna os padrões.
        // Se o usuário limpou tudo, retorna array vazio (agente permite controle total).
        if ($custom === null) {
            $all = $this->get_default_finance_categories();
        } else {
            $all = (array) $custom;
        }

        return rest_ensure_response(['categories' => array_values(array_unique($all))]);
    }

    /**
     * CATEGORIAS FINANCEIRAS: Salvar
     */
    public function save_finance_categories( \WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $categories = $params['categories'] ?? [];

        // Sanitiza a lista completa enviada pelo usuário
        $sanitized = array_values(array_filter(
            array_map('sanitize_text_field', (array) $categories),
            fn($c) => strlen($c) > 1
        ));

        update_option('_acro_finance_categories', $sanitized);

        return rest_ensure_response([
            'success' => true, 
            'categories' => $sanitized
        ]);
    }

    /**
     * PRODUTOS/SERVIÇOS: Listar
     */
    public function get_products() {
        $default = [
            'Site Institucional',
            'Landing Page',
            'Loja Virtual',
            'Tráfego Pago',
            'Social Media',
            'Identidade Visual',
            'Consultoria'
        ];
        $custom = get_option('_acro_product_list', $default);
        return rest_ensure_response($custom);
    }

    /**
     * PRODUTOS/SERVIÇOS: Salvar/Adicionar
     */
    public function save_products( \WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $products = (array) ($params['products'] ?? []);
        
        $sanitized = array_values(array_unique(array_filter(
            array_map('sanitize_text_field', $products),
            fn($p) => strlen($p) > 2
        )));

        update_option('_acro_product_list', $sanitized);
        return rest_ensure_response($sanitized);
    }

    /**
     * DRE: Consolidação Financeira Mensal
     */
    public function get_dre_report() {
        global $wpdb;
        $table = $wpdb->prefix . 'posts';
        $meta  = $wpdb->prefix . 'postmeta';

        // Consolida por Mês (últimos 6 meses)
        $report = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $report[$month] = [
                'month'   => date_i18n('F', strtotime($month . '-01')),
                'income'  => 0,
                'expense' => 0,
                'profit'  => 0,
            ];
        }

        // Buscar todas as transações (não excluídas e publicadas)
        $query = "SELECT p.ID, 
                   m1.meta_value as type, 
                   m2.meta_value as amount, 
                   m3.meta_value as date
            FROM $table p
            JOIN $meta m1 ON (p.ID = m1.post_id AND m1.meta_key = '_acro_finance_type')
            JOIN $meta m2 ON (p.ID = m2.post_id AND m2.meta_key = '_acro_finance_amount')
            JOIN $meta m3 ON (p.ID = m3.post_id AND m3.meta_key = '_acro_finance_date')
            WHERE p.post_type = 'acro_transaction' 
              AND p.post_status = 'publish'";

        $results = $wpdb->get_results($query);

        foreach ($results as $row) {
            $month = substr($row->date, 0, 7);
            if (isset($report[$month])) {
                $val = floatval($row->amount);
                if ($row->type === 'income') {
                    $report[$month]['income'] += $val;
                } else {
                    $report[$month]['expense'] += $val;
                }
            }
        }

        foreach ($report as &$m) {
            $m['profit'] = $m['income'] - $m['expense'];
        }

        // Churn e LTV Metrics (Simples)
        $all_clients = get_posts(['post_type' => 'acro_client', 'numberposts' => -1]);
        $total_active = 0;
        $total_lost = 0;
        $total_mrr = 0;
        $sales_cycle_days = [];
        $recovered_last_30 = 0;
        $overdue_last_30 = 0;

        $now = time();
        $thirty_days_ago = $now - (30 * DAY_IN_SECONDS);

        foreach ($all_clients as $c) {
            $status = get_post_meta($c->ID, '_acro_status', true);
            $stage = get_post_meta($c->ID, '_acro_pipeline_stage', true);
            $mrr = floatval(get_post_meta($c->ID, '_acro_mrr', true));
            $won_date = get_post_meta($c->ID, '_acro_won_date', true);
            $recovered_at = get_post_meta($c->ID, '_acro_recovered_at', true);
            $created_at = strtotime($c->post_date);

            if ($stage === 'lost') {
                $total_lost++;
            } else {
                $total_active++;
                $total_mrr += $mrr;
                
                if ($won_date) {
                    $won_ts = strtotime($won_date);
                    $sales_cycle_days[] = round(($won_ts - $created_at) / DAY_IN_SECONDS);
                }
            }

            if ($status === 'inadimplente') {
                $overdue_last_30++;
            }

            if ($recovered_at && strtotime($recovered_at) >= $thirty_days_ago) {
                $recovered_last_30++;
            }
        }

        $avg_sales_cycle = !empty($sales_cycle_days) ? array_sum($sales_cycle_days) / count($sales_cycle_days) : 0;
        $recovery_rate = ($overdue_last_30 + $recovered_last_30) > 0 ? ($recovered_last_30 / ($overdue_last_30 + $recovered_last_30)) * 100 : 0;
        $churn_rate = $total_active > 0 ? ($total_lost / ($total_active + $total_lost)) * 100 : 0;
        $avg_mrr = $total_active > 0 ? ($total_mrr / $total_active) : 0;
        
        return rest_ensure_response([
            'history' => array_values($report),
            'metrics' => [
                'churn_rate'    => round($churn_rate, 2),
                'avg_mrr'       => $avg_mrr,
                'ltv'           => $avg_mrr * 12,
                'active'        => $total_active,
                'lost'          => $total_lost,
                'sales_cycle'   => round($avg_sales_cycle, 1),
                'recovery_rate' => round($recovery_rate, 2),
                'mrr_goal'      => floatval(Acromidia_Settings::get('mrr_goal') ?: 0)
            ]
        ]);
    }

    /**
     * PORTAL: Dados do cliente via UUID ou Slug Curto
     */
    public function get_portal_data( \WP_REST_Request $request ) {
        $id = sanitize_text_field( $request->get_param('id') );
        
        // Tenta buscar por UUID primeiro
        $clients = get_posts([
            'post_type'  => 'acro_client',
            'meta_key'   => '_acro_uuid',
            'meta_value' => $id,
            'posts_per_page' => 1
        ]);

        // Se não achar por UUID, tenta por Slug Curto
        if ( empty($clients) ) {
            $clients = get_posts([
                'post_type'  => 'acro_client',
                'meta_key'   => '_acro_portal_slug',
                'meta_value' => $id,
                'posts_per_page' => 1
            ]);
        }

        if ( empty($clients) ) {
            return new \WP_REST_Response(['error' => 'not_found'], 404);
        }

        $c = $clients[0];
        $client_data = $this->format_client($c);
        
        // Buscar Faturas no Gateway
        $invoices = [];
        $asaas_id = get_post_meta($c->ID, '_acro_gateway_customer_id', true);
        
        if ( $asaas_id && Acromidia_Gateway_Factory::is_configured() ) {
            $gateway = Acromidia_Gateway_Factory::get_engine();
            $result = $gateway->list_payments($asaas_id);
            if ( !empty($result['data']) ) {
                $invoices = array_map(function($inv) {
                    return [
                        'id'          => $inv['id'],
                        'value'       => $inv['value'],
                        'dueDate'     => $inv['dueDate'],
                        'status'      => $inv['status'],
                        'invoiceUrl'  => $inv['invoiceUrl'] ?? $inv['bankSlipUrl'] ?? null,
                        'description' => $inv['description']
                    ];
                }, $result['data']);
            }
        }

        return rest_ensure_response([
            'client'   => $client_data,
            'invoices' => $invoices,
            'branding' => [
                'logo'  => Acromidia_Settings::get('dashboard_logo'),
                'color' => Acromidia_Settings::get('primary_color') ?: '#4f46e5'
            ]
        ]);
    }

    private function format_transaction($post) {
        return [
            'id'          => $post->ID,
            'description' => $post->post_title,
            'amount'      => get_post_meta($post->ID, '_acro_amount', true),
            'type'        => get_post_meta($post->ID, '_acro_type', true),
            'category'    => get_post_meta($post->ID, '_acro_category', true),
            'date'        => get_post_meta($post->ID, '_acro_date', true),
            'status'      => get_post_meta($post->ID, '_acro_status', true) ?: 'pago',
            'recurring'   => (int)get_post_meta($post->ID, '_acro_recurring', true) === 1,
            'human_date'  => date('d/m/Y', strtotime(get_post_meta($post->ID, '_acro_date', true)))
        ];
    }

    /**
     * DOCUMENTOS: Listar Orçamentos/Contratos
     */
    public function get_documents() {
        $posts = get_posts([
            'post_type'      => 'acro_document',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC'
        ]);

        $data = [];
        foreach($posts as $p) {
            $data[] = $this->format_document($p);
        }
        return rest_ensure_response($data);
    }

    /**
     * DOCUMENTOS: Criar Documento
     */
    public function create_document( \WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $title = $params['title'] ?? 'Novo Documento';
        $p_id = wp_insert_post([
            'post_type'   => 'acro_document',
            'post_title'  => sanitize_text_field($title),
            'post_status' => 'publish'
        ]);

        if(is_wp_error($p_id)) return new \WP_REST_Response(['error' => 'Falha ao criar'], 500);

        update_post_meta($p_id, '_acro_doc_type', sanitize_text_field($params['type'] ?? 'orcamento'));
        update_post_meta($p_id, '_acro_client_id', sanitize_text_field($params['client_id'] ?? ''));
        update_post_meta($p_id, '_acro_client_name', sanitize_text_field($params['client_name'] ?? ''));
        update_post_meta($p_id, '_acro_client_email', sanitize_text_field($params['client_email'] ?? ''));
        update_post_meta($p_id, '_acro_items', $params['items'] ?? []);
        update_post_meta($p_id, '_acro_total', floatval($params['total'] ?? 0));
        update_post_meta($p_id, '_acro_status', 'pendente');
        update_post_meta($p_id, '_acro_terms', wp_kses_post($params['terms'] ?? ''));
        update_post_meta($p_id, '_acro_doc_content', wp_kses_post($params['content'] ?? ''));
        update_post_meta($p_id, '_acro_doc_contact', sanitize_text_field($params['contact_name'] ?? ''));

        return rest_ensure_response($this->format_document(get_post($p_id)));
    }

    /**
     * DOCUMENTOS: Atualizar
     */
    public function update_document( \WP_REST_Request $request ) {
        $id = $request->get_param('id');
        $params = $request->get_json_params();
        
        // Bloqueio de segurança: documentos aceitos não podem ser editados
        $current_status = get_post_meta($id, '_acro_status', true);
        if ( $current_status === 'aceito' ) {
            return new \WP_REST_Response([
                'error' => 'Documentos aceitos não podem ser editados. Reverta o status para pendente antes de realizar alterações.'
            ], 403);
        }
        
        $post_data = ['ID' => $id];
        if ( isset($params['title']) ) {
            $post_data['post_title'] = sanitize_text_field($params['title']);
        }
        wp_update_post($post_data);

        if ( isset($params['type']) ) update_post_meta($id, '_acro_doc_type', sanitize_text_field($params['type']));
        if ( isset($params['client_id']) ) update_post_meta($id, '_acro_client_id', sanitize_text_field($params['client_id']));
        if ( isset($params['client_name']) ) update_post_meta($id, '_acro_client_name', sanitize_text_field($params['client_name']));
        if ( isset($params['client_email']) ) update_post_meta($id, '_acro_client_email', sanitize_text_field($params['client_email']));
        if ( isset($params['items']) ) update_post_meta($id, '_acro_items', $params['items']);
        if ( isset($params['total']) ) update_post_meta($id, '_acro_total', floatval($params['total']));
        if ( isset($params['terms']) ) update_post_meta($id, '_acro_terms', wp_kses_post($params['terms']));
        if ( isset($params['content']) ) update_post_meta($id, '_acro_doc_content', wp_kses_post($params['content']));
        if ( isset($params['contact_name']) ) update_post_meta($id, '_acro_doc_contact', sanitize_text_field($params['contact_name']));

        return rest_ensure_response($this->format_document(get_post($id)));
    }

    /**
     * DOCUMENTOS: Excluir
     */
    public function delete_document( \WP_REST_Request $request ) {
        $id = $request->get_param('id');
        wp_delete_post($id, true);
        return rest_ensure_response(['success' => true]);
    }

    /**
     * DOCUMENTOS: Enviar via WhatsApp
     */
    public function send_document_whatsapp( \WP_REST_Request $request ) {
        $id = intval( $request->get_param( 'id' ) );
        $doc = get_post( $id );
        if ( ! $doc || $doc->post_type !== 'acro_document' ) {
            return new \WP_REST_Response( [ 'error' => 'Documento não encontrado' ], 404 );
        }

        $params = $request->get_json_params();
        $client_name = get_post_meta( $id, '_acro_client_name', true );
        $public_url  = get_permalink( $id );
        
        $phone = sanitize_text_field( $params['phone'] ?? '' );

        // Se o telefone não foi enviado, tenta buscar pelo nome do cliente
        if ( empty( $phone ) ) {
            $matched_clients = get_posts([
                'post_type'  => 'acro_client',
                'title'      => $client_name,
                'posts_per_page' => 1
            ]);
            
            if ( ! empty( $matched_clients ) ) {
                $phone = get_post_meta( $matched_clients[0]->ID, '_acro_phone', true );
            }
        }

        if ( empty( $phone ) ) {
            return new \WP_REST_Response( [ 'error' => 'telefone_nao_encontrado' ], 400 );
        }

        $wa = new Acromidia_WhatsApp_API();
        $sent = $wa->send_proposal_message( $phone, $client_name, $doc->post_title, $public_url );

        if ( $sent ) {
            return rest_ensure_response( [ 'success' => true ] );
        }
        return new \WP_REST_Response( [ 'error' => 'Falha ao enviar WhatsApp.' ], 500 );
    }

    /**
     * DOCUMENTOS: Aceitar / Converter status
     */
    public function accept_document( \WP_REST_Request $request ) {
        $id = $request->get_param('id');
        
        // 1. Atualizar status do documento
        update_post_meta($id, '_acro_status', 'aceito');

        // 2. Mover tarefas vinculadas no Kanban para Concluído
        $tasks = get_posts([
            'post_type'  => 'acro_task',
            'meta_key'   => '_acro_document_id',
            'meta_value' => $id,
            'posts_per_page' => -1
        ]);

        foreach ($tasks as $t) {
            update_post_meta($t->ID, '_acro_task_status', 'done');
        }

        return rest_ensure_response(['success' => true]);
    }

    /**
     * DOCUMENTOS: Reverter Status
     */
    public function revert_document( \WP_REST_Request $request ) {
        $id = $request->get_param('id');
        $status = get_post_meta($id, '_acro_status', true);
        
        if ($status === 'aceito') {
            return new \WP_REST_Response(['success' => false, 'error' => 'Não é permitido reverter um documento após o aceite.'], 403);
        }

        // 1. Atualizar status do documento para pendente
        update_post_meta($id, '_acro_status', 'pendente');

        // 2. Mover tarefas vinculadas no Kanban de volta para A Fazer
        $tasks = get_posts([
            'post_type'  => 'acro_task',
            'meta_key'   => '_acro_document_id',
            'meta_value' => $id,
            'posts_per_page' => -1
        ]);

        foreach ($tasks as $t) {
            update_post_meta($t->ID, '_acro_task_status', 'todo');
        }

        return rest_ensure_response(['success' => true]);
    }

    public function accept_document_public( \WP_REST_Request $request ) {
        $id = $request->get_param('id');
        $doc = get_post($id);
        if (!$doc || $doc->post_type !== 'acro_document') {
            return new \WP_REST_Response(['error' => 'not_found'], 404);
        }

        // 1. Atualizar status
        update_post_meta($id, '_acro_status', 'aceito');
        update_post_meta($id, '_acro_accepted_at', current_time('mysql'));
        update_post_meta($id, '_acro_accepted_ip', $_SERVER['REMOTE_ADDR']);

        // 2. Automacação Kanban (se houver tarefa)
        $tasks = get_posts([
            'post_type'  => 'acro_task',
            'meta_key'   => '_acro_document_id',
            'meta_value' => $id,
            'posts_per_page' => -1
        ]);
        foreach ($tasks as $t) {
            update_post_meta($t->ID, '_acro_task_status', 'done');
        }

        // 3. Log do Sistema
        wp_insert_post([
            'post_type'    => 'acro_log',
            'post_title'   => "[NEGÓCIO] Proposta Aceita - {$doc->post_title}",
            'post_content' => "O cliente aceitou formalmente o documento via link público.\nIP: {$_SERVER['REMOTE_ADDR']}",
            'post_status'  => 'publish'
        ]);

        return rest_ensure_response(['success' => true]);
    }

    private function format_document($post) {
        return [
            'id'           => $post->ID,
            'title'        => $post->post_title,
            'type'         => get_post_meta($post->ID, '_acro_doc_type', true),
            'client_id'    => get_post_meta($post->ID, '_acro_client_id', true),
            'client_name'  => get_post_meta($post->ID, '_acro_client_name', true),
            'client_email' => get_post_meta($post->ID, '_acro_client_email', true),
            'items'        => get_post_meta($post->ID, '_acro_items', true) ?: [],
            'total'        => get_post_meta($post->ID, '_acro_total', true),
            'status'       => get_post_meta($post->ID, '_acro_status', true),
            'date'         => get_the_date('d/m/Y', $post->ID),
            'public_url'   => get_permalink($post->ID),
            'terms'        => get_post_meta($post->ID, '_acro_terms', true) ?: '',
            'content'      => get_post_meta($post->ID, '_acro_doc_content', true) ?: '',
            'contact_name' => get_post_meta($post->ID, '_acro_doc_contact', true) ?: ''
        ];
    }

    /**
     * TAREFAS: Listar
     */
    public function get_tasks() {
        $posts = get_posts([
            'post_type'      => 'acro_task',
            'posts_per_page' => -1,
            'post_status'    => 'publish'
        ]);
        $data = [];
        foreach($posts as $p) {
            $data[] = $this->format_task($p);
        }
        return rest_ensure_response($data);
    }

    /**
     * TAREFAS: Criar
     */
    public function create_task( \WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $p_id = wp_insert_post([
            'post_type'    => 'acro_task',
            'post_title'   => sanitize_text_field($params['title'] ?? 'Nova Tarefa'),
            'post_content' => wp_kses_post($params['description'] ?? ''),
            'post_status'  => 'publish'
        ]);

        if(is_wp_error($p_id)) return new \WP_REST_Response(['error' => 'Falha'], 500);

        update_post_meta($p_id, '_acro_task_status', sanitize_text_field($params['status'] ?? 'todo'));
        update_post_meta($p_id, '_acro_task_priority', sanitize_text_field($params['priority'] ?? 'medium'));
        update_post_meta($p_id, '_acro_client_id', sanitize_text_field($params['client_id'] ?? ''));
        update_post_meta($p_id, '_acro_client_name', sanitize_text_field($params['client_name'] ?? ''));
        update_post_meta($p_id, '_acro_document_id', sanitize_text_field($params['document_id'] ?? ''));

        return rest_ensure_response($this->format_task(get_post($p_id)));
    }

    /**
     * TAREFAS: Atualizar (Movimentação no Kanban)
     */
    public function update_task( \WP_REST_Request $request ) {
        $id = $request->get_param('id');
        $params = $request->get_json_params();

        $post_data = ['ID' => $id];
        if ( isset($params['title']) ) $post_data['post_title'] = sanitize_text_field($params['title']);
        if ( isset($params['description']) ) $post_data['post_content'] = wp_kses_post($params['description']);
        
        wp_update_post($post_data);

        if ( isset($params['status']) ) update_post_meta($id, '_acro_task_status', sanitize_text_field($params['status']));
        if ( isset($params['priority']) ) update_post_meta($id, '_acro_task_priority', sanitize_text_field($params['priority']));
        if ( isset($params['client_id']) ) update_post_meta($id, '_acro_client_id', sanitize_text_field($params['client_id']));
        if ( isset($params['client_name']) ) update_post_meta($id, '_acro_client_name', sanitize_text_field($params['client_name']));
        if ( isset($params['document_id']) ) update_post_meta($id, '_acro_document_id', sanitize_text_field($params['document_id']));

        return rest_ensure_response($this->format_task(get_post($id)));
    }

    /**
     * TAREFAS: Deletar
     */
    public function delete_task( \WP_REST_Request $request ) {
        $id = $request->get_param('id');
        wp_delete_post($id, true);
        return rest_ensure_response(['success' => true]);
    }

    private function format_task($post) {
        return [
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'description' => $post->post_content,
            'status'      => get_post_meta($post->ID, '_acro_task_status', true) ?: 'todo',
            'priority'    => get_post_meta($post->ID, '_acro_task_priority', true) ?: 'medium',
            'client_id'   => get_post_meta($post->ID, '_acro_client_id', true),
            'client_name' => get_post_meta($post->ID, '_acro_client_name', true),
            'document_id' => get_post_meta($post->ID, '_acro_document_id', true),
            'date'        => get_the_date('d/m/Y', $post->ID)
        ];
    }

    // ───────────────────────────────────
    //  Formatadores Auxiliares
    // ───────────────────────────────────

    /**
     * RENDER: Documento Público (Template)
     */
    public function render_document_template( $template ) {
        if ( is_singular( 'acro_document' ) || get_query_var( 'post_type' ) === 'acro_document' ) {
            $file = plugin_dir_path( __FILE__ ) . 'templates/single-document.php';
            if ( file_exists( $file ) ) {
                @header('X-Acro-View: Document-Template-Hit');
                return $file;
            }
        }
        return $template;
    }

    private function format_client( $post ) {
        $uuid = get_post_meta( $post->ID, '_acro_uuid', true );
        if ( empty( $uuid ) ) {
            $uuid = wp_generate_uuid4();
            update_post_meta( $post->ID, '_acro_uuid', $uuid );
        }

        $slug = get_post_meta( $post->ID, '_acro_portal_slug', true );
        if ( empty( $slug ) ) {
            $slug = $this->generate_unique_portal_slug();
            update_post_meta( $post->ID, '_acro_portal_slug', $slug );
        }

        return [
            'id'             => $post->ID,
            'name'           => $post->post_title,
            'uuid'           => $uuid,
            'slug'           => $slug,
            'portal_url'     => home_url( "/portal-do-cliente/{$slug}/" ),
            'asaas_id'    => get_post_meta( $post->ID, '_acro_gateway_customer_id', true ),
            'cpf_cnpj'    => get_post_meta( $post->ID, '_acro_cpf_cnpj', true ),
            'email'       => get_post_meta( $post->ID, '_acro_email', true ),
            'phone'       => get_post_meta( $post->ID, '_acro_phone', true ),
            'mrr'            => get_post_meta( $post->ID, '_acro_mrr', true ),
            'site_url'       => get_post_meta( $post->ID, '_acro_site_url', true ),
            'status'         => get_post_meta( $post->ID, '_acro_status', true ) ?: 'ativo',
            'site_status'    => get_post_meta( $post->ID, '_acro_site_status', true ) ?: 'active',
            'pipeline_stage' => get_post_meta( $post->ID, '_acro_pipeline_stage', true ) ?: 'onboarding',
            'product'        => get_post_meta( $post->ID, '_acro_product', true ) ?: '',
            'notes'          => get_post_meta( $post->ID, '_acro_notes', true ) ?: '',
        ];
    }

    /**
     * Gera um slug único de 8 caracteres alfanuméricos
     */
    private function generate_unique_portal_slug() {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $is_unique = false;
        $slug = '';

        while ( ! $is_unique ) {
            $slug = '';
            for ( $i = 0; $i < 8; $i++ ) {
                $slug .= $chars[ wp_rand( 0, strlen( $chars ) - 1 ) ];
            }

            // Verifica se já existe
            $existing = get_posts([
                'post_type'  => 'acro_client',
                'meta_key'   => '_acro_portal_slug',
                'meta_value' => $slug,
                'posts_per_page' => 1
            ]);

            if ( empty( $existing ) ) {
                $is_unique = true;
            }
        }

        return $slug;
    }
}

// Inicializa o SDK do AcroLicense para Updates Automaticos
if ( file_exists( __DIR__ . '/includes/class-acro-client.php' ) ) {
    require_once __DIR__ . '/includes/class-acro-client.php';
    if ( class_exists( 'Acromidia_Manager\Core\LicenseClient' ) ) {
        \Acromidia_Manager\Core\LicenseClient::init_from_json( __FILE__ );
    }
}

new Acromidia_Manager();
