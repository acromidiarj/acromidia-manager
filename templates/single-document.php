<?php
/**
 * Template para exibição de Orçamentos e Contratos (ERP)
 * Design: Ultra-Premium, Formal and Print-Optimized
 * Padrão: PT-BR (Datas e Moeda)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$doc_id   = get_the_ID();
$type     = get_post_meta($doc_id, '_acro_doc_type', true);
$client   = get_post_meta($doc_id, '_acro_client_name', true);
$items    = get_post_meta($doc_id, '_acro_items', true) ?: [];
$total    = get_post_meta($doc_id, '_acro_total', true);
$terms    = get_post_meta($doc_id, '_acro_terms', true);
$content  = get_post_meta($doc_id, '_acro_doc_content', true);
$status   = get_post_meta($doc_id, '_acro_status', true);
$accepted_at = get_post_meta($doc_id, '_acro_accepted_at', true);
$accepted_ip = get_post_meta($doc_id, '_acro_accepted_ip', true);
$contact     = get_post_meta($doc_id, '_acro_doc_contact', true);

// Logo Dinâmico
$custom_logo_url = \Acromidia_Settings::get( 'dashboard_logo' );
if ( ! $custom_logo_url ) {
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    if ( $custom_logo_id ) {
        $logo_data = wp_get_attachment_image_src( $custom_logo_id, 'full' );
        $custom_logo_url = $logo_data ? $logo_data[0] : '';
    }
}
$primary_color = \Acromidia_Settings::get( 'primary_color' ) ?: '#4f46e5';

// Cores Dinâmicas
$color = ($type === 'contrato') ? '#1e293b' : $primary_color; // Slate formal ou Cor Primária customizada

// Labels Dinâmicos
$label_title  = ($type === 'contrato') ? 'Instrumento Particular de Contrato' : 'Proposta Comercial / Orçamento';
$label_action = ($type === 'contrato') ? 'Assinar Contrato Eletronicamente' : 'Aceitar e Iniciar Projeto';

// Formatação PT-BR
$date_display = get_the_date('d \d\e F \d\e Y', $doc_id);
$total_display = 'R$ ' . number_format($total, 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php the_title(); ?> | Acromidia Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --acro-primary: <?php echo esc_attr($primary_color); ?>;
        }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; scroll-behavior: smooth; }
        .outfit { font-family: 'Outfit', sans-serif; }
        .glass { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .prose { max-width: none; color: #334155; line-height: 1.8; }
        .prose strong { color: #0f172a; font-weight: 800; }
        .prose p { margin-bottom: 1.5rem; text-align: justify; }
        .prose ul, .prose ol { padding-left: 2rem; margin-bottom: 1.5rem; }
        .prose h1, .prose h2, .prose h3 { color: #0f172a; font-family: 'Outfit', sans-serif; font-weight: 800; margin-top: 2.5rem; }
        
        .signature-box { border: 2px dashed #cbd5e1; background: #f1f5f9; }
        .accepted-seal { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
        
        /* Botão Primário Dinâmico */
        .btn-primary { background-color: var(--acro-primary) !important; color: white !important; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; color: #000 !important; padding: 0 !important; }
            .glass { border: none !important; box-shadow: none !important; padding: 0 !important; }
            .prose { font-size: 11pt !important; line-height: 1.6 !important; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body class="min-h-screen md:p-12 pb-32">
    <div class="max-w-4xl mx-auto space-y-12 animate-in fade-in slide-in-from-bottom-5 duration-700">
        
        <!-- HEADER (STATUS BAR) -->
        <header class="flex items-center justify-between px-8 py-4 bg-white rounded-full border border-slate-200 shadow-sm no-print mx-4 md:mx-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white" style="background-color: var(--acro-primary);">
                     <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Documento Verificado</span>
            </div>
            
            <div class="flex items-center gap-4">
                <?php if($status === 'aceito'): ?>
                    <span class="px-4 py-1.5 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase rounded-full border border-emerald-100 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Assinatura Confirmada
                    </span>
                <?php else: ?>
                    <span class="px-4 py-1.5 bg-blue-50 text-blue-600 text-[10px] font-black uppercase rounded-full border border-blue-100 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-blue-500 animate-pulse rounded-full"></span> Pendente de Aceite
                    </span>
                <?php endif; ?>
                <button onclick="window.print()" class="p-2 text-slate-400 hover:text-slate-900 transition-colors" title="Imprimir">
                    <i data-lucide="printer" class="w-5 h-5"></i>
                </button>
            </div>
        </header>

        <!-- OFFICIAL LOGO / LETTERHEAD -->
        <div class="text-center space-y-2 pt-2">
             <?php if ( $custom_logo_url ) : ?>
                <div class="mx-auto mb-4">
                    <img src="<?php echo esc_url($custom_logo_url); ?>" class="max-h-20 mx-auto object-contain" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                </div>
             <?php else : ?>
                <div class="w-16 h-16 mx-auto rounded-[22px] flex items-center justify-center text-white shadow-xl mb-4" style="background-color: var(--acro-primary);">
                    <i data-lucide="layers" class="w-8 h-8"></i>
                </div>
                <div>
                   <h1 class="text-xl font-black outfit tracking-tighter uppercase"><?php echo get_bloginfo('name'); ?></h1>
                   <p class="text-[9px] text-slate-400 font-bold uppercase tracking-[0.4em]">Soluções em Negócios e Tecnologia</p>
                </div>
             <?php endif; ?>
        </div>

        <!-- MAIN DOCUMENT CONTAINER -->
        <main class="glass rounded-[40px] p-8 md:p-16 relative bg-white overflow-hidden min-h-[800px]">
            
            <!-- Document Header Title -->
            <div class="border-b-2 border-slate-50 pb-8 mb-8 text-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.5em] mb-2"><?php echo $label_title; ?></p>
                <h2 class="text-3xl md:text-4xl font-black outfit tracking-tight text-slate-900"><?php the_title(); ?></h2>
                <div class="flex items-center justify-center gap-3 mt-6 text-xs font-bold text-slate-400 uppercase tracking-widest">
                    <span>Emitido em <?php echo $date_display; ?></span>
                    <span class="text-slate-200">|</span>
                    <span>Protocolo #<?php echo $doc_id; ?></span>
                </div>
            </div>

            <!-- Client Info Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12 bg-slate-50 p-6 rounded-[30px] border border-slate-100">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">CONTRATANTE / CLIENTE</p>
                    <p class="text-lg font-black text-slate-900"><?php echo $client; ?></p>
                    <?php if(!empty($contact)): ?>
                        <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-1 italic">A/C: <?php echo $contact; ?></p>
                    <?php else: ?>
                        <p class="text-xs text-slate-500 font-medium mt-1">Identificado como beneficiário deste documento.</p>
                    <?php endif; ?>
                </div>
                <div class="md:text-right">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">VALOR DO INSTRUMENTO</p>
                    <?php if($type === 'contrato'): ?>
                        <?php if($total > 0): ?>
                            <p class="text-3xl font-black text-slate-900"><?php echo $total_display; ?></p>
                            <p class="text-xs text-slate-500 font-medium mt-1">Valor fixado neste instrumento.</p>
                        <?php else: ?>
                            <p class="text-xl font-black text-slate-900">Conforme Cláusulas</p>
                            <p class="text-xs text-slate-500 font-medium mt-1">Valores detalhados no corpo do contrato.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-3xl font-black text-blue-600"><?php echo $total_display; ?></p>
                        <p class="text-xs text-slate-500 font-medium mt-1">Investimento Total Orçado.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- THE CONTENT (LEGALEZE) -->
            <article class="prose prose-slate mb-20">
                <?php if(!empty($content)): ?>
                    <?php echo wp_kses_post($content); ?>
                <?php else: ?>
                    <p class="text-slate-400 italic text-center py-20 border-2 border-dashed border-slate-100 rounded-3xl">Nenhum texto legal anexado a este documento.</p>
                <?php endif; ?>
            </article>

            <!-- ITEM LIST (ONLY FOR PROPOSALS/ORCAMENTOS) -->
            <?php if(!empty($items) && $type === 'orcamento'): ?>
            <div class="page-break space-y-8 mb-20">
                <h3 class="text-xs font-black outfit uppercase tracking-widest text-slate-400 flex items-center gap-3">
                    <i data-lucide="list-checks" class="w-4 h-4"></i> Tabela de Valores e Serviços
                </h3>
                <div class="space-y-4">
                    <?php foreach($items as $item): ?>
                    <div class="flex items-center justify-between p-6 rounded-2xl border border-slate-100 bg-slate-50/30">
                        <span class="font-bold text-slate-900"><?php echo esc_html($item['description']); ?></span>
                        <span class="font-black text-slate-900">R$ <?php echo number_format($item['price'], 2, ',', '.'); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="flex justify-end p-8 bg-slate-900 rounded-3xl text-white shadow-2xl">
                        <div class="text-right">
                            <p class="text-[10px] font-black uppercase tracking-widest opacity-50 mb-1">Total da Proposta</p>
                            <p class="text-4xl font-black outfit"><?php echo $total_display; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- FOOTER NOTES -->
            <?php if(!empty($terms) && $type === 'orcamento'): ?>
            <div class="border-t-2 border-slate-50 pt-12 text-center max-w-2xl mx-auto">
                 <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em] mb-4">Notas Importantes</p>
                 <p class="text-sm text-slate-500 font-medium whitespace-pre-line"><?php echo esc_html($terms); ?></p>
            </div>
            <?php endif; ?>

            <!-- SIGNATURE AREA (STATUS) -->
            <div id="signature-section" class="mt-32 pt-20 border-t-2 border-slate-100">
                <?php if($status === 'aceito'): ?>
                    <div class="accepted-seal p-12 rounded-[40px] text-white text-center shadow-2xl relative overflow-hidden animate-in zoom-in duration-500">
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 blur-[60px] rounded-full"></div>
                        <i data-lucide="check-circle-2" class="w-16 h-16 mx-auto mb-6"></i>
                        <h3 class="text-3xl font-black outfit uppercase tracking-tighter mb-2">Documento Aceito e Assinado</h3>
                        <p class="text-emerald-100 font-bold uppercase text-[10px] tracking-widest opacity-80 mb-8">Validado via Acromidia Manager Trust Layer</p>
                        
                        <div class="flex flex-col md:flex-row justify-center gap-8 text-[10px] font-black uppercase tracking-[0.2em]">
                            <div class="px-6 py-3 bg-white/10 rounded-2xl">IP: <?php echo $accepted_ip; ?></div>
                            <div class="px-6 py-3 bg-white/10 rounded-2xl">DATA: <?php echo date('d/m/Y H:i', strtotime($accepted_at)); ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="signature-box p-12 rounded-[40px] text-center no-print">
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-10">Aguardando sua Assinatura Eletrônica</p>
                        
                        <button id="btn-accept" onclick="handleAccept()" class="group relative px-12 py-6 bg-slate-900 text-white rounded-3xl font-black text-lg uppercase tracking-widest hover:scale-105 transition-all shadow-2xl flex items-center gap-4 mx-auto overflow-hidden">
                            <span id="btn-text"><?php echo $label_action; ?></span>
                            <i data-lucide="pen-tool" class="w-6 h-6 group-hover:rotate-12 transition-transform"></i>
                            <div id="btn-loader" class="hidden"><i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i></div>
                        </button>
                        
                        <p class="text-xs text-slate-400 font-bold mt-8 max-w-md mx-auto leading-relaxed">
                            Ao assinar, você concorda plenamente com os termos estabelecidos acima nesta data <?php echo date('d/m/Y'); ?>.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

        </main>

        <!-- EXTERNAL FOOTER -->
        <footer class="text-center no-print">
            <p class="text-[10px] font-black uppercase tracking-[0.5em] text-slate-300 mb-8">Generated by Acro Manager Business Suite Elite v4.1.0</p>
            <div class="flex justify-center gap-8 text-slate-200">
                <i data-lucide="shield" class="w-4 h-4"></i>
                <i data-lucide="lock" class="w-4 h-4"></i>
                <i data-lucide="printer" class="w-4 h-4 cursor-pointer hover:text-slate-900" onclick="window.print()"></i>
            </div>
        </footer>

    </div>

    <script>
        lucide.createIcons();

        async function handleAccept() {
            if (!confirm("Deseja realizar o aceite eletrônico deste documento? Esta ação tem validade jurídica de conformidade entre as partes.")) return;

            const btn = document.getElementById('btn-accept');
            const btnText = document.getElementById('btn-text');
            const btnLoader = document.getElementById('btn-loader');
            
            btn.disabled = true;
            btnText.innerText = "Processando Assinatura...";
            btnLoader.classList.remove('hidden');

            try {
                const response = await fetch('<?php echo get_rest_url(null, "acromidia/v1/public/documents/" . $doc_id . "/accept"); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                if (response.ok) {
                    location.reload();
                } else {
                    alert("Falha ao registrar aceite. Tente novamente mais tarde.");
                }
            } catch (e) {
                alert("Erro técnico de conexão.");
            } finally {
                btn.disabled = false;
                btnLoader.classList.add('hidden');
                btnText.innerText = "<?php echo $label_action; ?>";
            }
        }
    </script>
</body>
</html>
