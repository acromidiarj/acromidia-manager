<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$rest_nonce = wp_create_nonce( 'wp_rest' );
$rest_url   = rest_url( 'acromidia/v1' );
$settings_url = admin_url( 'admin.php?page=acromidia-settings' );
$asaas_ok     = \Acromidia_Gateway_Factory::is_configured();
$wa_ok        = \Acromidia_Settings::has('wa_token') && \Acromidia_Settings::has('wa_phone_id');

// Identifica o gateway ativo para UI dinâmica
$primary_id = \Acromidia_Settings::get( 'primary_gateway' ) ?: 'asaas';
$gateways_map = [
    'asaas'       => 'Asaas',
    'stripe'      => 'Stripe',
    'mercadopago' => 'Mercado Pago',
    'pagarme'     => 'Pagar.me',
    'pagbank'     => 'PagBank'
];
$gateway_label = $gateways_map[$primary_id] ?? 'Gateway';

// Branding Dinâmico
$custom_logo    = \Acromidia_Settings::get( 'dashboard_logo' );
$primary_color  = \Acromidia_Settings::get( 'primary_color' ) ?: '#4f46e5';
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>

<style>
/* ══ DESIGN SYSTEM ACRO MANAGER — V3.2 (FIX SYNC) ══ */
:root {
  --acro-primary: <?php echo esc_attr($primary_color); ?>;
  --acro-bg: #f8fafc;
  --acro-text: #0f172a;
  --acro-slate: #64748b;
  --acro-border: #e2e8f0;
}

/* Unificação de Background — Respeitando o Layout Nativo */
html, body, #wpwrap, #wpbody, #wpbody-content, #wpfooter { 
    background: var(--acro-bg) !important; 
}

/* Estilização Amigável do Rodapé */
#wpfooter { 
    border-top: none !important; 
    padding: 20px 40px !important; 
    color: var(--acro-slate) !important;
}

#wpbody-content { padding-bottom: 40px !important; }

/* Esconder Alertas e Avisos Sujos do WP */
.update-nag, .notice, #screen-options-link-wrap { display: none !important; }

.acro-app { font-family: 'Inter', sans-serif; color: var(--acro-text); -webkit-font-smoothing: antialiased; }

/* ── COMPONENTS ── */
.card-glass { background: #ffffff; border: 1px solid var(--acro-border); border-radius: 20px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }

.btn-primary { 
    background: var(--acro-primary) !important; color: white !important; border-radius: 14px !important; padding: 12px 24px !important; font-weight: 700 !important; font-size: 14px !important;
    display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; border: none !important; cursor: pointer;
}
.btn-primary:hover { background: #4338ca !important; transform: translateY(-1px); }

/* ── INPUTS ── */
.input-label { display: block; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--acro-slate); margin-bottom: 8px; margin-left: 4px; }
.modern-input {
    width: 100%; height: 56px; background: #fff !important; 
    border: 2px solid var(--acro-border) !important; border-radius: 16px !important; 
    padding: 0 20px 0 54px !important;
    font-size: 15px !important; font-weight: 600 !important; color: var(--acro-text) !important; 
    transition: border 0.2s, box-shadow 0.2s !important; outline: none !important; box-sizing: border-box !important;
}
.modern-input:focus { border-color: var(--acro-primary) !important; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1) !important; }
.modern-textarea {
    height: auto;
    min-height: 100px !important;
    padding: 15px 20px 15px 54px !important;
    line-height: 1.6 !important;
    resize: vertical !important;
    overflow-y: auto !important;
}

.input-icon { 
    position: absolute !important; left: 20px !important; top: 50% !important; transform: translateY(-50%) !important; 
    width: 20px !important; height: 20px !important; color: #94a3b8 !important; 
    pointer-events: none !important; z-index: 10 !important;
    display: flex; align-items: center; justify-content: center;
}

/* ── MODAL ── */
.modal-overlay { background: rgba(15, 23, 42, 0.8) !important; backdrop-filter: blur(12px) !important; }
.modal-card { filter: drop-shadow(0 25px 70px rgba(0, 0, 0, 0.3)); border: 1px solid rgba(255,255,255,0.1); }

/* ── BADGES ── */
.badge { padding: 4px 12px; border-radius: 10px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
.badge-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

/* TINYMCE CUSTOMIZATION */
.tox-tinymce { border-radius: 20px !important; border-color: #f1f5f9 !important; box-shadow: none !important; }
.tox-editor-header { 
    position: sticky !important; 
    top: -30px !important; /* Compensa o padding do modal para "subir" mais */
    z-index: 100 !important; 
    background: #f8fafc !important; 
    border-bottom: 2px solid #f1f5f9 !important; 
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05) !important;
}
.tox .tox-menubar { display: none !important; }
.tox .tox-toolbar-overlord { background: transparent !important; }
.tox .tox-edit-area__iframe { background: #fff !important; }
.badge-warning { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
.badge-danger { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
.badge-info { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }

[v-cloak] { display: none; }
.fade-enter-active, .fade-leave-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(30px) scale(0.95); }

.list-enter-active, .list-leave-active { transition: all 0.4s ease; }
.list-enter-from, .list-leave-to { opacity: 0; transform: translateX(30px); }
</style>

<div id="acro-app" class="acro-app pb-20" v-cloak>

  <!-- ══ HEADER ══ -->
  <header class="bg-white/90 backdrop-blur-md border-b sticky top-0 z-50 px-8 py-4">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-2xl flex items-center justify-center shadow-lg overflow-hidden" style="background-color: var(--acro-primary);">
           <?php if ( $custom_logo ) : ?>
               <img src="<?php echo esc_url($custom_logo); ?>" class="w-full h-full object-contain p-1">
           <?php else : ?>
               <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
           <?php endif; ?>
        </div>
        <div>
          <h1 class="font-black text-slate-900 leading-none text-xl">Acro Manager</h1>
          <span class="text-[9px] font-black uppercase text-indigo-600 tracking-[0.2em]">Management Suite</span>
        </div>
      </div>

      <nav class="flex bg-slate-100 p-1.5 rounded-2xl">
        <button @click="view='dashboard'" :class="view==='dashboard'?'bg-white shadow-sm text-indigo-600':'text-slate-500'" class="px-6 py-2 rounded-xl text-sm font-black transition-all">Geral</button>
        <button @click="view='clients'" :class="view==='clients'?'bg-white shadow-sm text-indigo-600':'text-slate-500'" class="px-6 py-2 rounded-xl text-sm font-black transition-all">Carteira</button>
        <button @click="view='crm'" :class="view==='crm'?'bg-white shadow-sm text-indigo-600':'text-slate-500'" class="px-6 py-2 rounded-xl text-sm font-black transition-all flex items-center gap-2"><i data-lucide="kanban" class="w-4 h-4"></i> Pipeline CRM</button>
        <button @click="view='finance'" :class="view==='finance'?'bg-white shadow-sm text-indigo-600':'text-slate-500'" class="px-6 py-2 rounded-xl text-sm font-black transition-all flex items-center gap-2"><i data-lucide="wallet" class="w-4 h-4"></i> Financeiro</button>
        <button @click="view='docs'" :class="view==='docs'?'bg-white shadow-sm text-indigo-600':'text-slate-500'" class="px-6 py-2 rounded-xl text-sm font-black transition-all flex items-center gap-2"><i data-lucide="file-check" class="w-4 h-4"></i> Propostas</button>
        <button @click="view='tasks'" :class="view==='tasks'?'bg-white shadow-sm text-indigo-600':'text-slate-500'" class="px-6 py-2 rounded-xl text-sm font-black transition-all flex items-center gap-2"><i data-lucide="clipboard-list" class="w-4 h-4"></i> Produção</button>
        <button @click="view='reports'" :class="view==='reports'?'bg-white shadow-sm text-indigo-600':'text-slate-500'" class="px-6 py-2 rounded-xl text-sm font-black transition-all">Relatórios</button>
      </nav>

      <div class="flex items-center gap-5">
        <a :href="settingsUrl" class="w-10 h-10 rounded-2xl flex items-center justify-center text-slate-400 hover:bg-slate-50 transition-all" title="Configurações"><i data-lucide="settings" class="w-5 h-5"></i></a>
        <button v-if="asaasOk" @click="importGateway" :disabled="importing" 
                class="px-5 py-2.5 bg-indigo-50 border border-indigo-100/50 text-indigo-600 rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center gap-2 hover:bg-indigo-500 hover:text-white hover:border-indigo-500 transition-all shadow-sm active:scale-95 disabled:opacity-50 disabled:cursor-wait"
                :class="{'animate-pulse pointer-events-none': importing}">
            <i data-lucide="cloud-download" class="w-4 h-4" :class="{'animate-spin': importing}"></i> 
            {{ importing ? 'Sincronizando' : 'Importar ' + gatewayLabel }}
        </button>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-8 pt-12">
    <!-- DASHBOARD -->
    <div v-if="view==='dashboard'">
      <div class="mb-10 flex items-center justify-between">
        <div>
            <h2 class="text-4xl font-black text-slate-900 tracking-tighter">Receita Periódica</h2>
            <p class="text-slate-500 font-medium mt-1">Visão clara e automatizada da saúde do seu faturamento.</p>
        </div>
        <div class="flex gap-3">
             <span :class="asaasOk?'badge-success':'badge-warning'" class="badge">Gateway {{ gatewayLabel }}</span>
            <span :class="waOk?'badge-success':'badge-warning'" class="badge">WhatsApp Ativo</span>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
        <div class="card-glass p-8 border-b-4 border-b-sky-500">
            <span class="input-label">Saldo em Conta Asaas</span>
            <div v-if="loadingBalance" class="mt-2 text-sky-500"><i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i></div>
            <p v-else class="text-4xl font-black text-slate-900 tracking-tighter mt-2 whitespace-nowrap">R$&nbsp;{{ formatMoney(asaasBalance) }}</p>
        </div>
        <div class="card-glass p-8 border-b-4 border-b-indigo-500">
            <span class="input-label">Receita Recorrente (MRR)</span>
            <p class="text-4xl font-black text-slate-900 tracking-tighter mt-2 whitespace-nowrap">R$&nbsp;{{ formatMoney(totalMRR) }}</p>
        </div>
        <div class="card-glass p-8 border-b-4 border-b-emerald-500">
            <span class="input-label">Base de Clientes Ativos</span>
            <p class="text-4xl font-black text-slate-900 tracking-tighter mt-2">{{ activeCount }}</p>
        </div>
        <div class="card-glass p-8 border-b-4 border-b-rose-500">
            <span class="input-label">Contas em Atraso</span>
            <p class="text-4xl font-black tracking-tighter mt-2" :class="overdueCount>0?'text-rose-600':'text-slate-900'">{{ overdueCount }}</p>
        </div>
      </div>

      <div class="card-glass overflow-hidden shadow-2xl shadow-rose-100/30 border-t-4 border-t-rose-500">
        <div class="px-8 py-6 bg-gradient-to-r from-rose-50 to-white flex justify-between items-center border-b border-rose-100">
            <h3 class="font-black text-rose-800 uppercase text-xs tracking-widest flex items-center gap-2"><i data-lucide="alert-triangle" class="w-4 h-4 text-rose-500"></i> Radar de Inadimplência</h3>
            <button v-if="overdueCount>0" @click="view='clients'; filterStatus='inadimplente';" class="text-[12px] font-black uppercase text-rose-600 hover:text-rose-700 tracking-widest bg-white border border-rose-200 px-3 py-1.5 rounded-full shadow-sm">Ver Todos</button>
        </div>
        <div v-if="loading" class="p-20 text-center"><i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto text-rose-500"></i></div>
        <div v-else-if="!clients.filter(c => c.status==='inadimplente').length" class="p-20 text-center flex flex-col items-center justify-center">
            <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-4"><i data-lucide="check-circle-2" class="w-8 h-8"></i></div>
            <p class="text-slate-900 font-black text-lg">Métricas Perfeitas</p>
            <p class="text-slate-500 font-medium text-xs mt-1">Nenhum cliente inadimplente no radar atual.</p>
        </div>
        <div v-else class="divide-y divide-rose-50">
            <div v-for="c in clients.filter(c => c.status==='inadimplente').slice(0,5)" :key="c.id" class="px-8 py-6 flex flex-col md:flex-row md:items-center justify-between hover:bg-rose-50/30 transition-all gap-4">
                <div class="flex items-center gap-5">
                    <div class="w-12 h-12 rounded-2xl bg-rose-500 text-white flex items-center justify-center font-black text-lg shadow-md shadow-rose-200">{{ c.name.charAt(0) }}</div>
                    <div>
                        <p class="font-black text-slate-900 text-base leading-tight">{{ c.name }}</p>
                        <p class="text-[10px] text-slate-400 font-bold mt-1.5 uppercase tracking-wider">{{ formatPhone(c.phone) || 'Sem contato' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right mr-4 hidden md:block">
                        <p class="font-black text-rose-600 text-lg leading-none">R$ {{ formatMoney(c.mrr) }}</p>
                        <p class="text-[11px] text-slate-400 font-bold mt-1 uppercase tracking-widest">Atrasado</p>
                    </div>
                    
                    <button v-if="c.site_status === 'blocked'" @click="toggleBlock(c)" class="p-2.5 rounded-xl border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all shadow-sm flex items-center justify-center shrink-0" title="Desbloquear Site Manualmente"><i data-lucide="lock" class="w-4 h-4"></i></button>
                    <button v-else @click="toggleBlock(c)" class="p-2.5 rounded-xl border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-200 hover:bg-rose-50 transition-all flex items-center justify-center shrink-0" title="Bloquear Site Imediatamente"><i data-lucide="unlock" class="w-4 h-4"></i></button>

                    <button @click="sendWhatsApp($event, c, '15_days_after')" class="px-4 py-2.5 rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-all shadow-md shadow-slate-200 flex items-center gap-2 text-[12px] font-black uppercase tracking-widest shrink-0" title="Disparar Acompanhamento"><i data-lucide="message-circle" class="w-3.5 h-3.5"></i> Cobrar</button>
                </div>
            </div>
        </div>
      </div>

      <!-- ERP SNAPSHOT -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12 mb-20 animate-in fade-in slide-in-from-bottom-5 duration-700">
          <div class="md:col-span-2">
              <div class="card-glass shadow-2xl shadow-indigo-100/30 border-t-4 border-t-indigo-500">
                  <div class="px-8 py-6 bg-gradient-to-r from-indigo-50/50 to-white flex justify-between items-center border-b border-indigo-100">
                      <h3 class="font-black text-indigo-800 uppercase text-xs tracking-widest flex items-center gap-2"><i data-lucide="wallet" class="w-4 h-4 text-indigo-500"></i> Fluxo de Caixa (ERP Interno)</h3>
                      <button @click="view='finance'" class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-700 tracking-widest bg-white border border-indigo-200 px-3 py-1.5 rounded-full shadow-sm">Gestão Completa</button>
                  </div>
                   <div class="p-8 grid grid-cols-2 lg:grid-cols-4 gap-6 text-center bg-white/30 backdrop-blur-xl h-full">
                       <!-- RECEBER -->
                       <div class="bg-emerald-50/50 p-6 rounded-3xl border border-emerald-100/50 flex flex-col items-center justify-center relative overflow-hidden group">
                           <p class="text-[10px] uppercase font-bold text-emerald-600 tracking-widest mb-1.5 opacity-70">Previsão de Receita</p>
                            <p class="text-xl font-black text-emerald-600 whitespace-nowrap tracking-tight">
                                <span class="text-xs font-bold opacity-70 mr-0.5">R$</span> {{ formatMoney(totalPendingIncomes) }}
                            </p>
                            <div class="mt-2 pt-2 border-t border-emerald-100 w-full">
                                <p class="text-[8px] font-bold text-emerald-400 uppercase tracking-tighter line-clamp-1">Realizado: R$&nbsp;{{ formatMoney(totalIncomes) }}</p>
                            </div>
                       </div>
                       
                       <!-- PAGAR -->
                       <div class="bg-rose-50/50 p-6 rounded-3xl border border-rose-100/50 flex flex-col items-center justify-center relative overflow-hidden group">
                           <p class="text-[10px] uppercase font-bold text-rose-600 tracking-widest mb-1.5 opacity-70">Custos & Obrigações</p>
                            <p class="text-xl font-black text-rose-600 whitespace-nowrap tracking-tight">
                                <span class="text-xs font-bold opacity-70 mr-0.5">R$</span> {{ formatMoney(totalPendingExpenses) }}
                            </p>
                            <div class="mt-2 pt-2 border-t border-rose-100 w-full">
                                <p class="text-[8px] font-bold text-rose-400 uppercase tracking-tighter line-clamp-1">Maior Ralo: {{ topExpenseCategory }}</p>
                            </div>
                       </div>

                       <!-- BALANÇO OPERACIONAL (DRE) -->
                       <div class="bg-indigo-50/50 p-6 rounded-3xl border border-indigo-100/50 flex flex-col items-center justify-center">
                           <p class="text-[10px] uppercase font-bold text-indigo-400 tracking-widest mb-1.5">Margem de Lucro (DRE)</p>
                            <p class="text-xl font-black whitespace-nowrap tracking-tight" :class="profitMargin >= 0 ? 'text-indigo-600' : 'text-rose-600'">
                                {{ profitMargin >= 0 ? '' : '-' }}{{ Math.round(Math.abs(profitMargin)) }}%
                            </p>
                            <div class="mt-2 pt-2 border-t border-indigo-100 w-full">
                                <p class="text-[8px] font-bold text-indigo-400 uppercase tracking-tighter">Resultado: R$&nbsp;{{ formatMoney(totalIncomes - totalExpenses) }}</p>
                            </div>
                       </div>

                       <!-- PROJEÇÃO DE CAIXA -->
                       <div class="bg-slate-900 p-6 rounded-3xl shadow-xl shadow-slate-200 flex flex-col items-center justify-center border border-slate-700/50" style="background: #0f172a !important;">
                           <p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest mb-1.5 flex items-center gap-1"><i data-lucide="trending-up" class="w-2.5 h-2.5"></i> Projeção de Caixa</p>
                            <p class="text-xl font-black text-white whitespace-nowrap tracking-tight">
                                <span class="text-xs font-bold opacity-50 mr-0.5">R$</span> {{ formatMoney(projectedEndBalance) }}
                           </p>
                           <div class="mt-2 pt-2 border-t border-slate-700 w-full">
                                <p class="text-[8px] font-bold text-slate-500 uppercase tracking-tighter">Líquido Atual: R$&nbsp;{{ formatMoney(asaasBalance + (totalIncomes - totalExpenses)) }}</p>
                            </div>
                       </div>
                   </div>
              </div>
          </div>
          <div class="md:col-span-1">
              <div class="card-glass shadow-2xl shadow-amber-100/30 border-t-4 border-t-amber-500">
                  <div class="px-8 py-6 bg-gradient-to-r from-amber-50/50 to-white flex justify-between items-center border-b border-amber-100">
                      <h3 class="font-black text-amber-800 uppercase text-xs tracking-widest flex items-center gap-2"><i data-lucide="file-check" class="w-4 h-4 text-amber-500"></i> Pipeline Comercial</h3>
                  </div>
                  <div class="px-8 py-6 flex flex-col items-center justify-between bg-white/50">
                      <div class="w-full">
                          <div class="flex items-center justify-between mb-4">
                              <div>
                                  <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest leading-none">Total no Mês</p>
                                  <p class="text-3xl font-black text-slate-900 tracking-tighter mt-1">{{ commercialStats.total }} <span class="text-xs font-bold opacity-30">PROPOSTAS</span></p>
                              </div>
                              <div class="bg-amber-50 px-2 py-1 rounded-lg border border-amber-100 flex items-center gap-1.5 self-start">
                                  <i data-lucide="info" class="w-2.5 h-2.5 text-amber-500"></i>
                                  <span class="text-[8px] font-black text-amber-600 uppercase tracking-widest">{{ commercialStats.pending }} Pendentes</span>
                              </div>
                          </div>

                          <!-- Mini Indicadores -->
                          <div class="grid grid-cols-2 gap-3">
                              <div class="bg-emerald-50/50 p-3 rounded-xl border border-emerald-100 flex items-center justify-between">
                                  <div>
                                      <p class="text-[7px] font-black text-emerald-400 uppercase tracking-widest">Aceitas</p>
                                      <p class="text-base font-black text-emerald-600">{{ commercialStats.accepted }}</p>
                                  </div>
                                  <div class="w-7 h-7 rounded-full bg-emerald-100/50 text-emerald-600 flex items-center justify-center">
                                      <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                  </div>
                              </div>
                              <div class="bg-rose-50/50 p-3 rounded-xl border border-rose-100 flex items-center justify-between">
                                  <div>
                                      <p class="text-[7px] font-black text-rose-400 uppercase tracking-widest">Negadas</p>
                                      <p class="text-base font-black text-rose-600">{{ commercialStats.rejected }}</p>
                                  </div>
                                  <div class="w-7 h-7 rounded-full bg-rose-100/50 text-rose-600 flex items-center justify-center">
                                      <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                  </div>
                              </div>
                          </div>

                          <!-- Proporção -->
                          <div class="mt-4 flex flex-col gap-2">
                              <div class="flex justify-between items-center text-[8px] font-black uppercase tracking-widest px-1">
                                  <span class="text-emerald-500">Taxa de Conversão</span>
                                  <span class="text-slate-900">{{ Math.round(commercialStats.conversion_rate) }}%</span>
                              </div>
                              <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden flex">
                                  <div class="h-full bg-emerald-500 transition-all duration-1000" :style="{ width: commercialStats.conversion_rate + '%' }"></div>
                              </div>
                          </div>
                      </div>

                      <button @click="view='docs'" class="w-full py-3.5 px-6 bg-slate-900 text-white text-[10px] font-black rounded-xl shadow-xl shadow-slate-200 hover:bg-slate-800 transition-all uppercase tracking-widest mt-6 flex items-center justify-center gap-2 active:scale-95">
                          <i data-lucide="plus-circle" class="w-4 h-4 text-white"></i> Novo Documento
                      </button>
                  </div>
              </div>
          </div>
      </div>
    </div>

    <!-- RELATÓRIOS E LOGS (Ideias B e C) -->
    <div v-if="view==='reports'">
        <header class="mb-12">
            <h2 class="text-4xl font-black text-slate-900 tracking-tighter">Desempenho e Logs</h2>
            <p class="text-slate-500 font-medium mt-1">Crescimento projetado de MRR e auditoria de comunicação automatizada (WhatsApp).</p>
        </header>

        <!-- META DE MRR (ELITE) -->
        <div v-if="dreData.metrics.mrr_goal > 0" class="card-glass p-8 mb-12 bg-gradient-to-r from-slate-900 to-indigo-900 border-none shadow-2xl overflow-hidden relative" style="background: linear-gradient(to right, #0f172a, #312e81) !important; border:none !important">
            <i data-lucide="target" class="absolute -right-10 -bottom-10 w-64 h-64 text-white/5 rotate-12"></i>
            <div class="relative z-10">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                    <div>
                        <span class="text-[10px] font-black uppercase text-indigo-300 tracking-widest bg-white/10 px-3 py-1 rounded-full border border-white/10">Objetivo Estratégico</span>
                        <h2 class="text-3xl font-black text-white tracking-tighter mt-4">Meta de MRR Mensal</h2>
                        <p class="text-indigo-200/60 font-bold text-sm">Acompanhamento de crescimento recorrente em tempo real.</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black uppercase text-indigo-300 tracking-widest text-indigo-300/60">Até agora</p>
                        <p class="text-4xl font-black text-white tracking-tighter mt-1">R$ {{ formatMoney(mrrAtivo) }} <span class="text-indigo-400 text-xl">/ R$ {{ formatMoney(dreData.metrics.mrr_goal) }}</span></p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between items-end">
                        <span class="text-sm font-black text-white uppercase tracking-widest">Progresso: {{ Math.min(100, Math.round((mrrAtivo / dreData.metrics.mrr_goal) * 100)) }}%</span>
                        <span v-if="mrrAtivo < dreData.metrics.mrr_goal" class="text-[10px] font-black text-indigo-300 uppercase tracking-widest">Faltam R$ {{ formatMoney(dreData.metrics.mrr_goal - mrrAtivo) }} para bater a meta</span>
                        <span v-else class="text-[10px] font-black text-emerald-400 uppercase tracking-widest flex items-center gap-2"><i data-lucide="party-popper" class="w-4 h-4"></i> Meta Batida! Parabéns!</span>
                    </div>
                    <div class="h-4 bg-white/10 rounded-full overflow-hidden p-1 border border-white/10">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-400 rounded-full transition-all duration-1000" :style="{ width: Math.min(100, (mrrAtivo / dreData.metrics.mrr_goal) * 100) + '%' }"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- INTELIGÊNCIA GERENCIAL -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <!-- MRR Saudável -->
            <div class="card-glass p-6 border-b-4 border-emerald-500 bg-gradient-to-br from-white to-emerald-50">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase text-emerald-600 tracking-widest">MRR Saudável</span>
                    <i data-lucide="trending-up" class="w-5 h-5 text-emerald-500"></i>
                </div>
                <h3 class="text-3xl font-black text-slate-900 tracking-tighter">R$ {{ formatMoney(mrrAtivo) }}</h3>
                <p class="text-[10px] text-slate-500 font-bold mt-2 uppercase tracking-widest leading-tight">Arrecadação de<br>clientes em dia</p>
            </div>

            <div class="card-glass p-6 border-b-4 border-indigo-500 md:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase text-indigo-600 tracking-widest">Desempenho Comercial (30d)</span>
                    <i data-lucide="bar-chart-3" class="w-5 h-5 text-indigo-500"></i>
                </div>
                
                <div class="grid grid-cols-12 gap-4 mt-10">
                    <div class="col-span-2 text-center">
                        <p class="text-2xl font-black text-slate-900">{{ commercialStats.total }}</p>
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-1">Propostas</p>
                    </div>
                    <div class="col-span-2 text-center border-l border-slate-100">
                        <p class="text-2xl font-black text-emerald-600">{{ Math.round(commercialStats.conversion_rate) }}%</p>
                        <p class="text-[9px] text-emerald-600/60 font-black uppercase tracking-widest mt-1">Conversão</p>
                    </div>
                    <div class="col-span-2 text-center border-l border-slate-100">
                        <p class="text-2xl font-black text-rose-500">{{ commercialStats.rejected }}</p>
                        <p class="text-[9px] text-rose-400 font-bold uppercase tracking-widest mt-1">Rejeitadas</p>
                    </div>
                    <div class="col-span-6 text-center border-l border-slate-100 bg-slate-50/50 rounded-2xl py-2">
                        <p class="text-2xl font-black text-slate-900">R$ {{ formatMoney(commercialStats.avg_ticket) }}</p>
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-1">Ticket Médio</p>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div class="flex flex-col">
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Pipeline em Aberto</span>
                        <span class="text-sm font-black text-amber-600">R$ {{ formatMoney(commercialStats.opportunity) }}</span>
                    </div>
                    <div class="h-8 w-px bg-slate-200"></div>
                    <div class="flex flex-col items-end">
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Volume Fechado</span>
                        <span class="text-sm font-black text-emerald-600">R$ {{ formatMoney(commercialStats.revenue) }}</span>
                    </div>
                </div>
            </div>

            <!-- MRR em Risco -->
            <div class="card-glass p-6 border-b-4 border-rose-500 bg-gradient-to-br from-white to-rose-50">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase text-rose-600 tracking-widest">MRR em Risco</span>
                    <i data-lucide="trending-down" class="w-5 h-5 text-rose-500"></i>
                </div>
                <h3 class="text-3xl font-black text-slate-900 tracking-tighter">R$ {{ formatMoney(mrrRisco) }}</h3>
                <p class="text-[10px] text-slate-500 font-bold mt-2 uppercase tracking-widest leading-tight">Valor retido em<br>inadimplência</p>
            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <!-- CHURN RATE -->
            <div class="card-glass p-6 border-b-4 border-amber-500 bg-gradient-to-br from-white to-amber-50">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase text-amber-600 tracking-widest">Taxa de Churn</span>
                    <i data-lucide="user-minus" class="w-5 h-5 text-amber-500"></i>
                </div>
                <h3 class="text-3xl font-black text-slate-900 tracking-tighter">{{ dreData.metrics.churn_rate }}%</h3>
                <p class="text-[10px] text-slate-500 font-bold mt-2 uppercase tracking-widest leading-tight">Representa {{ dreData.metrics.lost }} clientes<br>perdidos no período</p>
            </div>

            <!-- CICLO DE VENDAS -->
            <div class="card-glass p-6 border-b-4 border-sky-500 bg-gradient-to-br from-white to-sky-50">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase text-sky-600 tracking-widest">Ciclo de Vendas</span>
                    <i data-lucide="calendar" class="w-5 h-5 text-sky-500"></i>
                </div>
                <h3 class="text-3xl font-black text-slate-900 tracking-tighter">{{ dreData.metrics.sales_cycle }} d</h3>
                <p class="text-[10px] text-slate-500 font-bold mt-2 uppercase tracking-widest leading-tight">Média de dias para<br>fechar contrato</p>
            </div>

            <!-- TAXA DE RECUPERAÇÃO -->
            <div class="card-glass p-6 border-b-4 border-emerald-600 bg-emerald-900/5">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase text-emerald-700 tracking-widest">Sucesso de Cobrança</span>
                    <i data-lucide="shield-check" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <h3 class="text-3xl font-black text-emerald-900 tracking-tighter">{{ dreData.metrics.recovery_rate }}%</h3>
                <p class="text-[10px] text-slate-500 font-bold mt-2 uppercase tracking-widest leading-tight">Clientes recuperados<br>pela automação</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            <!-- DRE SIMPLIFICADA -->
            <div class="lg:col-span-2 card-glass p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="input-label !mb-0 text-slate-900">DRE Simplificada (6 Meses)</h3>
                        <p class="text-[11px] text-slate-400 font-bold mt-1 uppercase tracking-widest">Consolidação de Receitas vs Despesas</p>
                    </div>
                    <button @click="exportToCSV(dreData.history, 'dre-acro-manager.csv', ['month', 'income', 'expense', 'profit'])" class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 transition-colors">
                        <i data-lucide="download" class="w-4 h-4"></i> Exportar CSV
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b">
                            <tr>
                                <th class="px-5 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">Mês</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase text-emerald-500 tracking-widest text-right">Receitas</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase text-rose-500 tracking-widest text-right">Despesas</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase text-slate-900 tracking-widest text-right">Resultado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="m in dreData.history" :key="m.month" class="hover:bg-slate-50/50 transition-all">
                                <td class="px-5 py-4 text-xs font-black text-slate-600 uppercase tracking-tighter">{{ m.month }}</td>
                                <td class="px-5 py-4 text-sm font-bold text-emerald-600 text-right">R$ {{ formatMoney(m.income) }}</td>
                                <td class="px-5 py-4 text-sm font-bold text-rose-600 text-right">R$ {{ formatMoney(m.expense) }}</td>
                                <td class="px-5 py-4 text-sm font-black text-right" :class="m.profit >= 0 ? 'text-indigo-600' : 'text-rose-500'">R$ {{ formatMoney(m.profit) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ANALYTICS DE VALOR -->
            <div class="card-glass p-8 flex flex-col justify-between">
                <div>
                    <h3 class="input-label mb-8 flex items-center gap-2"><i data-lucide="gem" class="w-4 h-4 text-indigo-500"></i> Vitalidade do Negócio</h3>
                    
                    <div class="space-y-8">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Lifetime Value (LTV)</p>
                            <p class="text-4xl font-black text-indigo-600 tracking-tighter">R$ {{ formatMoney(dreData.metrics.ltv) }}</p>
                            <p class="text-[9px] text-slate-400 font-bold mt-2 leading-relaxed">Estimativa de receita por cliente<br>baseada em um ciclo de 12 meses.</p>
                        </div>
                        
                        <div class="pt-8 border-t border-slate-100">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Ticket Médio (ARPU)</p>
                            <p class="text-3xl font-black text-slate-900 tracking-tighter">R$ {{ formatMoney(dreData.metrics.avg_mrr) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 bg-indigo-50 p-6 rounded-3xl border border-indigo-100">
                    <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-3">Base Atual</p>
                    <div class="flex justify-between items-end">
                        <div class="text-center">
                            <p class="text-2xl font-black text-indigo-900">{{ dreData.metrics.active }}</p>
                            <p class="text-[8px] text-indigo-400 font-bold uppercase tracking-widest mt-1">Ativos</p>
                        </div>
                        <div class="w-px h-10 bg-indigo-200"></div>
                        <div class="text-center">
                            <p class="text-2xl font-black text-slate-400">{{ dreData.metrics.lost }}</p>
                            <p class="text-[8px] text-slate-400 font-bold uppercase tracking-widest mt-1">Lost</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            <!-- BREAKDOWN DE PRODUTOS -->
            <div class="card-glass p-8 bg-slate-50/50">
                <h3 class="input-label mb-6 flex items-center gap-2"><i data-lucide="pie-chart" class="w-4 h-4 text-indigo-500"></i> Mix de Contratos</h3>
                <div v-if="productBreakdown.length === 0" class="text-center py-10 text-slate-400 text-xs font-bold uppercase tracking-widest">Nenhum produto em base.</div>
                <div class="space-y-4">
                    <div v-for="pb in productBreakdown.slice(0, 5)" :key="pb.name" class="flex justify-between items-center p-4 bg-white rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>
                        <div class="flex flex-col pl-2">
                            <span class="font-black text-slate-900 text-sm truncate max-w-[120px]" :title="pb.name">{{ pb.name }}</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">{{ pb.count }} Contrato(s)</span>
                        </div>
                        <span class="font-black text-indigo-600 text-sm">R$ {{ formatMoney(pb.mrr) }}</span>
                    </div>
                </div>
            </div>

            <!-- Gráfico de MRR Projetado -->
            <div class="lg:col-span-2 card-glass p-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="input-label !mb-0">Crescimento Projetado (MRR) - 6 Meses</h3>
                    <div class="text-[10px] font-black uppercase tracking-[0.2em] bg-sky-50 text-sky-600 px-3 py-1.5 rounded-lg border border-sky-100 flex items-center gap-2 shadow-sm">
                        <i data-lucide="bar-chart" class="w-3.5 h-3.5"></i> Meta: R$ {{ formatMoney(metrics.projected_mrr) }}
                    </div>
                </div>
                <div class="h-[300px] w-full flex items-center justify-center relative">
                    <canvas id="mrrChart" class="absolute inset-0"></canvas>
                    <div v-if="loadingReports" class="text-indigo-600"><i data-lucide="loader-2" class="w-8 h-8 animate-spin"></i></div>
                </div>
            </div>
        </div>

        <div class="card-glass p-8 mb-12">
            <h3 class="input-label mb-6">Comparativo Histórico: Receitas vs Despesas</h3>
            <div class="h-[300px] w-full relative">
                <canvas id="dreChart"></canvas>
            </div>
        </div>
        
        <!-- Logs de Cobrança -->
        <div class="card-glass overflow-hidden shadow-2xl shadow-indigo-100/10">
            <div class="px-8 py-6 bg-slate-50/50 border-b flex justify-between items-center">
                <h3 class="font-black text-slate-800 uppercase text-xs tracking-widest flex items-center gap-2"><i data-lucide="message-square" class="w-4 h-4 text-emerald-500"></i> Auditoria de Disparos WhatsApp</h3>
                <button v-if="logs.length > 5" @click="showAllLogs = !showAllLogs" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:text-indigo-800 transition-colors">
                    {{ showAllLogs ? 'Ver Menos -' : 'Ver Todos +' }}
                </button>
            </div>
            <div v-if="loadingLogs" class="p-16 text-center text-slate-400"><i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto text-indigo-600 mb-4"></i></div>
            <div v-else-if="!logs.length" class="p-16 text-center text-slate-400 font-black text-xs uppercase tracking-widest">Nenhuma mensagem disparada recentemente.</div>
            <div v-else class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                <div v-for="log in (showAllLogs ? logs : logs.slice(0, 5))" :key="log.id" class="px-8 py-6 flex flex-col md:flex-row md:items-center justify-between hover:bg-slate-50/80 transition-all gap-4">
                    <div class="flex items-center gap-5">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-sm" :class="log.title.includes('[SUCESSO]')?'bg-emerald-100 text-emerald-600':'bg-rose-100 text-rose-600'">
                            <i :data-lucide="log.title.includes('[SUCESSO]')?'check':'x'" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="font-black text-slate-900 text-sm">{{ log.title }}</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.15em] whitespace-pre-line mt-1">{{ log.content }}</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest whitespace-nowrap">{{ log.date }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- CLIENTS VIEW -->
    <div v-if="view==='clients'">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
            <div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tighter">Carteira de Clientes</h2>
                <p class="text-slate-500 font-bold mt-2 text-sm leading-relaxed">Gerenciamento centralizado de MRR, contratos e domínios.</p>
            </div>
            <div class="flex items-center gap-4">
                <button @click="exportToCSV(filteredClients, 'clientes-acro-manager.csv', ['name', 'email', 'phone', 'mrr', 'status', 'site_url'])" class="p-4 bg-white hover:bg-slate-50 text-slate-400 hover:text-indigo-600 transition-all rounded-xl border border-slate-200 shadow-sm flex items-center gap-3 text-[10px] font-black uppercase tracking-widest">
                    <i data-lucide="download" class="w-4 h-4"></i> Exportar
                </button>
            </div>
        </div>
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-8">
            <div class="flex flex-col gap-5">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-4xl font-black text-slate-900 tracking-tighter">Monitoramento</h2>
                    
                    <button v-if="waOk && overdueCount > 0" @click="massBillOverdue" :disabled="massBilling" class="btn-primary !bg-rose-500 hover:!bg-rose-600 !py-2 !px-4 !text-[11px] !shadow-lg shadow-rose-200 ml-2">
                        <i data-lucide="send" class="w-4 h-4" :class="{'animate-pulse': massBilling}"></i> {{ massBilling ? 'Enviando Avisos...' : 'Cobrar Inadimplentes ('+overdueCount+')' }}
                    </button>
                </div>
                
                <div class="flex gap-2.5 items-center">
                    <button @click="filterStatus='todos'" :class="filterStatus==='todos'?'bg-slate-800 text-white shadow-md':'bg-slate-100 text-slate-500 hover:bg-slate-200'" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Todos</button>
                    <button @click="filterStatus='ativo'" :class="filterStatus==='ativo'?'bg-emerald-500 text-white shadow-md shadow-emerald-200':'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Em Dia</button>
                    <button @click="filterStatus='inadimplente'" :class="filterStatus==='inadimplente'?'bg-rose-500 text-white shadow-md shadow-rose-200':'bg-rose-50 text-rose-600 hover:bg-rose-100'" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        Devedores
                        <span v-if="overdueCount>0" class="bg-rose-600 text-white rounded-full px-2 py-0.5 text-[9px]">{{overdueCount}}</span>
                    </button>
                    
                    <div v-if="syncingStatus" class="ml-2 flex items-center gap-1.5 text-[9px] font-black uppercase text-slate-400 tracking-widest bg-slate-50 border border-slate-100 px-3 py-1.5 rounded-full shadow-sm"><i data-lucide="loader-2" class="w-3 h-3 animate-spin text-indigo-500"></i> Auto-Sync...</div>
                </div>
            </div>
            
            <div class="relative w-full md:w-80 flex items-center">
                <i data-lucide="search" class="input-icon"></i>
                <input v-model="search" type="text" placeholder="Localizar por nome..." class="modern-input !pr-10">
                <button v-show="search" @click="search=''" class="absolute right-3 text-slate-400 hover:text-rose-500 transition-colors p-1" title="Limpar busca"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
        </header>

        <div class="card-glass overflow-hidden shadow-2xl shadow-indigo-100/10">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Cliente</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Situação</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Recorrência</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] text-center">Gestão</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="c in filteredClients" :key="c.id" :class="c.status==='inadimplente' ? 'bg-rose-50/20 hover:bg-rose-50/50 transition-all' : 'hover:bg-indigo-50/20 transition-all'">
                        <td class="px-8 py-6 relative">
                            <div v-if="c.status==='inadimplente'" class="absolute left-0 top-0 bottom-0 w-1.5 bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.5)]"></div>
                            <p class="font-black text-slate-900">{{ c.name }}</p>
                            <div class="flex flex-col gap-1.5 mt-1.5">
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest flex items-center gap-1.5"><i data-lucide="phone" class="w-3 h-3 text-slate-300"></i>{{ formatPhone(c.phone) || 'S/ TELEFONE' }}</p>
                                <p v-if="c.product" class="text-[9px] uppercase tracking-widest text-indigo-500 font-black bg-indigo-50 border border-indigo-100 py-0.5 px-2 rounded-lg w-max"><i data-lucide="tag" class="w-2.5 h-2.5 inline-block mr-1 align-text-bottom"></i>{{ c.product }}</p>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span :class="c.status==='ativo'?'badge-success':'badge-warning'" class="badge">{{ c.status==='ativo'?'ATUALIZADO':'EM ATRASO' }}</span>
                        </td>
                        <td class="px-8 py-6 font-black text-slate-900 text-lg">R$ {{ formatMoney(c.mrr) }}</td>
                        <td class="px-8 py-6">
                            <div class="flex flex-wrap justify-end xl:justify-center gap-2">
                                <button v-if="c.site_status === 'blocked'" @click="toggleBlock(c)" class="p-2.5 rounded-xl border-2 border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all shadow-sm flex items-center justify-center shrink-0" title="Desbloquear Site Manualmente"><i data-lucide="lock" class="w-4 h-4"></i></button>
                                <button v-else @click="toggleBlock(c)" class="p-2.5 rounded-xl border-2 border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-200 hover:bg-rose-50 transition-all flex items-center justify-center shrink-0" title="Bloquear Site Imediatamente"><i data-lucide="unlock" class="w-4 h-4"></i></button>

                                <button @click="openInvoicesModal(c)" class="p-2.5 rounded-xl border-2 border-sky-100 text-sky-600 hover:bg-sky-50 transition-all flex items-center justify-center shrink-0" title="Faturas Asaas"><i data-lucide="file-text" class="w-4 h-4"></i></button>

                                <!-- WhatsApp Group -->
                                <div class="relative group flex items-center shrink-0">
                                    <button @click="sendWhatsApp($event, c, 'manual')" class="p-2.5 rounded-xl border-2 border-emerald-100 text-emerald-600 hover:bg-emerald-50 transition-all flex items-center justify-center" title="Cobrança Padrão"><i data-lucide="message-circle" class="w-4 h-4"></i></button>
                                    <!-- Dropdown Menu -->
                                    <div class="absolute right-full mr-4 top-1/2 -translate-y-1/2 hidden group-hover:flex flex-col gap-1 bg-white shadow-[0_10px_40px_-10px_rgba(0,0,0,0.2)] p-2 rounded-2xl border border-slate-200 z-[100] min-w-[180px] animate-in fade-in slide-in-from-right-2 duration-200">
                                        <button @click.prevent="sendWhatsApp($event, c, '5_days_before')" class="text-[10px] font-black text-slate-500 hover:text-indigo-600 text-left px-3 py-2 uppercase hover:bg-indigo-50 rounded-xl transition-colors whitespace-nowrap truncate"><i data-lucide="clock" class="w-3 h-3 inline-block mb-0.5 opacity-50 mr-1"></i> Lembrete Preventivo</button>
                                        <button @click.prevent="sendWhatsApp($event, c, '7_days_after')" class="text-[10px] font-black text-rose-500 hover:text-rose-600 text-left px-3 py-2 uppercase hover:bg-rose-50 rounded-xl transition-colors whitespace-nowrap truncate"><i data-lucide="alert-triangle" class="w-3 h-3 inline-block mb-0.5 opacity-50 mr-1"></i> Aviso (Atrasado 7 Dias)</button>
                                        <button @click.prevent="sendWhatsApp($event, c, '15_days_after')" class="text-[10px] font-black text-rose-700 hover:text-rose-800 text-left px-3 py-2 uppercase hover:bg-rose-100 rounded-xl transition-colors whitespace-nowrap truncate"><i data-lucide="ban" class="w-3 h-3 inline-block mb-0.5 opacity-50 mr-1"></i> Ameaça (15 Dias)</button>
                                    </div>
                                </div>

                                <button @click="syncAsaas($event, c)" class="p-2.5 rounded-xl border-2 border-indigo-100 text-indigo-600 hover:bg-indigo-50 transition-all flex items-center justify-center shrink-0" title="Sincronizar Asaas"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
                                <a :href="c.portal_url" target="_blank" class="p-2.5 rounded-xl border-2 border-slate-900 bg-slate-900 text-white hover:bg-slate-800 transition-all flex items-center justify-center shrink-0" title="Ver Portal do Cliente (Link Seguro)"><i data-lucide="external-link" class="w-4 h-4"></i></a>
                                <button @click="openEditModal(c)" class="p-2.5 rounded-xl border-2 border-blue-100 text-blue-600 hover:bg-blue-50 transition-all flex items-center justify-center shrink-0" title="Editar"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                                <button @click="deleteTarget=c" class="p-2.5 rounded-xl border-2 border-rose-100 text-rose-600 hover:bg-rose-50 transition-all flex items-center justify-center shrink-0" title="Remover"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div v-if="totalPages > 1" class="px-8 py-5 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Página {{ currentPage }} de {{ totalPages }} • {{ totalFiltered }} Clientes</span>
                <div class="flex items-center gap-2">
                    <button @click="currentPage--" :disabled="currentPage === 1" class="w-8 h-8 flex items-center justify-center border border-slate-200 bg-white rounded-lg hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                        <i data-lucide="chevron-left" class="w-4 h-4 text-slate-600"></i>
                    </button>
                    <button @click="currentPage++" :disabled="currentPage === totalPages" class="w-8 h-8 flex items-center justify-center border border-slate-200 bg-white rounded-lg hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-600"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- FINANCE VIEW (ERP Fluxo de Caixa) -->
    <div v-if="view==='finance'">
        <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tighter">Fluxo de Caixa Interno</h2>
                <p class="text-slate-500 font-medium mt-1">Gestão centralizada de receitas e despesas da sua operação.</p>
            </div>
            <button @click="showFinanceModal=true" class="btn-primary">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Novo Lançamento
            </button>
        </header>

        <div v-if="loadingFinance" class="text-center py-20 opacity-20"><i data-lucide="loader-2" class="w-12 h-12 animate-spin mx-auto text-indigo-600"></i></div>
        <div v-else class="space-y-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="card-glass p-8 border-b-4 border-b-emerald-500 bg-gradient-to-br from-white to-emerald-50 hover:shadow-xl transition-all">
                <span class="input-label">Entradas Realizadas</span>
                <p class="text-4xl font-black text-emerald-600 tracking-tighter mt-2">R$ {{ formatMoney(totalIncomes) }}</p>
            </div>
            <div class="card-glass p-8 border-b-4 border-b-rose-500 bg-gradient-to-br from-white to-rose-50 hover:shadow-xl transition-all">
                <span class="input-label">Saídas Realizadas</span>
                <p class="text-4xl font-black text-rose-600 tracking-tighter mt-2">R$ {{ formatMoney(totalExpenses) }}</p>
            </div>
            <div class="card-glass p-8 border-b-4 border-b-indigo-500 bg-gradient-to-br from-white to-indigo-50 hover:shadow-xl transition-all">
                <span class="input-label">Saldo em Caixa</span>
                <p class="text-4xl font-black text-indigo-600 tracking-tighter mt-2 whitespace-nowrap">R$&nbsp;{{ formatMoney(totalIncomes - totalExpenses) }}</p>
            </div>
        </div>

        <!-- LISTA DE LANÇAMENTOS -->
        <div class="card-glass overflow-hidden shadow-2xl shadow-indigo-100/10">
            <div class="px-8 py-6 bg-slate-50/50 border-b flex flex-col gap-6">
                <!-- Linha 1: Título e Filtro de Período Rápido -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <h3 class="input-label !mb-0 text-slate-800">Fluxo de Caixa</h3>
                        <div class="flex items-center bg-slate-200/50 p-1 rounded-xl shadow-inner border border-slate-300/30">
                            <button @click="setFinancePeriod('today')" 
                                    :class="financePeriod === 'today' ? 'bg-white shadow-sm text-indigo-600 rounded-lg' : 'text-slate-500 hover:text-indigo-600'" 
                                    class="px-3 py-1.5 text-[10px] font-black uppercase transition-all">Hoje</button>
                            <button @click="setFinancePeriod('week')" 
                                    :class="financePeriod === 'week' ? 'bg-white shadow-sm text-indigo-600 rounded-lg' : 'text-slate-500 hover:text-indigo-600'" 
                                    class="px-3 py-1.5 text-[10px] font-black uppercase transition-all border-x border-slate-300/30">Semana</button>
                            <button @click="setFinancePeriod('month')" 
                                    :class="financePeriod === 'month' ? 'bg-white shadow-sm text-indigo-600 rounded-lg' : 'text-slate-500 hover:text-indigo-600'" 
                                    class="px-3 py-1.5 text-[10px] font-black uppercase transition-all">Mês</button>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest hidden md:block">Intervalo Personalizado:</span>
                        <div class="flex items-center gap-2 bg-white border border-slate-200 p-1.5 rounded-xl text-slate-500 shadow-sm mr-2">
                            <div class="flex items-center gap-2 px-2 border-r border-slate-100">
                                <span class="text-[11px] font-black uppercase tracking-tighter">De</span>
                                <input type="date" v-model="financeDateStart" class="bg-transparent border-none p-0 text-[11px] font-black text-slate-900 focus:ring-0 cursor-pointer">
                            </div>
                            <div class="flex items-center gap-2 px-2">
                                <span class="text-[11px] font-black uppercase tracking-tighter">Até</span>
                                <input type="date" v-model="financeDateEnd" class="bg-transparent border-none p-0 text-[11px] font-black text-slate-900 focus:ring-0 cursor-pointer">
                            </div>
                        </div>

                        <button @click="printFinanceReport" :disabled="!filteredTransactions.length" class="h-[38px] px-4 bg-slate-900 text-white rounded-xl flex items-center gap-2 hover:bg-indigo-600 transition-all shadow-lg shadow-slate-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                            <span class="text-[11px] font-black uppercase tracking-widest hidden sm:inline">Imprimir</span>
                        </button>
                    </div>
                </div>

                <!-- Linha 2: Filtros de Tipo e Status -->
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <div class="flex items-center bg-slate-100 p-1 rounded-xl">
                        <button @click="financeHistoryTab='all'" :class="financeHistoryTab==='all' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 text-[12px] font-black uppercase tracking-widest rounded-lg transition-all">Todos Lançamentos</button>
                        <button @click="financeHistoryTab='income'" :class="financeHistoryTab==='income' ? 'bg-white shadow-sm text-emerald-600' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 text-[12px] font-black uppercase tracking-widest rounded-lg transition-all">Somente Receitas</button>
                        <button @click="financeHistoryTab='expense'" :class="financeHistoryTab==='expense' ? 'bg-white shadow-sm text-rose-600' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-1.5 text-[12px] font-black uppercase tracking-widest rounded-lg transition-all">Somente Despesas</button>
                    </div>

                    <div class="hidden md:block w-px h-6 bg-slate-200 mx-2"></div>

                    <div class="flex items-center bg-slate-100 p-1 rounded-xl">
                        <button @click="financeStatusFilter='all'" :class="financeStatusFilter==='all' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-500'" class="px-4 py-1.5 text-[12px] font-black uppercase tracking-widest rounded-lg transition-all">Total</button>
                        <button @click="financeStatusFilter='pago'" :class="financeStatusFilter==='pago' ? 'bg-white shadow-sm text-slate-900 font-black' : 'text-slate-500 font-bold'" class="px-4 py-1.5 text-[12px] uppercase tracking-widest rounded-lg transition-all">Pagos / Recebidos</button>
                        <button @click="financeStatusFilter='pendente'" :class="financeStatusFilter==='pendente' ? 'bg-white shadow-sm text-amber-600 font-black' : 'text-slate-500 font-bold'" class="px-4 py-1.5 text-[12px] uppercase tracking-widest rounded-lg transition-all">A Pagar / Receber</button>
                    </div>
                </div>
            </div>
            <table class="w-full text-left">
                <thead class="bg-slate-50/30 border-b text-slate-400">
                    <tr>
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em]">Data</th>
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em]">Descrição</th>
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em]">Fluxo</th>
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em]">Status</th>
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em]">Categoria</th>
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-right">Valor</th>
                        <th class="px-8 py-5 text-[11px] font-black uppercase tracking-[0.2em] text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loadingFinance" class="text-center">
                        <td colspan="7" class="py-20 text-slate-400"><i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto text-indigo-600"></i></td>
                    </tr>
                    <tr v-else-if="!filteredTransactions.length">
                        <td colspan="7" class="py-32">
                            <div class="flex flex-col items-center justify-center text-center w-full">
                                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center text-slate-200 mb-6 shrink-0 shadow-inner">
                                    <i data-lucide="receipt" class="w-10 h-10"></i>
                                </div>
                                <h4 class="text-slate-400 font-black text-[10px] uppercase tracking-[0.2em] mb-2">Nenhuma transação encontrada</h4>
                                <p class="text-slate-300 font-bold text-[10px] uppercase tracking-widest max-w-[300px] leading-relaxed">Tente mudar o filtro ou adicione um novo lançamento manual.</p>
                            </div>
                        </td>
                    </tr>
                        <tr v-for="t in filteredTransactions" :key="t.id" :class="{'bg-rose-50/50': t.status==='pendente' && isOverdue(t.date)}" class="hover:bg-slate-50 transition-all group">
                            <td class="px-8 py-6 text-sm font-bold whitespace-nowrap" :class="t.status==='pendente' && isOverdue(t.date) ? 'text-rose-600' : 'text-slate-500'">
                                {{ t.human_date }}
                                <div v-if="t.status==='pendente' && isOverdue(t.date)" class="text-[10px] font-black uppercase tracking-tighter text-rose-500 mt-1 flex items-center gap-1">
                                    <i data-lucide="alert-circle" class="w-2.5 h-2.5"></i> Vencido / Atrasado
                                </div>
                            </td>
                            <td class="px-8 py-6 font-black text-slate-900">
                                {{ t.description }}
                                <span v-if="t.recurring" class="ml-2 inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-indigo-400 border border-indigo-100 px-1.5 py-0.5 rounded">
                                    <i data-lucide="repeat" class="w-2.5 h-2.5"></i> Recorrente
                                </span>
                                <span v-if="t.asaas_id" class="ml-2 inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-sky-500 bg-sky-50 border border-sky-100 px-1.5 py-0.5 rounded">
                                    <i data-lucide="refresh-cw" class="w-2.5 h-2.5"></i> Automático (Asaas)
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <span v-if="t.type==='income'" class="flex items-center gap-1.5 text-[9px] font-black text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-0.5 rounded-md w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Entrada
                                </span>
                                <span v-else class="flex items-center gap-1.5 text-[9px] font-black text-rose-600 uppercase tracking-widest bg-rose-50 px-2 py-0.5 rounded-md w-fit">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Saída
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <span v-if="t.status==='pago'" class="text-[9px] font-black uppercase tracking-widest text-slate-300 flex items-center gap-1">
                                    <i data-lucide="check" class="w-3 h-3"></i> Efetuado
                                </span>
                                <span v-else class="text-[9px] font-black uppercase tracking-widest text-amber-600 bg-amber-50 px-2 py-1 rounded-full flex items-center gap-1 w-fit shadow-sm border border-amber-100">
                                    <i data-lucide="clock" class="w-3 h-3"></i> Pendente
                                </span>
                            </td>
                            <td class="px-8 py-6">
                            <span class="px-3 py-1 bg-slate-100 text-slate-600 text-[10px] font-black rounded-lg uppercase tracking-widest border border-slate-200">{{ t.category }}</span>
                        </td>
                        <td class="px-8 py-6 text-right font-black whitespace-nowrap" :class="t.type==='income'?'text-emerald-600':'text-rose-600'">
                            {{ t.type==='income'?'+':'-' }} R$&nbsp;{{ formatMoney(t.amount) }}
                        </td>
                         <td class="px-8 py-6 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button v-if="t.status==='pendente'" @click="markAsPaid(t)" class="w-8 h-8 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-500 hover:text-white transition-all shadow-sm" title="Marcar como Pago">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </button>
                                <button @click="openFinanceEditModal(t)" class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-400 rounded-lg hover:bg-white hover:text-indigo-600 hover:shadow-md transition-all" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </button>
                                <button @click="handleDeleteTransaction(t.id)" class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-400 rounded-lg hover:bg-white hover:text-rose-500 hover:shadow-md transition-all" title="Excluir">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4h6v2"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
      </div>
    </div>

    <!-- DOCUMENTS VIEW (Propostas e Contratos) -->
    <div v-if="view==='docs'">
        <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tighter">Propostas e Contratos</h2>
                <p class="text-slate-500 font-medium mt-1">Feche mais negócios com orçamentos profissionais e links compartilháveis.</p>
            </div>
            <button @click="openDocModal" class="btn-primary">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Novo Documento
            </button>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="card-glass p-6 border-b-4 border-slate-300">
                <span class="input-label">Documentos Totais</span>
                <p class="text-3xl font-black text-slate-900 mt-2">{{ documents.length }}</p>
            </div>
            <div class="card-glass p-6 border-b-4 border-amber-400">
                <span class="input-label">Aguardando Resposta</span>
                <p class="text-3xl font-black text-amber-600 mt-2">{{ documents.filter(d => d.status==='pendente').length }}</p>
            </div>
            <div class="card-glass p-6 border-b-4 border-emerald-500">
                <span class="input-label">Convertidos (Aceitos)</span>
                <p class="text-3xl font-black text-emerald-600 mt-2">{{ documents.filter(d => d.status==='aceito').length }}</p>
            </div>
            <div class="card-glass p-6 border-b-4 border-indigo-500">
                <span class="input-label">Volume de Orçamentos</span>
                <p class="text-3xl font-black text-indigo-600 mt-2">R$ {{ formatMoney(documents.reduce((a,d)=>a+parseFloat(d.total||0),0)) }}</p>
            </div>
        </div>

        <div class="card-glass overflow-hidden shadow-2xl shadow-indigo-100/10">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Data</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Título / Cliente</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Valor</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Status</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loadingDocs" class="text-center">
                        <td colspan="5" class="py-20 text-indigo-600"><i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto"></i></td>
                    </tr>
                    <tr v-else-if="!documents.length" class="text-center py-20">
                        <td colspan="5" class="py-20 flex flex-col items-center justify-center">
                             <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-300 mb-4"><i data-lucide="file-text" class="w-8 h-8"></i></div>
                             <p class="text-slate-400 font-black text-xs uppercase tracking-widest">Inicie criando uma proposta para seus clientes.</p>
                        </td>
                    </tr>
                    <tr v-for="doc in documents" :key="doc.id" class="hover:bg-slate-50 transition-all">
                        <td class="px-8 py-6 text-xs font-bold text-slate-500 tracking-widest">{{ doc.date }}</td>
                        <td class="px-8 py-6">
                            <p class="font-black text-slate-900 leading-tight">{{ doc.title }}</p>
                            <p class="text-[10px] text-indigo-500 font-black uppercase tracking-widest mt-1">{{ doc.client_name }}</p>
                        </td>
                        <td class="px-8 py-6 font-black text-slate-900">R$ {{ formatMoney(doc.total) }}</td>
                        <td class="px-8 py-6">
                            <span :class="doc.status==='aceito'?'badge-success':(doc.status==='pendente'?'badge-warning':'badge-danger')" class="badge uppercase">{{ doc.status==='aceito'?'ACEITO':doc.status }}</span>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex items-center justify-center gap-3">
                                <button v-if="doc.status !== 'aceito'" @click="acceptDocument(doc)" class="p-2.5 rounded-xl border-2 border-emerald-100 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm flex items-center justify-center" :title="doc.type === 'contrato' ? 'Aceitar Contrato' : 'Aceitar Orçamento'"><i data-lucide="check-circle" class="w-4 h-4"></i></button>
                                <button v-else class="p-2.5 rounded-xl border-2 border-slate-100 text-slate-300 cursor-not-allowed flex items-center justify-center" title="Documento Finalizado e Aceito"><i data-lucide="lock" class="w-4 h-4"></i></button>
                                <button v-if="doc.status !== 'aceito'" @click="openDocEditModal(doc)" class="p-2.5 rounded-xl border-2 border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-indigo-600 transition-all shadow-sm flex items-center justify-center" :title="doc.type === 'contrato' ? 'Editar Contrato' : 'Editar Proposta'"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                                <a v-if="doc.public_url" :href="doc.public_url" target="_blank" class="p-2.5 rounded-xl border-2 border-indigo-100 text-indigo-600 hover:bg-indigo-50 transition-all shadow-sm flex items-center justify-center" title="Visualizar Link Público"><i data-lucide="external-link" class="w-4 h-4"></i></a>
                                <button v-if="doc.public_url" @click="copyDocLink(doc)" class="p-2.5 rounded-xl border-2 border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-indigo-600 transition-all shadow-sm flex items-center justify-center" title="Copiar Link"><i data-lucide="copy" class="w-4 h-4"></i></button>
                                <button @click="printProposal(doc)" class="p-2.5 rounded-xl border-2 border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-100 transition-all shadow-sm flex items-center justify-center" :title="doc.type === 'contrato' ? 'Imprimir Contrato' : 'Imprimir Orçamento'"><i data-lucide="printer" class="w-4 h-4"></i></button>
                                <button @click="sendDocWhatsApp(doc)" class="p-2.5 rounded-xl border-2 border-emerald-100 text-emerald-600 hover:bg-emerald-50 transition-all shadow-sm flex items-center justify-center" title="Enviar WhatsApp"><i data-lucide="message-circle" class="w-4 h-4"></i></button>
                                <button @click="handleDeleteDoc(doc.id)" class="p-2.5 text-slate-300 hover:text-rose-500 transition-colors pointer-cursor"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PRODUCTION KANBAN (Tarefas) -->
    <div v-if="view==='tasks'">
        <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tighter">Fluxo de Produção</h2>
                <p class="text-slate-500 font-medium mt-1">Gerencie a entrega dos seus serviços e acompanhe o progresso técnico.</p>
            </div>
            <button @click="openTaskModal" class="btn-primary !bg-indigo-600 shadow-indigo-100">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Nova Tarefa
            </button>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div v-for="col in taskColumns" :key="col.id" 
                 :class="col.bg"
                 class="flex flex-col h-full rounded-[40px] border border-slate-100 p-6 min-h-[70vh] transition-all" 
                 @dragover.prevent @drop="onDropTask($event, col.id)">
                <div class="flex items-center justify-between mb-8 px-2">
                    <h3 class="font-black text-[11px] uppercase tracking-[0.3em] text-slate-400 flex items-center gap-2">
                        <span :class="col.color" class="w-2.5 h-2.5 rounded-full shadow-lg"></span>
                        {{ col.name }}
                    </h3>
                    <span class="bg-indigo-50 text-indigo-600 rounded-full px-3 py-1 text-[10px] font-black border border-indigo-100">{{ tasks.filter(t => t.status===col.id).length }}</span>
                </div>

                <div class="space-y-6">
                    <div v-if="loadingTasks" class="text-center py-20 opacity-20"><i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto"></i></div>
                    <template v-else>
                      <div v-for="task in tasks.filter(t => t.status===col.id)" :key="task.id" 
                           draggable="true" 
                           @dragstart="onDragTask($event, task)"
                           class="card-glass p-6 border-l-4 hover:shadow-2xl hover:-translate-y-1 transition-all cursor-move group"
                           :class="task.priority==='high'?'border-rose-500':(task.priority==='medium'?'border-amber-400':'border-slate-300')">
                        
                        <div class="flex flex-col gap-4">
                            <div class="flex justify-between items-start gap-4">
                                <h4 class="font-black text-slate-900 text-base leading-tight flex-1">{{ task.title }}</h4>
                                <div class="flex gap-1 shrink-0">
                                    <button @click="openTaskEditModal(task)" class="p-1.5 text-slate-300 hover:text-indigo-600 transition-all"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i></button>
                                </div>
                            </div>
                            
                            <p v-if="task.description" class="text-xs text-slate-400 line-clamp-2 leading-relaxed">{{ task.description }}</p>

                            <div v-if="task.client_id || task.client_name" class="flex items-center gap-2 px-3 py-1.5 bg-indigo-50/50 rounded-xl border border-indigo-100/50 w-max">
                                <i data-lucide="user" class="w-3 h-3 text-indigo-400"></i>
                                <span class="text-[9px] font-black text-indigo-600 uppercase tracking-widest">
                                    {{ task.client_id ? (clients.find(c => c.id == task.client_id)?.name || task.client_name || 'Cliente') : task.client_name }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between pt-3 border-t border-slate-50">
                                <div class="flex items-center gap-2">
                                    <span :class="task.priority==='high'?'text-rose-500':(task.priority==='medium'?'text-amber-500':'text-slate-400')" class="flex items-center gap-1 text-[9px] font-black uppercase tracking-widest">
                                        <i data-lucide="zap" class="w-3 h-3"></i> {{ task.priority }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest">{{ task.date }}</span>
                                    <button @click="handleDeleteTask(task.id)" class="opacity-0 group-hover:opacity-100 p-1 text-slate-300 hover:text-rose-500 transition-all"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                </div>
                            </div>
                        </div>
                      </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ CRM PIPELINE ══ -->
    <div v-if="view==='crm'" class="h-full flex flex-col pb-10">
        <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tighter">Sales & Success Pipeline</h2>
                <div class="flex bg-slate-100 p-1.5 rounded-2xl w-max mt-4">
                    <button @click="crmTab='sales'" :class="crmTab==='sales'?'bg-white shadow-sm text-slate-900':'text-slate-500'" class="px-6 py-2 rounded-xl text-[10px] font-black transition-all uppercase tracking-widest">Aquisição e Setup</button>
                    <button @click="crmTab='success'" :class="crmTab==='success'?'bg-white shadow-sm text-slate-900':'text-slate-500'" class="px-6 py-2 rounded-xl text-[10px] font-black transition-all uppercase tracking-widest">Retenção e Risco</button>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="text-[10px] uppercase font-black tracking-widest text-slate-400 bg-slate-100 py-2 px-4 rounded-xl flex items-center gap-2 hidden lg:flex"><i data-lucide="zap" class="w-3 h-3 text-emerald-500"></i> Smart Engine Asaas</div>
                <div class="relative w-full md:w-64 flex items-center">
                    <i data-lucide="search" class="input-icon"></i>
                    <input v-model="search" type="text" placeholder="Filtrar CRM..." class="modern-input !text-sm !h-12 !rounded-xl !pr-10">
                    <button v-show="search" @click="search=''" class="absolute right-3 text-slate-400 hover:text-rose-500 transition-colors p-1" title="Limpar busca"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                <button v-if="crmTab==='sales'" @click="openLeadModal" class="btn-primary !h-12 !px-5 !rounded-xl shadow-md"><i data-lucide="user-plus" class="w-4 h-4"></i> Lead Manual</button>
            </div>
        </header>
        
        <div class="flex gap-6 overflow-x-auto pb-8 items-start snap-x" style="min-height: 550px;">
            <div v-for="column in crmColumns" :key="column.id" class="flex flex-col bg-slate-50/80 rounded-3xl w-[320px] shrink-0 p-5 border shadow-inner snap-center" :class="column.id === 'risk' ? 'border-rose-100 bg-rose-50/30' : 'border-slate-200'" @dragover.prevent @dragenter.prevent @drop="onDrop($event, column.id)">
                <h3 class="font-black text-slate-800 text-sm mb-5 flex items-center justify-between">
                    <span class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full shadow-sm" :class="column.id === 'risk' ? 'bg-rose-500' : (column.id === 'lost' ? 'bg-slate-800' : (column.id === 'ativo' ? 'bg-emerald-500' : (column.id === 'onboarding' ? 'bg-sky-500' : 'bg-slate-400')))"></div> {{ column.label }}</span>
                    <span class="bg-white px-2.5 py-1 rounded-lg text-[10px] shadow-sm border border-slate-100 text-slate-500">{{ crmClients.filter(c => getPipelineStage(c) === column.id).length }}</span>
                </h3>
                
                <div class="flex flex-col gap-4 min-h-[150px]">
                    <div v-for="c in crmClients.filter(c => getPipelineStage(c) === column.id)" :key="c.id" 
                         class="card-glass p-5 bg-white transition-all shadow-sm group relative" 
                         :class="[getPipelineStage(c) === 'risk' ? 'border-rose-300 shadow-rose-100 bg-rose-50/50' : 'hover:border-indigo-300 hover:shadow-lg hover:shadow-indigo-100/50', (getPipelineStage(c) === 'risk' || getPipelineStage(c) === 'lost') ? 'cursor-not-allowed' : 'cursor-grab active:cursor-grabbing']"
                         :draggable="getPipelineStage(c) !== 'risk' && getPipelineStage(c) !== 'lost'" 
                         @dragstart="onDragStart($event, c)">
                        
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-10 h-10 rounded-2xl text-white flex items-center justify-center font-black text-lg shadow-md shrink-0 transition-transform group-hover:scale-105" :class="getPipelineStage(c) === 'risk' ? 'bg-rose-500' : (getPipelineStage(c) === 'lost' ? 'bg-slate-800' : (getPipelineStage(c) === 'ativo' ? 'bg-emerald-500' : (getPipelineStage(c) === 'onboarding' ? 'bg-sky-500' : 'bg-slate-400')))">{{ c.name.charAt(0).toUpperCase() }}</div>
                            <div class="flex-1 min-w-0">
                                <p class="font-black text-sm text-slate-900 truncate leading-tight mt-0.5">{{ c.name }}</p>
                                <p v-if="c.product" class="text-[8px] uppercase tracking-widest text-indigo-500 font-black bg-indigo-50 border border-indigo-100 py-0.5 px-1.5 rounded-md mt-1 mb-1 w-max max-w-[130px] truncate"><i data-lucide="tag" class="w-2 h-2 inline-block mr-0.5 align-text-bottom"></i>{{ c.product }}</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest truncate mt-0.5 flex items-center gap-1" v-if="c.site_url"><i data-lucide="globe" class="w-2.5 h-2.5"></i>{{ c.site_url }}</p>
                                <p v-if="c.notes" class="text-[10px] text-slate-400 italic mt-2 line-clamp-2 border-l-2 border-indigo-100 pl-2 leading-relaxed">{{ c.notes }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                            <span class="text-xs font-black" :class="getPipelineStage(c) === 'risk' ? 'text-rose-600' : 'text-slate-900'">R$ {{ formatMoney(c.mrr) }}</span>
                            <div class="flex gap-1.5 items-center">
                                <button v-if="getPipelineStage(c) === 'onboarding' || getPipelineStage(c) === 'ativo'" @click.prevent="moveStageByArrow(c, -1)" class="p-1.5 bg-slate-50 hover:bg-slate-200 text-slate-400 hover:text-slate-600 rounded-[10px] transition-colors" title="Voltar Etapa"><i data-lucide="chevron-left" class="w-3.5 h-3.5"></i></button>
                                
                                <span v-if="c.status==='inadimplente'" class="text-[9px] uppercase tracking-widest bg-rose-600 text-white px-2 py-1 rounded-[8px] font-black flex items-center gap-1 shadow-md shadow-rose-200" title="Atrasado no Asaas"><i data-lucide="alert-triangle" class="w-3 h-3"></i> Fatura</span>
                                <span v-if="c.site_status==='blocked'" class="text-[9px] uppercase tracking-widest bg-slate-900 text-white px-2 py-1 rounded-[8px] font-black flex items-center gap-1 shadow-md" title="Site Offline"><i data-lucide="lock" class="w-3 h-3"></i> Trancado</span>
                                <button v-if="getPipelineStage(c) !== 'risk' && getPipelineStage(c) !== 'lost'" @click.prevent="openEditModal(c)" class="p-1.5 bg-slate-50 hover:bg-slate-200 text-slate-400 hover:text-indigo-600 rounded-[10px] transition-colors" title="Cadastrar Site"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>
                                <button v-if="getPipelineStage(c) === 'risk'" @click="sendWhatsApp($event, c, '15_days_after')" class="p-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[10px] transition-colors flex items-center shadow-md" title="Mandar Cobrança Nativa"><i data-lucide="send" class="w-3.5 h-3.5"></i></button>
                                <a :href="getWaLink(c.phone)" target="_blank" v-if="c.phone" class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-[10px] transition-colors shadow-sm" title="Conversar no WhatsApp"><i data-lucide="message-circle" class="w-3.5 h-3.5"></i></a>
                                
                                <button v-if="getPipelineStage(c) === 'prospect' || getPipelineStage(c) === 'onboarding'" @click.prevent="moveStageByArrow(c, 1)" class="p-1.5 bg-slate-50 hover:bg-slate-200 text-slate-400 hover:text-slate-600 rounded-[10px] transition-colors" title="Avançar Etapa"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i></button>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="!crmClients.filter(c => getPipelineStage(c) === column.id).length" class="h-24 border-2 border-dashed border-slate-200 bg-white/50 rounded-2xl flex items-center justify-center text-slate-400 text-[10px] font-black uppercase tracking-widest transition-colors mb-auto">
                        Arrastar Card
                    </div>
                </div>
            </div>
        </div>
    </div>
  </main>

  <!-- ══ ONBOARDING / EDIT MODAL ══ -->
  <transition name="fade">
    <div v-if="showModal" class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
        <div class="absolute inset-0 modal-overlay" @click="showModal=false"></div>
        <div class="relative bg-white w-full max-w-xl rounded-[40px] modal-card overflow-hidden">
            <header class="px-12 pt-12 pb-6">
                <div class="w-16 h-16 bg-indigo-600 rounded-3xl flex items-center justify-center text-white shadow-2xl shadow-indigo-100 mb-8">
                    <i :data-lucide="editTarget ? 'pencil' : 'user-plus'" class="w-8 h-8"></i>
                </div>
                <h3 class="text-4xl font-black text-slate-900 tracking-tighter">{{ editTarget ? 'Editar Registro' : 'Novo Lead' }}</h3>
                <p class="text-slate-500 font-bold mt-2">{{ editTarget ? 'Atualize as informações do contrato e Domínio do Site.' : 'Cadastre um prospecto manual para acompanhamento.' }}</p>
            </header>
            
            <form @submit.prevent="saveClient" class="px-12 pb-12 space-y-8">
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="input-label">Razão Social / Nome</label>
                            <div class="relative">
                                <i data-lucide="briefcase" class="input-icon"></i>
                                <input v-model="form.name" type="text" class="modern-input" placeholder="Ex: Acromidia Digital Group" required>
                            </div>
                        </div>
                        <div>
                            <label class="input-label">Domínio Oficial do Cliente</label>
                            <div class="relative">
                                <i data-lucide="globe" class="input-icon"></i>
                                <input v-model="form.site_url" type="text" class="modern-input" placeholder="ex: cliente.com.br">
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="input-label">WhatsApp Corporativo</label>
                            <div class="relative">
                                <i data-lucide="phone" class="input-icon"></i>
                                <input :value="displayPhone" @input="handlePhoneInput" type="text" class="modern-input" placeholder="(00) 00000-0000">
                            </div>
                        </div>
                        <div>
                            <label class="input-label">Valor Estimado (MRR)</label>
                            <div class="relative">
                                <div class="input-icon font-black text-[12px] text-slate-400">R$</div>
                                <input :value="displayMRR" @input="handleMRRInput" type="text" class="modern-input" placeholder="R$ 0,00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1">
                        <div>
                            <label class="input-label">Serviço / Produto Adquirido (Opcional)</label>
                            <div class="relative">
                                <i data-lucide="tag" class="input-icon"></i>
                                <input v-model="form.product" type="text" list="productList" class="modern-input !text-sm" placeholder="Ex: Site Institucional, Landing Page, Tráfego Pago...">
                                <datalist id="productList">
                                    <option v-for="opt in productOptions" :key="opt" :value="opt">{{ opt }}</option>
                                </datalist>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1">
                    <div>
                        <label class="input-label">Observações de Follow-up (CRM)</label>
                        <div class="relative">
                            <i data-lucide="message-square" class="input-icon !top-6"></i>
                            <textarea v-model="form.notes" class="modern-input modern-textarea" placeholder="Anotações sobre a negociação, próximos passos ou detalhes técnicos..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-6 pt-6">
                    <button type="button" @click="showModal=false" class="flex-1 py-5 text-xs font-black text-slate-400 hover:text-indigo-600 transition-colors uppercase tracking-widest">Abandonar</button>
                    <button type="submit" class="flex-[2] h-16 bg-indigo-600 text-white text-sm font-black rounded-3xl shadow-2xl shadow-indigo-200 hover:bg-indigo-700 transition-all uppercase tracking-[0.1em] flex items-center justify-center gap-3" :disabled="saving">
                        <i v-if="saving" data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                        {{ saving ? 'Sincronizando' : 'Salvar Alterações' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
  </transition>

  <!-- DELETE -->
  <transition name="fade">
    <div v-if="deleteTarget" class="fixed inset-0 z-[1000] flex items-center justify-center p-4 text-center">
        <div class="absolute inset-0 modal-overlay" @click="deleteTarget=null"></div>
        <div class="relative bg-white p-12 max-w-sm w-full rounded-[40px] modal-card">
            <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-[30px] flex items-center justify-center mx-auto mb-8">
                <i data-lucide="alert-triangle" class="w-10 h-10"></i>
            </div>
            <h3 class="text-3xl font-black text-slate-900 mb-2">Excluir?</h3>
            <p class="text-slate-500 font-bold mb-10 text-sm leading-relaxed">Você está prestes a remover <br><b>{{ deleteTarget.name }}</b> da sua base.</p>
            <div class="flex flex-col gap-4">
                <button @click="executeDelete" class="w-full py-5 bg-rose-500 text-white font-black rounded-3xl shadow-xl shadow-rose-200 text-xs uppercase tracking-widest">Confirmar Exclusão</button>
                <button @click="deleteTarget=null" class="w-full py-5 text-slate-400 font-black text-xs uppercase tracking-widest">Caminho de Volta</button>
            </div>
        </div>
    </div>
  </transition>

  <!-- MODAL DE FATURAS (VISÃO 360) -->
  <transition name="fade">
    <div v-if="showInvoicesModal" class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
        <div class="absolute inset-0 modal-overlay" @click="showInvoicesModal=false"></div>
        <div class="relative bg-white w-full max-w-2xl rounded-[40px] modal-card overflow-hidden">
            <header class="px-12 pt-12 pb-6 border-b border-slate-100">
                <div class="w-16 h-16 bg-sky-600 rounded-3xl flex items-center justify-center text-white shadow-2xl shadow-sky-100 mb-8">
                    <i data-lucide="banknote" class="w-8 h-8"></i>
                </div>
                <h3 class="text-3xl font-black text-slate-900 tracking-tighter">Histórico Financeiro</h3>
                <p class="text-slate-500 font-bold mt-2 text-sm">Visão 360º de faturas em tempo real do banco emissor.</p>
            </header>
            
            <div class="px-12 py-8 bg-slate-50 min-h-[300px] max-h-[50vh] overflow-y-auto">
                <div v-if="loadingInvoices" class="flex flex-col items-center justify-center h-full text-sky-600 py-12">
                    <i data-lucide="loader-2" class="w-10 h-10 animate-spin mb-4"></i>
                    <p class="font-black text-xs uppercase tracking-widest text-slate-400">Puxando Dados da Conta...</p>
                </div>
                <div v-else-if="!invoices.length" class="text-center py-12">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-300 mx-auto mb-4"><i data-lucide="inbox" class="w-8 h-8"></i></div>
                    <p class="text-slate-400 font-black text-xs uppercase tracking-widest">Nenhuma fatura encontrada.</p>
                </div>
                <div v-else class="space-y-4">
                    <div v-for="inv in invoices" :key="inv.id" class="p-6 bg-white rounded-3xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between shadow-sm hover:shadow-md transition-all gap-4">
                        <div>
                            <p class="font-black text-slate-900 text-xl tracking-tighter">R$ {{ formatMoney(inv.value) }}</p>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">
                                Vencimento: <span class="text-slate-600">{{ new Date(inv.dueDate + 'T00:00:00').toLocaleDateString('pt-BR') }}</span>
                            </p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">{{ inv.description || 'Fatura Padrão' }}</p>
                        </div>
                        <div class="flex flex-col items-start md:items-end gap-3">
                            <span v-if="inv.status==='RECEIVED'" class="badge badge-success !text-[10px] !px-3">PAGO</span>
                            <span v-else-if="inv.status==='OVERDUE'" class="badge badge-danger !text-[10px] !px-3">VENCIDO</span>
                            <span v-else class="badge badge-warning !text-[10px] !px-3">AGUARDANDO</span>
                            
                            <a v-if="inv.invoiceUrl" :href="inv.invoiceUrl" target="_blank" class="text-xs font-black text-sky-600 hover:text-sky-700 hover:underline uppercase flex items-center gap-1">
                                Visualizar PDF <i data-lucide="external-link" class="w-3 h-3"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <footer class="px-12 py-6 bg-white flex justify-end">
                <button @click="showInvoicesModal=false" class="py-4 px-8 bg-slate-900 text-white text-xs font-black rounded-3xl shadow-xl shadow-slate-200 hover:bg-slate-800 transition-all uppercase tracking-[0.1em]">Fechar Painel</button>
            </footer>
        </div>
    </div>
  </transition>

  <!-- MODAL DE LANÇAMENTO FINANCEIRO -->
  <transition name="fade">
    <div v-if="showFinanceModal" class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
        <div class="absolute inset-0 modal-overlay" @click="showFinanceModal=false"></div>
        <div class="relative bg-white w-full max-w-lg rounded-[40px] modal-card overflow-hidden">
            <header class="px-10 pt-10 pb-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-3xl font-black text-slate-900 tracking-tighter">{{ (editTarget && editTarget.type==='finance') ? 'Editar Lançamento' : 'Novo Lançamento' }}</h3>
                    <p class="text-slate-400 font-bold mt-1 text-xs uppercase tracking-widest">{{ (editTarget && editTarget.type==='finance') ? 'Ajuste os dados da movimentação conforme necessário.' : 'Registre entradas ou saídas de caixa.' }}</p>
                </div>
                <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-indigo-100">
                    <i data-lucide="landmark" class="w-6 h-6"></i>
                </div>
            </header>
            <form @submit.prevent="saveTransaction" class="p-10 space-y-8">
                <div class="flex bg-slate-100 p-1.5 rounded-2xl">
                    <button type="button" @click="financeForm.type='income'" :class="financeForm.type==='income'?'bg-white shadow-sm text-emerald-600':'text-slate-500'" class="flex-1 py-3 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all">Receita</button>
                    <button type="button" @click="financeForm.type='expense'" :class="financeForm.type==='expense'?'bg-white shadow-sm text-rose-600':'text-slate-500'" class="flex-1 py-3 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all">Despesa</button>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="input-label">Descrição do Lançamento</label>
                        <div class="relative">
                            <i data-lucide="type" class="input-icon"></i>
                            <input v-model="financeForm.description" type="text" class="modern-input" placeholder="Ex: Venda de Site, Aluguel, Salário..." required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="input-label">Valor (R$)</label>
                            <div class="relative">
                                <div class="input-icon font-black text-[12px] text-slate-400">R$</div>
                                <input :value="displayFinanceAmount" @input="handleFinanceAmountInput" type="text" class="modern-input" placeholder="0,00" required>
                            </div>
                        </div>
                        <div>
                            <label class="input-label">Data / Vencimento</label>
                            <div class="relative">
                                <i data-lucide="calendar" class="input-icon"></i>
                                <input v-model="financeForm.date" type="date" class="modern-input !pl-14" required>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="input-label">Status do Lançamento</label>
                        <div class="flex bg-slate-100 p-1 rounded-xl">
                            <button type="button" @click="financeForm.status='pago'" :class="financeForm.status==='pago'?'bg-white shadow-sm text-slate-900 font-black':'text-slate-400'" class="flex-1 py-1.5 rounded-lg text-[9px] uppercase tracking-widest transition-all">Consolidado (Pago/Recebido)</button>
                            <button type="button" @click="financeForm.status='pendente'" :class="financeForm.status==='pendente'?'bg-white shadow-sm text-amber-600 font-black':'text-slate-400'" class="flex-1 py-1.5 rounded-lg text-[9px] uppercase tracking-widest transition-all">Pendente (Previsão)</button>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 bg-indigo-50/30 p-4 rounded-3xl border border-indigo-100/30 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-indigo-600 shadow-sm border border-indigo-50">
                            <i data-lucide="repeat" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[10px] font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                                Lançamento Recorrente
                                <span v-if="financeForm.recurring" class="px-2 py-0.5 bg-indigo-600 text-white text-[7px] rounded-full">ATIVO</span>
                            </p>
                            <p class="text-[9px] text-slate-500 font-medium">Cria automaticamente a fatura do mês seguinte.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="financeForm.recurring" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>  

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="input-label !mb-0">Categoria</label>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="managingCategories=!managingCategories; addingCategory=false" class="text-[10px] font-black uppercase tracking-widest flex items-center gap-1 transition-all" :class="managingCategories ? 'text-rose-500 hover:text-rose-700' : 'text-slate-400 hover:text-slate-600'">
                                    <i :data-lucide="managingCategories ? 'x' : 'settings-2'" class="w-3 h-3"></i> {{ managingCategories ? 'Fechar' : 'Gerir' }}
                                </button>
                                <button type="button" @click="addingCategory=!addingCategory; managingCategories=false" class="text-[10px] font-black text-indigo-500 hover:text-indigo-700 uppercase tracking-widest flex items-center gap-1 transition-all">
                                    <i data-lucide="plus" class="w-3 h-3"></i> Nova
                                </button>
                            </div>
                        </div>
                        <!-- Campo inline para nova categoria -->
                        <transition name="fade">
                            <div v-if="addingCategory" class="flex gap-3 mb-4 items-center bg-indigo-50 px-4 py-3 rounded-2xl border border-indigo-100">
                                <i data-lucide="tag" class="w-4 h-4 text-indigo-400 shrink-0"></i>
                                <input v-model="newCategoryInput" @keyup.enter="addFinanceCategory" type="text" class="flex-1 bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-800 placeholder-indigo-300" placeholder="Nome da categoria...">
                                <button type="button" @click="addFinanceCategory" :disabled="savingCategory" class="text-[10px] font-black text-white bg-indigo-600 px-3 py-1.5 rounded-xl hover:bg-indigo-700 transition-all uppercase tracking-wider shrink-0">
                                    <i v-if="savingCategory" data-lucide="loader-2" class="w-3 h-3 animate-spin"></i>
                                    <span v-else>OK</span>
                                </button>
                                <button type="button" @click="addingCategory=false; newCategoryInput=''" class="text-slate-400 hover:text-rose-500 transition-all">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </transition>

                        <!-- Modo gerenciamento: chips de categorias -->
                        <transition name="fade">
                            <div v-if="managingCategories" class="mb-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Todas as Categorias — Clique no 🗑 para excluir</p>
                                <div class="flex flex-wrap gap-2">
                                    <div v-for="cat in financeCategories" :key="cat" class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                                        {{ cat }}
                                        <!-- Confirmação inline -->
                                        <template v-if="categoryDeleteConfirm === cat">
                                            <button type="button" @click="deleteFinanceCategory(cat)" class="text-[9px] font-black bg-rose-500 text-white px-2 py-0.5 rounded-lg hover:bg-rose-600 transition-all uppercase ml-1">Sim</button>
                                            <button type="button" @click="categoryDeleteConfirm=null" class="text-[9px] font-black text-slate-400 hover:text-slate-600 uppercase">Não</button>
                                        </template>
                                        <button v-else type="button" @click="categoryDeleteConfirm=cat" class="ml-1 text-indigo-300 hover:text-rose-500 transition-colors" title="Excluir categoria">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4h6v2"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </transition>

                        <div class="relative">
                            <i data-lucide="tag" class="input-icon"></i>
                            <select v-model="financeForm.category" class="modern-input" required>
                                <option v-for="cat in financeCategories" :key="cat" :value="cat">{{ cat }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <button type="button" @click="showFinanceModal=false" class="flex-1 py-5 text-xs font-black text-slate-400 hover:text-indigo-600 transition-colors uppercase tracking-widest">Cancelar</button>
                    <button type="submit" class="flex-[2] h-16 bg-indigo-600 text-white text-sm font-black rounded-3xl shadow-2xl shadow-indigo-100 hover:bg-indigo-700 transition-all uppercase tracking-[0.1em] flex items-center justify-center gap-3" :disabled="savingFinance">
                        <i v-if="savingFinance" data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                        {{ savingFinance ? 'Salvando...' : 'Registrar' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
  </transition>
  
  <!-- MODAL DE CRIAÇÃO DE DOCUMENTO (ORÇAMENTO/CONTRATO) -->
  <transition name="fade">
    <div v-if="showDocModal" class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
        <div class="absolute inset-0 modal-overlay" @click="showDocModal=false"></div>
        <div class="relative bg-white w-full max-w-6xl h-[85vh] rounded-[40px] modal-card overflow-hidden flex flex-col shadow-2xl">
            <!-- Header Integrado -->
            <header class="px-10 py-8 border-b border-slate-100 flex items-center justify-between bg-white shrink-0">
                <div class="flex items-center gap-6">
                    <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-indigo-100">
                        <i :data-lucide="docForm.type==='orcamento'?'file-text':'pen-tool'" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <h3 class="text-3xl font-black text-slate-900 tracking-tighter">{{ docForm.type==='orcamento'?'Gerar Orçamento':'Redigir Contrato' }}</h3>
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-widest mt-1">Transforme negociações em documentos profissionais.</p>
                    </div>
                </div>
                <div class="flex bg-slate-100 p-1.5 rounded-2xl">
                    <button type="button" @click="docForm.type='orcamento'" :class="docForm.type==='orcamento'?'bg-white shadow-sm text-indigo-600 border-indigo-100':'text-slate-500 border-transparent'" class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border-2">Orçamento</button>
                    <button type="button" @click="docForm.type='contrato'" :class="docForm.type==='contrato'?'bg-white shadow-sm text-indigo-600 border-indigo-100':'text-slate-500 border-transparent'" class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border-2">Contrato</button>
                </div>
            </header>
            
            <div class="flex-1 flex overflow-hidden">
                <!-- Lado Esquerdo: Formulário Profundo -->
                <div class="flex-1 overflow-y-auto p-12 bg-slate-50/30 space-y-12 border-r border-slate-100">
                    
                    <!-- Bloco A: Cliente e Título -->
                    <section class="space-y-8">
                        <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2 mb-4"><span class="w-2 h-2 bg-indigo-500 rounded-full"></span> Cabeçalho do Documento</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="input-label ml-0">Título do Projeto</label>
                                <div class="relative">
                                    <i data-lucide="layout" class="input-icon"></i>
                                    <input v-model="docForm.title" type="text" class="modern-input" placeholder="Ex: E-commerce de Joias - Fase 1" required>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="input-label ml-0">Vínculo com Cliente</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <i data-lucide="user" class="input-icon"></i>
                                        <select v-if="!isNewDocClient" v-model="docForm.client_id" @change="onDocClientSelect" class="modern-input" required>
                                            <option value="">Selecione um cliente existente...</option>
                                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }} {{ c.phone ? '— ' + c.phone : '' }}</option>
                                        </select>
                                        <input v-else v-model="docForm.client_name" type="text" class="modern-input" placeholder="Digite o nome do novo cliente..." required>
                                    </div>
                                    <button type="button" @click="isNewDocClient = !isNewDocClient" :class="isNewDocClient ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600'" class="w-14 h-[56px] border-2 border-indigo-100 rounded-2xl flex items-center justify-center transition-all hover:bg-indigo-50" :title="isNewDocClient ? 'Selecionar da Lista' : 'Cadastrar Novo'">
                                        <i :data-lucide="isNewDocClient ? 'list' : 'user-plus'" class="w-5 h-5"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Novo Campo: Pessoa de Contato -->
                            <div class="space-y-2">
                                <label class="input-label ml-0">Aos cuidados de (Contato)</label>
                                <div class="relative">
                                    <i data-lucide="contact" class="input-icon"></i>
                                    <input v-model="docForm.contact_name" type="text" class="modern-input" placeholder="Ex: João da Silva (CEO)">
                                </div>
                            </div>
                            <!-- Novo Campo: Valor Total (Apenas para Contratos, já que não tem itens) -->
                            <div v-if="docForm.type === 'contrato'" class="space-y-2">
                                <label class="input-label ml-0">Valor Total do Contrato (R$)</label>
                                <div class="relative">
                                    <i data-lucide="banknote" class="input-icon"></i>
                                    <input :value="docForm.manual_total > 0 ? formatMoney(docForm.manual_total) : ''" @input="handleDocManualTotalInput" type="text" class="modern-input" placeholder="0,00">
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Bloco B: Dinâmica de Itens (Apenas Orçamento) -->
                    <section v-if="docForm.type === 'orcamento'" class="space-y-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2"><span class="w-2 h-2 bg-indigo-500 rounded-full"></span> Serviços e Valores</h4>
                            <button @click="docForm.items.push({description:'', price:0})" class="text-[10px] font-black text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-4 py-2 rounded-xl transition-all uppercase tracking-widest flex items-center gap-2">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Linha
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <transition-group name="list">
                                <div v-for="(item, idx) in docForm.items" :key="idx" class="flex gap-4 items-start bg-white p-5 rounded-3xl border border-slate-100 shadow-sm group hover:border-indigo-200 transition-all">
                                    <div class="flex-1">
                                        <textarea v-model="item.description" class="modern-input modern-textarea" placeholder="Descreva o serviço ou produto..."></textarea>
                                    </div>
                                    <div class="w-48 flex items-center gap-2 bg-slate-50 px-4 h-14 rounded-2xl border border-slate-100 group-hover:bg-indigo-50/50 group-hover:border-indigo-100 transition-all shrink-0">
                                        <span class="text-[10px] font-black text-slate-400">R$</span>
                                        <input :value="item.price > 0 ? formatMoney(item.price) : ''" @input="e => handleDocItemPriceInput(item, e)" type="text" class="w-full bg-transparent border-none focus:ring-0 font-black text-slate-900 text-sm text-right" placeholder="0,00">
                                    </div>
                                    <button type="button" @click="docForm.items.splice(idx, 1)" class="h-14 px-4 text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all shrink-0"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </div>
                            </transition-group>
                        </div>
                    </section>

                          <!-- Bloco C: Redação Completa -->
                    <section class="space-y-8">
                        <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2 mb-4"><span class="w-2 h-2 bg-indigo-500 rounded-full"></span> Redação do Documento</h4>
                            <div class="space-y-2">
                                <label class="input-label ml-0">Corpo do Trabalho / Detalhes do Contrato</label>
                                <p class="text-[10px] text-slate-400 font-medium mb-4">Descreva aqui o escopo completo, obrigações e detalhes técnicos que aparecerão no documento final.</p>
                                <div class="bg-white rounded-3xl border-2 border-slate-100 focus-within:border-indigo-400 transition-all shadow-sm overflow-hidden">
                                    <textarea id="editor-content"></textarea>
                                </div>
                                <textarea v-model="docForm.content" class="hidden"></textarea>
                            </div>
                    </section>

                    <!-- Bloco D: Notas e Cláusulas Curtas (Apenas Orçamento) -->
                    <section v-if="docForm.type === 'orcamento'" class="space-y-8">
                        <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2 mb-4"><span class="w-2 h-2 bg-indigo-500 rounded-full"></span> Notas de Rodapé</h4>
                        <div class="relative">
                            <i data-lucide="shield-check" class="input-icon !top-6"></i>
                            <textarea v-model="docForm.terms" class="modern-input min-h-[120px] py-6 pl-14" placeholder="Ex: Validade de 10 dias. Pagamento de 50% no início e 50% na entrega."></textarea>
                        </div>
                    </section>
                </div>

                <!-- Lado Direito: Widget de Resumo (Opcional) -->
                <aside v-if="docForm.type === 'orcamento'" class="w-96 bg-slate-50 p-10 flex flex-col justify-between">
                    <div class="space-y-8">
                        <div class="p-8 bg-white rounded-[32px] shadow-2xl shadow-indigo-100/50 border border-white relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-4 opacity-5">
                                <i data-lucide="badge-check" class="w-20 h-20 text-indigo-600"></i>
                            </div>
                            <span class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em]">Total Estimado</span>
                            <div class="mt-4 flex items-baseline gap-1">
                                <span class="text-xl font-black text-slate-400">R$</span>
                                <h4 class="text-5xl font-black text-slate-900 tracking-tighter">{{ formatMoney(docFormTotal) }}</h4>
                            </div>
                            <div class="mt-8 pt-8 border-t border-slate-50 space-y-4">
                                <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                                    <span class="text-slate-400">Items:</span>
                                    <span class="text-slate-900">{{ docForm.items.length }} Unid.</span>
                                </div>
                                <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                                    <span class="text-slate-400">Impostos:</span>
                                    <span class="text-slate-900">Inclusos</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 pt-10">
                        <button type="button" @click="saveDocument" class="w-full h-16 bg-slate-900 text-white text-sm font-black rounded-3xl shadow-2xl shadow-slate-200 hover:bg-slate-800 transition-all uppercase tracking-widest flex items-center justify-center gap-3" :disabled="savingDoc">
                            <i v-if="savingDoc" data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                            {{ savingDoc ? 'Sincronizando...' : 'Finalizar' }}
                        </button>
                        <button type="button" @click="closeDocModal" class="w-full py-5 text-xs font-black text-slate-400 hover:text-indigo-600 transition-colors uppercase tracking-widest">Descartar</button>
                    </div>
                </aside>
            </div>
            
            <!-- Footer Linear para Contratos (Agora fixo na base da flex, sem sobrepor) -->
            <div v-if="docForm.type !== 'orcamento'" class="p-10 bg-white border-t border-slate-100 flex items-center justify-end gap-6">
                <button type="button" @click="closeDocModal" class="text-xs font-black text-slate-400 hover:text-indigo-600 transition-colors uppercase tracking-widest">Descartar rascunho</button>
                <button type="button" @click="saveDocument" class="px-12 h-16 bg-slate-900 text-white text-sm font-black rounded-3xl shadow-2xl shadow-slate-200 hover:bg-slate-800 transition-all uppercase tracking-widest flex items-center justify-center gap-3" :disabled="savingDoc">
                    <i v-if="savingDoc" data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                    {{ savingDoc ? 'Salvando...' : 'Finalizar e Gerar Contrato' }}
                </button>
            </div>
        </div>
    </div>
  </transition>

  <!-- MODAL DE CRIAÇÃO DE TAREFA -->
  <transition name="fade">
    <div v-if="showTaskModal" class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
        <div class="absolute inset-0 modal-overlay" @click="showTaskModal=false"></div>
        <div class="relative bg-white w-full max-w-lg rounded-[40px] modal-card overflow-hidden shadow-2xl">
            <header class="px-10 pt-10 pb-6 border-b border-slate-100 flex items-center justify-between uppercase">
                <div>
                    <h3 class="text-3xl font-black text-slate-900 tracking-tighter">{{ editTarget && editTarget.type === 'task' ? 'Editar Atividade' : 'Nova Atividade' }}</h3>
                    <p class="text-slate-400 font-bold mt-1 text-xs uppercase tracking-widest">Mantenha o foco na entrega técnica.</p>
                </div>
                <div :class="editTarget && editTarget.type === 'task' ? 'bg-amber-500' : 'bg-indigo-600'" class="w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-indigo-100/30 transition-colors">
                    <i :data-lucide="editTarget && editTarget.type === 'task' ? 'edit-3' : 'clipboard-list'" class="w-6 h-6"></i>
                </div>
            </header>
            <form @submit.prevent="saveTask" class="p-10 space-y-8">
                <div>
                    <label class="input-label">Título da Tarefa</label>
                    <div class="relative">
                        <i data-lucide="type" class="input-icon"></i>
                        <input v-model="taskForm.title" type="text" class="modern-input" placeholder="Ex: Ajustar logo, Subir landing page..." required>
                    </div>
                </div>


                <div>
                    <label class="input-label">Descrição / Detalhes</label>
                    <div class="relative">
                        <i data-lucide="align-left" class="input-icon !top-6"></i>
                        <textarea v-model="taskForm.description" class="modern-input modern-textarea" placeholder="Descreva os detalhes técnico desta tarefa..."></textarea>
                    </div>
                </div>

                <!-- SEÇÃO ÚNICA DE CLIENTE (SMART) -->
                <div>
                    <label class="input-label">Identificação do Cliente</label>
                    
                    <!-- Estado A: Já vinculado oficialmente -->
                    <div v-if="taskForm.client_id" class="flex items-center gap-4 p-5 bg-indigo-50 border-2 border-indigo-100 rounded-3xl shadow-lg shadow-indigo-100/20">
                        <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shrink-0">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Vínculo Oficial</p>
                            <p class="text-sm font-black text-indigo-900">{{ clients.find(c => c.id == taskForm.client_id)?.name || 'Cliente Vinculado' }}</p>
                        </div>
                        <button type="button" @click="taskForm.client_id=''" class="p-2 text-indigo-300 hover:text-rose-500 transition-all" title="Remover Vínculo">
                            <i data-lucide="link-2-off" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Estado B: Nome Manual / Lead -->
                    <div v-else class="space-y-4">
                        <div class="relative">
                            <i data-lucide="user" class="input-icon"></i>
                            <input v-model="taskForm.client_name" type="text" class="modern-input" placeholder="Digite o nome do lead ou identificação manual...">
                        </div>
                        
                        <div class="flex items-center gap-2">
                           <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">Ou conecte:</span>
                           <select v-model="taskForm.client_id" class="flex-1 bg-transparent border-none text-[10px] font-black text-indigo-600 uppercase tracking-widest cursor-pointer focus:ring-0">
                               <option value="">Vincular a Cadastro Oficial...</option>
                               <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                           </select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-50">
                    <div>
                        <label class="input-label">Prioridade da Entrega</label>
                        <div class="relative">
                            <i data-lucide="zap" class="input-icon"></i>
                            <select v-model="taskForm.priority" class="modern-input">
                                <option value="low">Baixa</option>
                                <option value="medium">Média</option>
                                <option value="high">Alta / Urgente</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <button type="button" @click="showTaskModal=false" class="flex-1 py-5 text-xs font-black text-slate-400 hover:text-indigo-600 transition-colors uppercase tracking-widest">Descartar</button>
                    <button type="submit" class="flex-[2] h-16 bg-indigo-600 text-white text-sm font-black rounded-3xl shadow-2xl shadow-indigo-100 hover:bg-indigo-700 transition-all uppercase tracking-[0.1em] flex items-center justify-center gap-3" :disabled="savingTask">
                        <i v-if="savingTask" data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                        {{ savingTask ? 'Salvando...' : (editTarget && editTarget.type === 'task' ? 'Salvar Alterações' : 'Adicionar no Kanban') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
  </transition>

  <!-- NOTIFICAÇÕES TOAST -->
  <div class="fixed bottom-8 right-8 z-[2000] flex flex-col gap-3 pointer-events-none">
    <transition-group name="list">
        <div v-for="toast in toasts" :key="toast.id" :class="toast.type==='success'?'bg-slate-900 border-indigo-500':'bg-rose-600 border-rose-400'" class="glass px-8 py-5 rounded-3xl shadow-2xl border-l-4 text-white flex items-center gap-4 min-w-[320px] pointer-events-auto">
            <i v-if="toast.type==='success'" data-lucide="check-circle-2" class="w-5 h-5 text-indigo-400"></i>
            <i v-else data-lucide="alert-circle" class="w-5 h-5 text-rose-100"></i>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest opacity-60">{{ toast.title || 'Sistema' }}</p>
                <p class="text-sm font-bold tracking-tight">{{ toast.message }}</p>
            </div>
        </div>
    </transition-group>
  </div>

</div>

<script>
(function() {
  const { createApp, ref, computed, onMounted, nextTick, watch } = Vue;

  createApp({
    setup() {
      const view = ref('dashboard');
      let quill = null;
      const clients = ref([]);
      const loading = ref(true);
      const search = ref('');
      const filterStatus = ref('todos');
      const showModal = ref(false);
      const showInvoicesModal = ref(false);
      const invoices = ref([]);
      const loadingInvoices = ref(false);
      const asaasBalance = ref(0);
      const loadingBalance = ref(false);
      const importing = ref(false);
      const massBilling = ref(false);
      const syncingStatus = ref(false);
      const saving = ref(false);
      const logs = ref([]);
      const loadingLogs = ref(false);
      const metrics = ref({ labels: [], data: [], projected_mrr: 0, churn_risk: 0 });
      const commercialStats = ref({ total: 0, accepted: 0, pending: 0, rejected: 0, revenue: 0, opportunity: 0, avg_ticket: 0, conversion_rate: 0 });
      const loadingReports = ref(false);
      const dreData = ref({ history: [], metrics: { churn_rate: 0, ltv: 0, avg_mrr: 0, active: 0, lost: 0, sales_cycle: 0, recovery_rate: 0 } });
      const showFinanceModal = ref(false);
      const showAllLogs = ref(false);
      const loadingFinance = ref(false);
      const savingFinance = ref(false);
      const transactions = ref([]);
      const financeCategories = ref(['Vendas','Infraestrutura','Marketing','Pessoal','Retirada','Variáveis']);
      const newCategoryInput = ref('');
      const addingCategory = ref(false);
      const managingCategories = ref(false);
      const categoryDeleteConfirm = ref(null);
      const savingCategory = ref(false);
      const financeForm = ref({ type: 'income', description: '', amount: 0, date: new Date().toISOString().split('T')[0], category: 'Vendas', status: 'pago', recurring: false });
      const financeHistoryTab = ref('all'); // all, income, expense
      const financeStatusFilter = ref('all'); // all, pago, pendente

      // Filtro de Data (Início e Fim do Mês Atual)
      const now = new Date();
      const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
      const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
      const financeDateStart = ref(firstDay);
      const financeDateEnd = ref(lastDay);
      const financePeriod = ref('month');

      const setFinancePeriod = (period) => {
          const today = new Date();
          const y = today.getFullYear();
          const m = today.getMonth();
          const d = today.getDate();

          if (period === 'today') {
              financeDateStart.value = today.toISOString().split('T')[0];
              financeDateEnd.value = today.toISOString().split('T')[0];
          } else if (period === 'week') {
              const first = today.getDate() - today.getDay(); 
              financeDateStart.value = new Date(today.setDate(first)).toISOString().split('T')[0];
              financeDateEnd.value = new Date(today.setDate(first + 6)).toISOString().split('T')[0];
          } else if (period === 'month') {
              financeDateStart.value = new Date(y, m, 1).toISOString().split('T')[0];
              financeDateEnd.value = new Date(y, m + 1, 0).toISOString().split('T')[0];
          }
          financePeriod.value = period;
      };

      const filteredTransactions = computed(() => {
          let list = transactions.value;
          
          if (financeHistoryTab.value !== 'all') {
              list = list.filter(t => t.type === financeHistoryTab.value);
          }
          
          if (financeStatusFilter.value !== 'all') {
              list = list.filter(t => t.status === financeStatusFilter.value);
          }

          if (financeDateStart.value) {
              list = list.filter(t => t.date >= financeDateStart.value);
          }
          if (financeDateEnd.value) {
              list = list.filter(t => t.date <= financeDateEnd.value);
          }

          return list;
      });

      const showDocModal = ref(false);
      const loadingDocs = ref(false);
      const savingDoc = ref(false);
      const documents = ref([]);
      const docForm = ref({ type: 'orcamento', title: '', client_id: '', client_name: '', client_email: '', contact_name: '', items: [{description: '', price: 0}], terms: '', content: '', manual_total: 0 });
      const isNewDocClient = ref(false);


      const onDocClientSelect = () => {
          const client = clients.value.find(c => c.id == docForm.value.client_id);
          if (client) {
              docForm.value.client_name = client.name;
              docForm.value.client_email = client.email || '';
          } else {
              docForm.value.client_name = '';
              docForm.value.client_email = '';
          }
      };

      const handleDocItemPriceInput = (item, e) => {
          let val = e.target.value.replace(/\D/g, '');
          if (val === '') {
              item.price = 0;
              return;
          }
          item.price = parseFloat(val) / 100;
      };

      const handleDocManualTotalInput = (e) => {
          let val = e.target.value.replace(/\D/g, '');
          if (val === '') {
              docForm.value.manual_total = 0;
              return;
          }
          docForm.value.manual_total = parseFloat(val) / 100;
      };

      const showTaskModal = ref(false);
      const loadingTasks = ref(false);
      const savingTask = ref(false);
      const tasks = ref([]);
      const taskForm = ref({ title: '', status: 'todo', priority: 'medium', client_id: '', description: '' });

      const toasts = ref([]);
      const formatMoney = v => parseFloat(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2});

      const showToast = (message, type = 'success', title = '') => {
          const id = Date.now();
          toasts.value.push({ id, message, type, title });
          nextTick(() => { if (window.lucide) lucide.createIcons(); });
          setTimeout(() => {
              toasts.value = toasts.value.filter(t => t.id !== id);
          }, 4000);
      };

      let chartInstance = null;
      let financeChartInstance = null;
      const deleteTarget = ref(null);
      const editTarget = ref(null);
      const asaasOk = ref(<?php echo $asaas_ok?'true':'false'; ?>);
      const gatewayLabel = ref("<?php echo esc_js($gateway_label); ?>");
      const waOk = ref(<?php echo $wa_ok?'true':'false'; ?>);
      const settingsUrl = '<?php echo esc_url($settings_url); ?>';
      
      const currentPage = ref(1);
      const itemsPerPage = 10;

      const form = ref({ name: '', phone: '', mrr: null, site_url: '', product: '', notes: '' });
      const productOptions = ref([]);

      // ── MASCARA MONETÁRIA ──
      const displayMRR = computed(() => {
        if(form.value.mrr === null || form.value.mrr === undefined || form.value.mrr === '') return '';
        return parseFloat(form.value.mrr).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      });
      const handleMRRInput = (e) => {
        let value = e.target.value.replace(/\D/g, "");
        if (value === "") {
            form.value.mrr = null;
            return;
        }
        value = (value / 100).toFixed(2);
        form.value.mrr = parseFloat(value);
      };

      // ── MASCARA WHATSAPP ──
      const displayPhone = computed(() => {
        let v = (form.value.phone || "").replace(/\D/g, "");
        if (v.length > 11) v = v.substring(0, 11);
        if (v.length === 0) return "";
        if (v.length <= 2) return "(" + v;
        if (v.length <= 6) return "(" + v.substring(0, 2) + ") " + v.substring(2);
        if (v.length <= 10) return "(" + v.substring(0, 2) + ") " + v.substring(2, 6) + "-" + v.substring(6);
        return "(" + v.substring(0, 2) + ") " + v.substring(2, 7) + "-" + v.substring(7);
      });
      const handlePhoneInput = (e) => {
        form.value.phone = e.target.value.replace(/\D/g, "");
      };

      const siteLogo = ref("<?php echo esc_url( get_site_icon_url(128) ?: '' ); ?>");

      const printFinanceReport = () => {
          const list = filteredTransactions.value;
          const rangeStr = `${new Date(financeDateStart.value).toLocaleDateString()} até ${new Date(financeDateEnd.value).toLocaleDateString()}`;
          const typeStr = financeHistoryTab.value === 'all' ? 'Completo' : (financeHistoryTab.value === 'income' ? 'de Receitas' : 'de Despesas');

          // Criar iframe oculto para impressão
          const iframe = document.createElement('iframe');
          iframe.style.position = 'fixed';
          iframe.style.right = '0';
          iframe.style.bottom = '0';
          iframe.style.width = '0';
          iframe.style.height = '0';
          iframe.style.border = '0';
          document.body.appendChild(iframe);

          const doc = iframe.contentWindow.document;
          doc.write(`
              <html>
              <head>
                  <title>Relatório Financeiro</title>
                  <style>
                      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
                      body { font-family: 'Inter', sans-serif; color: #1e293b; padding: 20px; margin: 0; }
                      .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 30px; }
                      .logo { height: 40px; }
                      .title { text-align: right; }
                      .title h1 { margin: 0; font-size: 18px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
                      .title p { margin: 5px 0 0; font-size: 9px; color: #64748b; font-weight: 700; text-transform: uppercase; }
                      table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                      th { background: #f8fafc; text-align: left; padding: 10px 12px; font-size: 9px; text-transform: uppercase; font-weight: 900; color: #64748b; border-bottom: 1px solid #e2e8f0; }
                      td { padding: 10px 12px; font-size: 10px; border-bottom: 1px solid #f1f5f9; font-weight: 500; }
                      .amount { text-align: right; font-weight: 900; }
                      .income { color: #059669; }
                      .expense { color: #dc2626; }
                      .pending { color: #d97706; font-size: 8px; font-weight: 900; }
                      .totals { background: #f8fafc; padding: 15px; border-radius: 8px; display: flex; justify-content: flex-end; gap: 30px; border: 1px solid #f1f5f9; page-break-inside: avoid; }
                      .total-item { text-align: right; }
                      .total-item .label { font-size: 8px; font-weight: 900; color: #64748b; text-transform: uppercase; }
                      .total-item .val { font-size: 14px; font-weight: 900; margin-top: 2px; }
                      @media print {
                          body { padding: 0; }
                          .totals { border: 1px solid #e2e8f0; }
                      }
                  </style>
              </head>
              <body>
                  <div class="header">
                      <img src="${siteLogo.value}" class="logo" onerror="this.style.display='none'">
                      <div class="title">
                          <h1>Fluxo de Caixa</h1>
                          <p>Período: ${rangeStr}</p>
                          <p>Tipo: ${typeStr}</p>
                      </div>
                  </div>

                  <table>
                      <thead>
                          <tr>
                              <th>Data</th>
                              <th>Descrição</th>
                              <th>Categoria</th>
                              <th>Status</th>
                              <th class="amount">Valor</th>
                          </tr>
                      </thead>
                      <tbody>
                          ${list.map(t => `
                              <tr>
                                  <td>${t.human_date}</td>
                                  <td>${t.description} ${t.recurring ? '<small style="color:#6366f1">[Recorrente]</small>' : ''}</td>
                                  <td>${t.category}</td>
                                  <td>${t.status === 'pago' ? 'PAGO' : '<span class="pending">PENDENTE</span>'}</td>
                                  <td class="amount ${t.type === 'income' ? 'income' : 'expense'}">
                                      ${t.type === 'income' ? '+' : '-'} R$ ${parseFloat(t.amount).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                                  </td>
                              </tr>
                          `).join('')}
                      </tbody>
                  </table>

                  <div class="totals">
                      <div class="total-item">
                          <div class="label">Total Geral do Período</div>
                          <div class="val" style="color:#1e293b">R$ ${list.reduce((acc, t) => acc + parseFloat(t.amount || 0), 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                      </div>
                  </div>
              </body>
              </html>
          `);
          doc.close();

          // Esperar carregar e imprimir
          iframe.contentWindow.focus();
          setTimeout(() => {
              iframe.contentWindow.print();
              setTimeout(() => {
                  document.body.removeChild(iframe);
              }, 1000);
          }, 500);
      };

      // ── FORMATADORES DE UI ──
      const formatPhone = (v) => {
        if(!v) return "";
        let n = v.replace(/\D/g, "");
        if(n.length === 11) return "(" + n.substring(0,2) + ") " + n.substring(2,7) + "-" + n.substring(7);
        if(n.length === 10) return "(" + n.substring(0,2) + ") " + n.substring(2,6) + "-" + n.substring(6);
        return v;
      };

      watch(view, () => {
        search.value = '';
      });

      const filteredExpanded = computed(() => {
        let list = clients.value;
        if (filterStatus.value !== 'todos') {
            list = list.filter(c => c.status === filterStatus.value);
        }
        const q = (search.value || '').toLowerCase();
        return list.filter(c => {
            const name = (c.name || '').toLowerCase();
            const phone = (c.phone || '');
            const product = (c.product || '').toLowerCase();
            return name.includes(q) || phone.includes(q) || product.includes(q);
        });
      });

      const totalFiltered = computed(() => filteredExpanded.value.length);
      const totalPages = computed(() => Math.ceil(totalFiltered.value / itemsPerPage) || 1);

      const filteredClients = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage;
        return filteredExpanded.value.slice(start, start + itemsPerPage);
      });

      watch([search, filterStatus], () => {
        currentPage.value = 1;
      });

      watch([filteredClients, view], () => {
        nextTick(lucide.createIcons);
      });

      const totalMRR = computed(() => {
          const raw = (clients.value || []).reduce((a,c) => a + parseFloat(c.mrr||0), 0);
          return raw.toLocaleString('pt-BR',{minimumFractionDigits:2});
      });
      const activeCount = computed(() => clients.value.filter(c => c.status==='ativo').length);
      const overdueCount = computed(() => clients.value.filter(c => c.status==='inadimplente').length);
      const totalIncomes = computed(() => filteredTransactions.value.filter(t => (t.type === 'income' || t.type === 'Entrada') && t.status === 'pago').reduce((acc, t) => acc + parseFloat(t.amount || 0), 0));
      const totalExpenses = computed(() => filteredTransactions.value.filter(t => (t.type === 'expense' || t.type === 'Saída') && t.status === 'pago').reduce((acc, t) => acc + parseFloat(t.amount || 0), 0));
      const totalPendingExpenses = computed(() => filteredTransactions.value.filter(t => (t.type === 'expense' || t.type === 'Saída') && t.status === 'pendente').reduce((acc, t) => acc + parseFloat(t.amount || 0), 0));
      const totalPendingIncomes = computed(() => filteredTransactions.value.filter(t => (t.type === 'income' || t.type === 'Entrada') && t.status === 'pendente').reduce((acc, t) => acc + parseFloat(t.amount || 0), 0));

      // MÉTRICAS DE INTELIGÊNCIA ERP
      const profitMargin = computed(() => totalIncomes.value > 0 ? ((totalIncomes.value - totalExpenses.value) / totalIncomes.value) * 100 : 0);
      const projectedEndBalance = computed(() => asaasBalance.value + (totalIncomes.value - totalExpenses.value) + (totalPendingIncomes.value - totalPendingExpenses.value));
      const topExpenseCategory = computed(() => {
          const tally = {};
          filteredTransactions.value.filter(t => (t.type === 'expense' || t.type === 'Saída')).forEach(t => {
              tally[t.category] = (tally[t.category] || 0) + parseFloat(t.amount || 0);
          });
          const sorted = Object.keys(tally).sort((a,b) => tally[b] - tally[a]);
          return sorted.length > 0 ? sorted[0] : 'Nenhum Gasto';
      });
      const arpu = computed(() => activeCount.value > 0 ? totalMRR.value / activeCount.value : 0);

      const docFormTotal = computed(() => {
          if (docForm.value.type === 'contrato') {
              return docForm.value.manual_total || 0;
          }
          return docForm.value.items.reduce((acc, item) => acc + parseFloat(item.price || 0), 0);
      });

      const fetchProducts = async () => {
        const r = await fetch('<?php echo $rest_url; ?>/products', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
        productOptions.value = await r.json();
      };

      const fetchDRE = async () => {
        loadingReports.value = true;
        try {
            const r = await fetch('<?php echo $rest_url; ?>/reports/dre', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
            dreData.value = await r.json();
            nextTick(renderChart);
        } finally {
            loadingReports.value = false;
        }
      };

      const fetchClients = async () => {
        loading.value = true;
        try {
          const r = await fetch('<?php echo $rest_url; ?>/clients', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
          clients.value = await r.json();
        } finally {
          loading.value = false;
          nextTick(lucide.createIcons);
        }
      };

      const fetchDashboardStats = async () => {
        if(!asaasOk.value) return;
        loadingBalance.value = true;
        try {
          const r = await fetch('<?php echo $rest_url; ?>/asaas/balance', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
          const d = await r.json();
          asaasBalance.value = d.balance || 0;
        } finally {
          loadingBalance.value = false;
        }
      };

      const importGateway = async () => {
        importing.value = true;
        try {
          const r = await fetch('<?php echo $rest_url; ?>/asaas/import', { method: 'POST', headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
          const d = await r.json();
          if(d.success) {
            let msg = `Sincronização concluída: ${d.imported} novos vínculos estabelecidos.`;
            if(d.finance_synced > 0) msg += ` ${d.finance_synced} receitas adicionadas ao financeiro.`;
            showToast(msg, 'success', gatewayLabel.value.toUpperCase());
            await fetchClients();
            await fetchTransactions();
          } else {
            showToast('Falha na importação: ' + (d.error||'Desconhecida'), 'error', gatewayLabel.value.toUpperCase());
          }
        } finally {
          importing.value = false;
        }
      };

      const massBillOverdue = async () => {
        if(!confirm(`Tem certeza que deseja disparar mensagens de cobrança no WhatsApp para todos os ${overdueCount.value} clientes inadimplentes?\n\nIsso pode demorar alguns segundos.`)) return;
        
        massBilling.value = true;
        const overdues = clients.value.filter(c => c.status === 'inadimplente');
        let successCount = 0;
        
        for (const c of overdues) {
            try {
                const response = await fetch(`<?php echo $rest_url; ?>/clients/${c.id}/whatsapp`, { 
                    method: 'POST', 
                    headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} 
                });
                const d = await response.json();
                if(d.success) successCount++;
                // Pausa rápida entre mensagens para evitar spam/rate limit
                await new Promise(res => setTimeout(res, 800));
            } catch (err) {
                console.error('Erro ao cobrar '+c.name, err);
            }
        }
        
        massBilling.value = false;
        showToast(`Cobrança em massa finalizada: ${successCount} sucessos.`, 'success', 'WHATSAPP');
      };

      const syncOverduesSilently = async () => {
        if(!asaasOk.value) return;
        syncingStatus.value = true;
        try {
            const r = await fetch(`<?php echo $rest_url; ?>/asaas/sync-overdue`, { method: 'POST', headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
            const res = await r.json();
            if(res.success) {
               if(res.updated > 0) await fetchClients();
               if(res.finance_synced > 0) {
                   await fetchTransactions();
                   showToast(`${res.finance_synced} novas receitas sincronizadas do Asaas.`, 'success', 'FINANCEIRO');
               }
            }
        } catch(e) {
            console.error('Erro no Auto-Sync de Inadimplência', e);
        } finally {
            syncingStatus.value = false;
            nextTick(lucide.createIcons);
        }
      };

      const isOverdue = (dateStr) => {
          const today = new Date().toISOString().split('T')[0];
          return dateStr < today;
      };

      const handleRecurrence = async (t) => {
          if(!t.recurring) return;
          
          // Verifica se já existe um lançamento futuro para evitar duplicidade
          // (Simples verificação por descrição e mês seguinte)
          const nextDate = new Date(t.date);
          nextDate.setMonth(nextDate.getMonth() + 1);
          const nextMonthStr = nextDate.toISOString().split('-').slice(0,2).join('-');
          
          const alreadyExists = transactions.value.find(existing => 
              existing.description === t.description && 
              existing.date.startsWith(nextMonthStr)
          );
          
          if(alreadyExists) return;

          try {
              await fetch('<?php echo $rest_url; ?>/finance/transactions', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' },
                  body: JSON.stringify({
                      ...t,
                      date: nextDate.toISOString().split('T')[0],
                      status: 'pendente'
                  })
              });
              showToast('Nova fatura recorrente gerada para o próximo ciclo.', 'success', 'AUTOMAÇÃO');
          } catch(e) { console.error('Erro ao gerar recorrência', e); }
      };

      const markAsPaid = async (t) => {
          let amount = t.amount;
          const newAmount = prompt(`Confirmar pagamento de "${t.description}"?\n\nValor atual: R$ ${formatMoney(t.amount)}\nCaso o valor pago seja diferente, digite abaixo (apenas números):`, t.amount.toString());
          
          if (newAmount === null) return; // Cancelou
          if (newAmount.trim() !== "") {
              const parsed = parseFloat(newAmount.replace(',', '.'));
              if (!isNaN(parsed)) amount = parsed;
          }

          try {
              const res = await fetch(`<?php echo $rest_url; ?>/finance/transactions/${t.id}`, {
                  method: 'PUT',
                  headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' },
                  body: JSON.stringify({ status: 'pago', amount: amount })
              });
              if(res.ok) {
                  showToast('Pagamento confirmado!', 'success', 'FINANCEIRO');
                  
                  // Se for recorrente, cria o próximo
                  if(t.recurring) {
                      await handleRecurrence({ ...t, amount: amount });
                  }
                  
                  fetchTransactions();
              }
          } catch(e) { console.error(e); }
      };

      const fetchTransactions = async () => {
          loadingFinance.value = true;
          try {
              const r = await fetch('<?php echo $rest_url; ?>/finance/transactions', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
              transactions.value = await r.json();
          } finally {
              loadingFinance.value = false;
              nextTick(lucide.createIcons);
          }
      };

      const saveTransaction = async () => {
          savingFinance.value = true;
          const isEdit = editTarget.value && editTarget.value.type === 'finance';
          const url = isEdit ? `<?php echo $rest_url; ?>/finance/transactions/${editTarget.value.id}` : '<?php echo $rest_url; ?>/finance/transactions';
          const method = isEdit ? 'PUT' : 'POST';

          try {
              const r = await fetch(url, {
                  method: method,
                  headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' },
                  body: JSON.stringify(financeForm.value)
              });
              if (r.ok) {
                  const savedData = await r.json();
                  showFinanceModal.value = false;
                  
                  // Se mudou para PAGO agora e é recorrente, gera o próximo
                  if (financeForm.value.status === 'pago' && financeForm.value.recurring && (!editTarget.value || financeForm.value.oldStatus !== 'pago')) {
                      await handleRecurrence({ ...financeForm.value, id: savedData.id });
                  }
                  
                  editTarget.value = null;
                  financeForm.value = { type: 'income', description: '', amount: 0, date: new Date().toISOString().split('T')[0], category: 'Vendas', status: 'pago' };
                  showToast(isEdit ? 'Lançamento atualizado!' : 'Lançamento registrado!', 'success', 'FINANCEIRO');
                  fetchTransactions();
              }
          } finally {
              savingFinance.value = false;
          }
      };

      const openFinanceEditModal = (t) => {
          editTarget.value = { id: t.id, type: 'finance' };
          financeForm.value = { 
              type: t.type, 
              description: t.description, 
              amount: t.amount, 
              date: t.date, 
              category: t.category,
              status: t.status || 'pago',
              oldStatus: t.status || 'pago',
              recurring: t.recurring || false
          };
          showFinanceModal.value = true;
      };

      const handleDeleteTransaction = async (id) => {
          if(!confirm('Deseja realmente excluir este lançamento?')) return;
          await fetch(`<?php echo $rest_url; ?>/finance/transactions/${id}`, { method: 'DELETE', headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
          showToast('Lançamento removido.');
          fetchTransactions();
      };

      const fetchFinanceCategories = async () => {
          try {
              const r = await fetch('<?php echo $rest_url; ?>/finance/categories', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
              const d = await r.json();
              if (d.categories) financeCategories.value = d.categories;
          } catch(e) { /* mantém as padrão */ }
      };

      const addFinanceCategory = async () => {
          const name = (newCategoryInput.value || '').trim();
          if (!name || financeCategories.value.includes(name)) {
              newCategoryInput.value = '';
              addingCategory.value = false;
              return;
          }
          savingCategory.value = true;
          const updated = [...financeCategories.value, name];
          try {
              const r = await fetch('<?php echo $rest_url; ?>/finance/categories', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' },
                  body: JSON.stringify({ categories: updated })
              });
              const d = await r.json();
              if (d.categories) {
                  financeCategories.value = d.categories;
                  financeForm.value.category = name;
                  showToast(`Categoria "${name}" criada!`, 'success', 'FINANCEIRO');
              }
          } finally {
              savingCategory.value = false;
              newCategoryInput.value = '';
              addingCategory.value = false;
          }
      };

      const deleteFinanceCategory = async (name) => {
          categoryDeleteConfirm.value = null;
          const updated = financeCategories.value.filter(c => c !== name);
          try {
              const r = await fetch('<?php echo $rest_url; ?>/finance/categories', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' },
                  body: JSON.stringify({ categories: updated })
              });
              const d = await r.json();
              if (d.categories) {
                  financeCategories.value = d.categories;
                  // Se era a categoria selecionada, reseta para a primeira disponível ou vazio
                  if (financeForm.value.category === name) {
                      financeForm.value.category = d.categories.length > 0 ? d.categories[0] : '';
                  }
                  showToast(`Categoria "${name}" excluída!`, 'success', 'FINANCEIRO');
              }
          } catch(e) {
              showToast('Erro ao excluir categoria.', 'error', 'FINANCEIRO');
          }
      };

      const fetchDocuments = async () => {
          loadingDocs.value = true;
          try {
              const r = await fetch('<?php echo $rest_url; ?>/documents', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
              documents.value = await r.json();
          } finally {
              loadingDocs.value = false;
              nextTick(lucide.createIcons);
          }
      };

      const openDocModal = () => {
          editTarget.value = null;
          docForm.value = { type: 'orcamento', title: '', client_id: '', client_name: '', client_email: '', contact_name: '', items: [{description: '', price: 0}], terms: '', content: '', manual_total: 0 };
          showDocModal.value = true;
          initEditor();
          nextTick(lucide.createIcons);
      };

      const openDocEditModal = (doc) => {
          editTarget.value = { id: doc.id, type: 'document' };
          docForm.value = { 
              type: doc.type, 
              title: doc.title, 
              client_id: doc.client_id || '',
              client_name: doc.client_name, 
              client_email: doc.client_email, 
              items: JSON.parse(JSON.stringify(doc.items)), 
              terms: doc.terms,
              content: doc.content || '',
              contact_name: doc.contact_name || ''
          };
          isNewDocClient.value = false;
          showDocModal.value = true;
          initEditor();
          nextTick(lucide.createIcons);
      };

      const initEditor = () => {
          nextTick(() => {
              if (window.tinymce && tinymce.get('editor-content')) {
                  tinymce.remove('#editor-content');
              }
              if (window.tinymce) {
                  tinymce.init({
                      selector: '#editor-content',
                      menubar: false,
                      plugins: 'table lists link code advlist autolink autoresize charmap emoticons wordcount',
                      toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | table bullist numlist | link charmap emoticons | code clean',
                      branding: false,
                      promotion: false,
                      content_style: 'body { font-family: Inter, sans-serif; font-size: 15px; color: #1e293b; line-height: 1.8; padding: 20px; } p { margin-bottom: 1.5rem; }',
                      skin: 'oxide',
                      min_height: 500,
                      autoresize_bottom_margin: 50,
                      setup: (editor) => {
                          editor.on('init', () => {
                              editor.setContent(docForm.value.content || '');
                          });
                      }
                  });
              }
          });
      };

      const closeDocModal = () => {
          if (window.tinymce && tinymce.get('editor-content')) {
              tinymce.remove('#editor-content');
          }
          showDocModal.value = false;
          editTarget.value = null;
      };

      const saveDocument = async () => {
          if(!docForm.value.title || !docForm.value.client_name) return showToast('Preencha título e nome do cliente.', 'error');
          
          // Sincroniza conteúdo do TinyMCE
          if (window.tinymce && tinymce.get('editor-content')) {
              docForm.value.content = tinymce.get('editor-content').getContent();
          }
          
          savingDoc.value = true;
          
          const isEdit = editTarget.value && editTarget.value.type === 'document';
          const url = isEdit ? `<?php echo $rest_url; ?>/documents/${editTarget.value.id}` : '<?php echo $rest_url; ?>/documents';
          const method = isEdit ? 'PUT' : 'POST';

          try {
              const r = await fetch(url, {
                  method: method,
                  headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' },
                  body: JSON.stringify({ ...docForm.value, total: docFormTotal.value })
              });
              if (r.ok) {
                  const docRes = await r.json();
                   closeDocModal();
                  showToast(isEdit ? 'Documento atualizado!' : 'Documento gerado com sucesso!', 'success', 'VENDAS');
                  
                  // AUTOMAÇÃO: Cria tarefa no Kanban para qualquer novo documento
                  if (!isEdit) {
                      const taskPrefix = docForm.value.type === 'contrato' ? 'Contrato' : 'Orçamento';
                      try {
                          await fetch('<?php echo $rest_url; ?>/tasks', {
                              method: 'POST',
                              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' },
                              body: JSON.stringify({ 
                                title: `${taskPrefix}: ${docForm.value.title}`, 
                                priority: 'medium', 
                                status: 'todo',
                                client_id: docForm.value.client_id || '',
                                client_name: docForm.value.client_name || '',
                                document_id: docRes.id
                              })
                          });
                          showToast('Tarefa criada no Kanban.', 'success', 'AUTOMAÇÃO');
                          fetchTasks();
                      } catch(e) {
                          console.error('Falha na automação:', e);
                      }
                  }

                  fetchDocuments();
                  editTarget.value = null;
              }
          } catch(e) {
              console.error(e);
              showToast('Erro ao salvar documento.', 'error', 'SISTEMA');
          } finally {
              savingDoc.value = false;
          }
      };

      const renderFinanceChart = () => {
          if (financeChartInstance) financeChartInstance.destroy();
          const ctx = document.getElementById('financeChart')?.getContext('2d');
          if (!ctx) return;

          financeChartInstance = new Chart(ctx, {
              type: 'bar',
              data: {
                  labels: ['Consolidado Atualmente'],
                  datasets: [
                      {
                          label: 'Entradas',
                          data: [totalIncomes.value],
                          backgroundColor: '#10b981',
                          borderRadius: 20
                      },
                      {
                          label: 'Saídas',
                          data: [totalExpenses.value],
                          backgroundColor: '#f43f5e',
                          borderRadius: 20
                      }
                  ]
              },
              options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: { legend: { display: false } },
                  scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
              }
          });
      };

      const printProposal = (doc) => {
          const siteLogoUrl = siteLogo.value;
          const docTypeLabel = doc.type === 'orcamento' ? 'Orçamento' : 'Contrato';
          const formattedTotal = parseFloat(doc.total || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2});

          const itemsHtml = (doc.items || []).map((item, idx) => `
              <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f1f5f9; align-items: flex-start;">
                  <span style="font-size: 13px; color: #475569; text-align: justify; padding-right: 30px; line-height: 1.6; flex: 1;">
                    <strong>${idx + 1}.</strong> ${item.description.replace(/\n/g, '<br>')}
                  </span>
                  <span style="font-size: 14px; font-weight: 900; color: #1e293b; white-space: nowrap; margin-left: 20px;">
                    R$ ${parseFloat(item.price || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                  </span>
              </div>
          `).join('');

          const iframe = document.createElement('iframe');
          iframe.style.position = 'fixed';
          iframe.style.right = '0';
          iframe.style.bottom = '0';
          iframe.style.width = '0';
          iframe.style.height = '0';
          iframe.style.border = '0';
          document.body.appendChild(iframe);

          const docContent = `
              <html>
              <head>
                  <title>${docTypeLabel} - ${doc.client_name}</title>
                  <style>
                      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
                      body { font-family: 'Inter', sans-serif; color: #1e293b; padding: 40px; margin: 0; line-height: 1.6; }
                      .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 40px; }
                      .logo { height: 45px; max-width: 200px; object-fit: contain; }
                      .doc-info { text-align: right; }
                      .doc-info h1 { margin: 0; font-size: 22px; font-weight: 900; text-transform: uppercase; color: #4f46e5; letter-spacing: -1px; }
                      .doc-info p { margin: 5px 0 0; font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
                      .client-section { margin-bottom: 40px; background: #f8fafc; padding: 25px; border-radius: 16px; border: 1px solid #f1f5f9; }
                      .client-section h2 { margin: 0 0 10px; font-size: 11px; font-weight: 900; text-transform: uppercase; color: #64748b; letter-spacing: 1px; }
                      .client-name { font-size: 20px; font-weight: 900; color: #1e293b; tracking: -0.5px; }
                      .items-header { display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0; }
                      .items-header span { font-size: 10px; font-weight: 900; text-transform: uppercase; color: #94a3b8; letter-spacing: 1.5px; }
                      .total-section { margin-top: 50px; display: flex; justify-content: flex-end; }
                      .total-box { border-top: 4px solid #1e293b; padding: 20px 0; text-align: right; min-width: 250px; }
                      .total-box p { margin: 0; font-size: 11px; font-weight: 900; text-transform: uppercase; color: #64748b; letter-spacing: 1.5px; }
                      .total-box h3 { margin: 5px 0 0; font-size: 32px; font-weight: 900; color: #1e293b; white-space: nowrap; }
                      .terms-section { margin-top: 50px; padding: 30px; background: #fff; border: 1px solid #f1f5f9; border-radius: 20px; }
                      .terms-section h4 { margin: 0 0 15px; font-size: 11px; font-weight: 900; text-transform: uppercase; color: #64748b; }
                      .terms-content { font-size: 12px; color: #475569; text-align: justify; line-height: 1.8; }
                      .footer { border-top: 1px solid #f1f5f9; margin-top: 80px; padding-top: 30px; text-align: center; font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; }
                      @media print { 
                        body { padding: 0; } 
                        .total-box { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                      }
                  </style>
              </head>
              <body>
                  <div class="header">
                      <img src="${siteLogo.value}" class="logo" onerror="this.style.display='none'">
                      <div class="doc-info">
                          <h1>${docTypeLabel} Comercial</h1>
                          <p>Data de Emissão: ${doc.date}</p>
                          <p>Protocolo: #${doc.id}</p>
                      </div>
                  </div>

                  <div class="client-section">
                      <h2>Apresentado para:</h2>
                      <div class="client-name">${doc.client_name}</div>
                  </div>

                  <div style="margin-bottom: 20px;">
                      <h2 style="font-size: 24px; font-weight: 900; color: #1e293b; margin-bottom: 20px; letter-spacing: -1px;">${doc.title}</h2>
                  </div>

                  ${doc.content ? `
                  <div style="margin-bottom: 40px; font-size: 14px; color: #475569; line-height: 1.8; text-align: justify;">
                      ${doc.content}
                  </div>
                  ` : ''}

                  <div class="items-header">
                      <span>Descrição dos Serviços</span>
                      <span>Valor de Investimento</span>
                  </div>
                  
                  <div class="items-list">
                      ${itemsHtml}
                  </div>

                  <div class="total-section">
                      <div class="total-box">
                          <p>Valor Total</p>
                          <h3>R$ ${formattedTotal}</h3>
                      </div>
                  </div>

                  ${doc.terms ? `
                  <div class="terms-section">
                      <h4>Notas e Condições</h4>
                      <div class="terms-content">${doc.terms.replace(/\n/g, '<br>')}</div>
                  </div>
                  ` : ''}

                  <div class="footer">
                      Acro Manager Enterprise • Documento Digital Válido
                  </div>
              </body>
              </html>
          `;

          iframe.contentWindow.document.open();
          iframe.contentWindow.document.write(docContent);
          iframe.contentWindow.document.close();

          setTimeout(() => {
              iframe.contentWindow.focus();
              iframe.contentWindow.print();
              setTimeout(() => {
                  if (document.body.contains(iframe)) document.body.removeChild(iframe);
              }, 1000);
          }, 1000);
      };

      const copyDocLink = (doc) => {
          if (!doc.public_url) return;
          navigator.clipboard.writeText(doc.public_url).then(() => {
              showToast('Link da proposta copiado!', 'success', 'SISTEMA');
          }).catch(err => {
              console.error('Erro ao copiar link:', err);
              showToast('Erro ao copiar link. Faça manualmente.', 'error');
          });
      };

      const sendDocWhatsApp = async (doc) => {
          loadingDocs.value = true;
          try {
              const res = await fetch(`<?php echo $rest_url; ?>/documents/${doc.id}/whatsapp`, {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' }
              });
              const d = await res.json();
              
              if (d.success) {
                  showToast('Proposta enviada via WhatsApp!', 'success', 'WHATSAPP');
              } else if (d.error === 'telefone_nao_encontrado') {
                  const phone = prompt('Telefone do cliente não encontrado. Digite o número com DDD (ex: 11999999999):');
                  if (phone) {
                      const res2 = await fetch(`<?php echo $rest_url; ?>/documents/${doc.id}/whatsapp`, {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' },
                          body: JSON.stringify({ phone })
                      });
                      const d2 = await res2.json();
                      if (d2.success) showToast('Proposta enviada com sucesso!', 'success', 'WHATSAPP');
                      else showToast('Falha ao enviar.', 'error', 'WHATSAPP');
                  }
              } else {
                  showToast(d.error || 'Falha ao enviar.', 'error', 'WHATSAPP');
              }
          } catch(e) {
              console.error(e);
              showToast('Erro técnico ao enviar WhatsApp.', 'error', 'WHATSAPP');
          } finally {
              loadingDocs.value = false;
          }
      };

      const handleDeleteDoc = async (id) => {
          if(!confirm('Deseja excluir este documento permanentemente?')) return;
          await fetch(`<?php echo $rest_url; ?>/documents/${id}`, { method: 'DELETE', headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
          fetchDocuments();
      };

      const acceptDocument = async (doc) => {
          if(!confirm(`Deseja marcar "${doc.title}" como ACEITO? Isso moverá a tarefa para "Concluído" no Kanban.`)) return;
          
          try {
              const r = await fetch(`<?php echo $rest_url; ?>/documents/${doc.id}/accept`, {
                  method: 'POST',
                  headers: { 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' }
              });
              
              if (r.ok) {
                  showToast('Proposta Aceita! Sucesso no fechamento.', 'success', 'NEGÓCIO');
                  fetchDocuments();
                  fetchTasks();
              }
          } catch(e) {
              console.error(e);
              showToast('Erro ao processar aceite.', 'error');
          }
      };

      const revertDocument = async (doc) => {
          if(!confirm(`Deseja REVERTER o aceite de "${doc.title}"? Isso moverá as tarefas de volta para "A Fazer" no Kanban.`)) return;
          
          try {
              const r = await fetch(`<?php echo $rest_url; ?>/documents/${doc.id}/revert`, {
                  method: 'POST',
                  headers: { 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' }
              });
              
              if (r.ok) {
                  showToast('Aceite Revertido! Status voltou para Pendente.', 'warning', 'SISTEMA');
                  fetchDocuments();
                  fetchTasks();
              }
          } catch(e) {
              console.error(e);
              showToast('Erro ao processar reversão.', 'error');
          }
      };

      const taskColumns = [
          { id: 'todo', name: 'A Fazer', color: 'bg-slate-300', bg: 'bg-slate-100/80' },
          { id: 'doing', name: 'Execução', color: 'bg-sky-500', bg: 'bg-sky-100/50' },
          { id: 'testing', name: 'Homologação', color: 'bg-amber-500', bg: 'bg-amber-100/50' },
          { id: 'done', name: 'Concluído', color: 'bg-emerald-500', bg: 'bg-emerald-100/50' }
      ];

      const fetchTasks = async () => {
          loadingTasks.value = true;
          try {
              const r = await fetch('<?php echo $rest_url; ?>/tasks', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
              tasks.value = await r.json();
          } finally {
              loadingTasks.value = false;
              nextTick(lucide.createIcons);
          }
      };

      const openTaskModal = () => {
          editTarget.value = null;
          taskForm.value = { title: '', status: 'todo', priority: 'medium', client_id: '', client_name: '', description: '' };
          showTaskModal.value = true;
          nextTick(lucide.createIcons);
      };

      const openTaskEditModal = (task) => {
          editTarget.value = { id: task.id, type: 'task' };
          taskForm.value = { 
              title: task.title, 
              status: task.status, 
              priority: task.priority, 
              client_id: task.client_id, 
              client_name: task.client_name || '',
              description: task.description || '' 
          };
          showTaskModal.value = true;
          nextTick(lucide.createIcons);
      };

      const saveTask = async () => {
          if(!taskForm.value.title) return showToast('Preencha o título da tarefa.', 'error');
          savingTask.value = true;
          
          const isEdit = editTarget.value && editTarget.value.type === 'task';
          const url = isEdit ? `<?php echo $rest_url; ?>/tasks/${editTarget.value.id}` : '<?php echo $rest_url; ?>/tasks';
          const method = isEdit ? 'PUT' : 'POST';

          try {
              const r = await fetch(url, {
                  method: method,
                  headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $rest_nonce; ?>' },
                  body: JSON.stringify(taskForm.value)
              });
              if(r.ok) {
                  showTaskModal.value = false;
                  showToast(isEdit ? 'Tarefa atualizada!' : 'Tarefa adicionada ao Kanban!', 'success', 'PRODUÇÃO');
                  fetchTasks();
                  editTarget.value = null;
              }
          } finally {
              savingTask.value = false;
          }
      };

      const onDragTask = (e, task) => {
          e.dataTransfer.setData('taskId', task.id);
          e.dataTransfer.effectAllowed = 'move';
      };

      const onDropTask = async (e, targetStatus) => {
          const id = e.dataTransfer.getData('taskId');
          const task = tasks.value.find(t => t.id == id);
          if (!task || task.status === targetStatus) return;

          const oldStatus = task.status;
          task.status = targetStatus; // Optimistic UI
          nextTick(lucide.createIcons);

          try {
              await fetch(`<?php echo $rest_url; ?>/tasks/${id}`, {
                  method: 'PUT',
                  headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>', 'Content-Type': 'application/json'},
                  body: JSON.stringify({ status: targetStatus })
              });
          } catch(err) {
              task.status = oldStatus;
          }
      };

      const handleDeleteTask = async (id) => {
          if(!confirm('Excluir esta tarefa permanentemente?')) return;
          await fetch(`<?php echo $rest_url; ?>/tasks/${id}`, { method: 'DELETE', headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
          fetchTasks();
      };

      const displayFinanceAmount = computed(() => financeForm.value.amount > 0 ? formatMoney(financeForm.value.amount) : '');


      const handleFinanceAmountInput = (e) => {
          let val = e.target.value.replace(/\D/g, '');
          financeForm.value.amount = parseFloat(val) / 100;
      };

      const fetchLogsAndMetrics = async () => {
          if (!asaasOk.value) return;
          loadingLogs.value = true;
          loadingReports.value = true;
          try {
              const [resMetrics, resLogs, resCommercial, resDre] = await Promise.all([
                  fetch('<?php echo $rest_url; ?>/metrics/chart', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} }).then(r=>r.json()),
                  fetch('<?php echo $rest_url; ?>/logs', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} }).then(r=>r.json()),
                  fetch('<?php echo $rest_url; ?>/metrics/commercial', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} }).then(r=>r.json()),
                  fetch('<?php echo $rest_url; ?>/reports/dre', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} }).then(r=>r.json())
              ]);
              
              metrics.value = resMetrics;
              logs.value = resLogs;
              commercialStats.value = resCommercial;
              dreData.value = resDre; // Assuming dreData is a ref
              
              if(view.value === 'finance') {
                  fetchTransactions();
              }
              
              nextTick(renderChart);
          } finally {
              loadingLogs.value = false;
              loadingReports.value = false;
              nextTick(lucide.createIcons);
          }
      };

      const renderChart = () => {
          if(view.value !== 'reports') return;
          
          // MRR CHART
          const mrrCtx = document.getElementById('mrrChart');
          if(mrrCtx) {
              if(chartInstance) chartInstance.destroy();
              chartInstance = new Chart(mrrCtx, {
                  type: 'line',
                  data: {
                      labels: metrics.value.labels,
                      datasets: [{
                          label: 'Evolução MRR',
                          data: metrics.value.data,
                          borderColor: '#4f46e5',
                          backgroundColor: 'rgba(79, 70, 229, 0.1)',
                          borderWidth: 4,
                          fill: true,
                          tension: 0.4,
                          pointBackgroundColor: '#ffffff',
                          pointBorderColor: '#4f46e5',
                          pointBorderWidth: 2,
                          pointRadius: 4
                      }]
                  },
                  options: {
                      responsive: true,
                      maintainAspectRatio: false,
                      plugins: { legend: { display: false } },
                      scales: {
                          y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { dash: [4, 4] } },
                          x: { grid: { display: false } }
                      }
                  }
              });
          }

          // DRE CHART
          const dreCtx = document.getElementById('dreChart');
          if(dreCtx) {
              if (financeChartInstance) financeChartInstance.destroy();
              financeChartInstance = new Chart(dreCtx, {
                  type: 'bar',
                  data: {
                      labels: (dreData.value.history || []).map(m => m.month),
                      datasets: [
                          {
                              label: 'Receitas',
                              data: (dreData.value.history || []).map(m => m.income),
                              backgroundColor: 'rgba(16, 185, 129, 0.6)',
                              borderRadius: 8
                          },
                          {
                              label: 'Despesas',
                              data: (dreData.value.history || []).map(m => m.expense),
                              backgroundColor: 'rgba(244, 63, 94, 0.6)',
                              borderRadius: 8
                          }
                      ]
                  },
                  options: {
                      responsive: true,
                      maintainAspectRatio: false,
                      plugins: { legend: { display: false } },
                      scales: {
                          y: { beginAtZero: true, grid: { borderDash: [5,5], drawBorder: false } },
                          x: { grid: { display: false } }
                      }
                  }
              });
          }
      };

      const openLeadModal = () => {
        editTarget.value = null;
        form.value = { name: '', phone: '', mrr: null, site_url: '', product: '', notes: '' };
        showModal.value = true;
      };

      const openEditModal = (c) => {
        if (!c) return openLeadModal();
        editTarget.value = c;
        form.value = { 
            name: c.name, 
            phone: c.phone || "", 
            mrr: parseFloat(c.mrr || 0), 
            site_url: c.site_url || "", 
            product: c.product || "",
            notes: c.notes || ""
        };
        showModal.value = true;
      };

      const saveClient = async () => {
        saving.value = true;
        const isUpdate = !!editTarget.value;
        const method = isUpdate ? 'PUT' : 'POST';
        const path = isUpdate ? `/clients/${editTarget.value.id}` : '/leads';

        try {
          const r = await fetch('<?php echo $rest_url; ?>' + path, {
            method: method,
            headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>', 'Content-Type': 'application/json'},
            body: JSON.stringify(form.value)
          });
          const d = await r.json();
          if(!d.error) { 
            if(isUpdate) {
                const idx = clients.value.findIndex(x => x.id === d.id);
                if(idx !== -1) clients.value[idx] = d;
            } else {
                clients.value.push(d);
            }

            // Se o produto é novo, salva na lista global
            if (form.value.product && !productOptions.value.includes(form.value.product)) {
                productOptions.value.push(form.value.product);
                fetch('<?php echo $rest_url; ?>/products', {
                    method: 'POST',
                    headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>', 'Content-Type': 'application/json'},
                    body: JSON.stringify({ products: productOptions.value })
                });
            }

            showModal.value = false; 
            form.value = { name:'', phone:'', mrr:null, site_url:'', product:'', notes:'' }; 
            showToast(isUpdate ? 'Dados atualizados!' : 'Novo registro criado.');
            fetchDocuments();
            fetchTasks();
            fetchLogsAndMetrics(); // Assuming fetchFinanceStats is part of this or needs to be called separately
            fetchProducts();
            nextTick(lucide.createIcons);
          }
        } finally {
          saving.value = false;
          nextTick(lucide.createIcons);
        }
      };

      const executeDelete = async () => {
        const id = deleteTarget.value.id;
        clients.value = clients.value.filter(c => c.id !== id);
        deleteTarget.value = null;
        await fetch(`<?php echo $rest_url; ?>/clients/${id}`, { method: 'DELETE', headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
        showToast('Registro excluído permanentemente.');
      };


      
      const sendWhatsApp = async (e, c, reminderType = 'manual') => {
        const btn = e.currentTarget;
        const icon = btn.querySelector('i');
        if(icon) icon.classList.add('animate-spin');
        try {
          await fetch(`<?php echo $rest_url; ?>/clients/${c.id}/whatsapp`, { 
              method: 'POST', 
              headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>', 'Content-Type': 'application/json'},
              body: JSON.stringify({ reminder_type: reminderType })
          });
        } finally {
          showToast('Notificação WhatsApp enviada!', 'success', 'WHATSAPP');
          if(icon) icon.classList.remove('animate-spin');
          nextTick(lucide.createIcons);
        }
      };

      const toggleBlock = async (c) => {
          const isBlocked = c.site_status === 'blocked';
          const msg = isBlocked 
                ? `Tem certeza que deseja DESBLOQUEAR manualmente o site de ${c.name}?` 
                : `ATENÇÃO: Deseja BLOQUEAR o site de ${c.name} imediatamente por quebra de contrato?`;
                
          if (!confirm(msg)) return;
          
          try {
              const res = await fetch(`<?php echo $rest_url; ?>/client-toggle-block/${c.id}`, { method: 'POST', headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
              const d = await res.json();
              if(d.success) {
                  c.site_status = d.site_status;
                  showToast(c.site_status==='blocked' ? 'Acesso ao Site Bloqueado' : 'Acesso ao Site Reativado', c.site_status==='blocked' ? 'error' : 'success');
                  nextTick(lucide.createIcons);
              }
          } catch(e) {
              console.error(e);
              showToast("Erro técnico ao alterar bloqueio do site.", "error");
          }
      };

      const syncAsaas = async (e, c) => {
        const btn = e.currentTarget;
        const icon = btn.querySelector('i');
        icon.classList.add('animate-spin');
        try {
          const r = await fetch(`<?php echo $rest_url; ?>/clients/${c.id}/sync`, { 
            method: 'POST', 
            headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} 
          });
          const d = await r.json();
          if(d.success) {
            c.asaas_id = d.asaas_id;
            showToast('Dados sincronizados com sucesso.');
          }
        } catch (err) {
            console.error('Erro na sincronização:', err);
        } finally {
          icon.classList.remove('animate-spin');
        }
      };

      const openInvoicesModal = async (c) => {
        invoices.value = [];
        showInvoicesModal.value = true;
        loadingInvoices.value = true;
        try {
          const r = await fetch(`<?php echo $rest_url; ?>/clients/${c.id}/invoices`, { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
          const d = await r.json();
          invoices.value = d.data || [];
          if(d.status && c.status !== d.status) {
              c.status = d.status; // Real-time UI update!
          }
        } finally {
          loadingInvoices.value = false;
          nextTick(lucide.createIcons);
        }
      };

      // ── CRM PIPELINE LOGIC ──
      const crmTab = ref('sales');
      const allCrmColumns = [
          { id: 'prospect', label: 'Prospecção (API)', group: 'sales' },
          { id: 'onboarding', label: 'Implantação (Setup)', group: 'sales' },
          { id: 'ativo', label: 'Ativo (Saudável)', group: 'success' },
          { id: 'risk', label: 'Risco de Churn (Inadimplente)', group: 'success' },
          { id: 'lost', label: 'Inativo / Block', group: 'success' }
      ];
      
      const crmColumns = computed(() => {
          return allCrmColumns.filter(c => c.group === crmTab.value);
      });
      
      const crmClients = computed(() => {
          if (!search.value) return clients.value;
          const q = (search.value || '').toLowerCase();
          return clients.value.filter(c => {
              const name = (c.name || '').toLowerCase();
              const phone = (c.phone || '');
              return name.includes(q) || phone.includes(q);
          });
      });

      const getPipelineStage = (c) => {
          if (c.site_status === 'blocked') return 'lost';
          if (c.status === 'inadimplente') return 'risk';
          if (c.status === 'ativo' && !c.pipeline_stage) return 'ativo';
          return c.pipeline_stage || 'prospect';
      };

      const onDragStart = (e, client) => {
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('clientId', client.id);
      };

      const onDrop = async (e, targetStage) => {
          const clientId = parseInt(e.dataTransfer.getData('clientId'), 10);
          if (!clientId) return;
          const client = clients.value.find(c => c.id === clientId);
          if (!client) return;
          
          const currentStage = getPipelineStage(client);
          
          if(targetStage === 'risk' || targetStage === 'lost' || currentStage === 'risk' || currentStage === 'lost') {
              alert("🚀 Movimentação Automática Ativa!\n\nAs Colunas Risco e Inativo são controladas soberanamente pelo Asaas e o Bloqueador de Site. Resolva a fatura e o cliente voltará sozinho.");
              return;
          }

          if (currentStage !== targetStage) {
              const oldStage = client.pipeline_stage;
              client.pipeline_stage = targetStage; // Optimistic UI
              nextTick(lucide.createIcons);
              
              try {
                  const res = await fetch(`<?php echo $rest_url; ?>/clients/${clientId}`, {
                      method: 'PUT',
                      headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>', 'Content-Type': 'application/json'},
                      body: JSON.stringify({ pipeline_stage: targetStage })
                  });
                  if(!res.ok) throw new Error("Falha ao mover card");
              } catch(err) {
                  console.error(err);
                  client.pipeline_stage = oldStage; // Revert
                  alert("Houve um erro técnico de conexão. O card foi recuado.");
                  nextTick(lucide.createIcons);
              }
          }
      };

      const moveStageByArrow = async (client, direction) => {
          const arr = ['prospect', 'onboarding', 'ativo'];
          const currentStage = getPipelineStage(client);
          const currentIndex = arr.indexOf(currentStage);

          if (currentIndex === -1) return; // Risco ou Lost nao podem ser movidos manualmente
          
          const newIndex = currentIndex + direction;
          if (newIndex < 0 || newIndex >= arr.length) return;
          
          const targetStage = arr[newIndex];
          const oldStage = client.pipeline_stage;
          
          client.pipeline_stage = targetStage;
          nextTick(lucide.createIcons);
          
          try {
              const res = await fetch(`<?php echo $rest_url; ?>/clients/${client.id}`, {
                  method: 'PUT',
                  headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>', 'Content-Type': 'application/json'},
                  body: JSON.stringify({ pipeline_stage: targetStage })
              });
              if(!res.ok) throw new Error("Falha ao transitar card via setas");
          } catch(err) {
              console.error(err);
              client.pipeline_stage = oldStage; // Revert
              alert("Houve um erro técnico. O card foi recuado.");
              nextTick(lucide.createIcons);
          }
      };

      const exportToCSV = (data, filename, headers) => {
          let csv = headers.join(',') + '\n';
          data.forEach(row => {
              csv += headers.map(h => {
                  let val = row[h] || '';
                  if (typeof val === 'string') val = val.replace(/"/g, '""');
                  return `"${val}"`;
              }).join(',') + '\n';
          });
          const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
          const link = document.createElement("a");
          const url = URL.createObjectURL(blob);
          link.setAttribute("href", url);
          link.setAttribute("download", filename);
          link.style.visibility = 'hidden';
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
      };

      const mrrAtivo = computed(() => {
          return clients.value.filter(c => c.status === 'ativo').reduce((acc, c) => acc + parseFloat(c.mrr || 0), 0);
      });
      
      const mrrRisco = computed(() => {
          return clients.value.filter(c => c.status === 'inadimplente').reduce((acc, c) => acc + parseFloat(c.mrr || 0), 0);
      });
      
      const pipelineBreakdown = computed(() => {
          const tally = { 'prospect': 0, 'onboarding': 0, 'ativo': 0, 'risk': 0, 'lost': 0 };
          clients.value.forEach(c => {
              const st = getPipelineStage(c);
              if(tally[st] !== undefined) tally[st]++;
          });
          return tally;
      });
      
      const productBreakdown = computed(() => {
          const tally = {};
          clients.value.filter(c => getPipelineStage(c) !== 'lost' && getPipelineStage(c) !== 'risk').forEach(c => {
              const p = (c.product && c.product.trim() !== '') ? c.product.trim() : 'Não Etiquetado';
              if (!tally[p]) tally[p] = { count: 0, mrr: 0 };
              tally[p].count += 1;
              tally[p].mrr += parseFloat(c.mrr || 0);
          });
          return Object.keys(tally).map(k => ({ name: k, count: tally[k].count, mrr: tally[k].mrr })).sort((a,b) => b.mrr - a.mrr);
      });

      const getWaLink = (phone) => {
          if (!phone) return '#';
          let s = phone.replace(/\D/g,'');
          if (s.length === 10 || s.length === 11) s = '55' + s;
          return `https://wa.me/${s}`;
      };

      onMounted(() => {
        fetchClients().then(() => {
            syncOverduesSilently();
        });
        fetchDashboardStats();
        fetchFinanceCategories();
        fetchTransactions(); // Sempre busca para alimentar o Snapshot no Dashboard
        fetchDocuments();     // Alimenta o card de Pipeline no Dashboard
        fetchLogsAndMetrics(); // Alimenta os indicadores de desempenho comercial
        fetchProducts();
        fetchDRE();
      });

      watch([view, showModal, showInvoicesModal, deleteTarget, filterStatus, syncingStatus, crmTab, filteredClients, crmClients, showFinanceModal, showDocModal, showTaskModal], () => {
          if(view.value === 'reports') {
              if(logs.value.length === 0 && !loadingReports.value) fetchLogsAndMetrics();
              else nextTick(renderChart);
          }
          if(view.value === 'finance' || view.value === 'dashboard') {
              if(transactions.value.length === 0 && !loadingFinance.value) {
                fetchTransactions();
              } else if (view.value === 'finance') {
                nextTick(renderFinanceChart);
              } else if (view.value === 'dashboard') {
                nextTick(renderFinanceChart); // Caso precise renderizar algum mini-grafico no futuro
              }
          }
          if(view.value === 'docs' && documents.value.length === 0 && !loadingDocs.value) {
              fetchDocuments();
          }
          if(view.value === 'tasks' && tasks.value.length === 0 && !loadingTasks.value) {
              fetchTasks();
          }
          nextTick(() => { if (window.lucide) lucide.createIcons(); });
      });

      return { view, clients, loading, search, filterStatus, showModal, showInvoicesModal, invoices, loadingInvoices, asaasBalance, loadingBalance, importing, massBilling, syncingStatus, saving, logs, loadingLogs, metrics, loadingReports, deleteTarget, editTarget, asaasOk, waOk, settingsUrl, form, filteredClients, crmClients, totalMRR, activeCount, overdueCount, fetchClients, fetchDashboardStats, fetchLogsAndMetrics, importGateway, massBillOverdue, syncOverduesSilently, saveClient, executeDelete, formatMoney, sendWhatsApp, toggleBlock, syncAsaas, openInvoicesModal, displayMRR, handleMRRInput, displayPhone, handlePhoneInput, formatPhone, openEditModal, openLeadModal, crmTab, crmColumns, getPipelineStage, onDragStart, onDrop, getWaLink, moveStageByArrow, mrrAtivo, mrrRisco, pipelineBreakdown, productBreakdown, currentPage, totalPages, totalFiltered, itemsPerPage,
               showFinanceModal, loadingFinance, savingFinance, transactions, financeForm, financeCategories, newCategoryInput, addingCategory, managingCategories, categoryDeleteConfirm, savingCategory, addFinanceCategory, deleteFinanceCategory, saveTransaction, openFinanceEditModal, handleDeleteTransaction, displayFinanceAmount, handleFinanceAmountInput, totalIncomes, totalPendingIncomes, totalExpenses, totalPendingExpenses, profitMargin, projectedEndBalance, topExpenseCategory, arpu, financeHistoryTab, filteredTransactions, financeDateStart, financeDateEnd, financeStatusFilter, setFinancePeriod, financePeriod, isOverdue, markAsPaid, printFinanceReport, siteLogo,
               showDocModal, loadingDocs, savingDoc, documents, docForm, docFormTotal, openDocModal, openDocEditModal, saveDocument, closeDocModal, handleDeleteDoc, acceptDocument, revertDocument,
               showTaskModal, loadingTasks, savingTask, tasks, taskForm, taskColumns, openTaskModal, openTaskEditModal, saveTask, onDragTask, onDropTask, handleDeleteTask,
               productOptions, fetchProducts, dreData, fetchDRE, showAllLogs, exportToCSV,
               toasts, showToast, commercialStats, gatewayLabel, importGateway, printProposal, sendDocWhatsApp, copyDocLink, isNewDocClient, onDocClientSelect, handleDocItemPriceInput, handleDocManualTotalInput
      };
    }
  }).mount('#acro-app');
})();
</script>
