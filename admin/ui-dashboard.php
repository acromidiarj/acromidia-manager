<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$rest_nonce = wp_create_nonce( 'wp_rest' );
$rest_url   = rest_url( 'acromidia/v1' );
$settings_url = admin_url( 'admin.php?page=acromidia-settings' );
$asaas_ok     = \Acromidia_Settings::has('asaas_api_key');
$wa_ok        = \Acromidia_Settings::has('wa_token') && \Acromidia_Settings::has('wa_phone_id');
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* ══ DESIGN SYSTEM ACRO MANAGER — V3.2 (FIX SYNC) ══ */
:root {
  --acro-primary: #4f46e5;
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
.input-label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--acro-slate); margin-bottom: 8px; margin-left: 4px; }
.modern-input {
    width: 100% !important; height: 56px !important; background: #fff !important; 
    border: 2px solid var(--acro-border) !important; border-radius: 16px !important; 
    padding: 0 20px 0 54px !important;
    font-size: 15px !important; font-weight: 600 !important; color: var(--acro-text) !important; 
    transition: all 0.2s !important; outline: none !important; box-sizing: border-box !important;
}
.modern-input:focus { border-color: var(--acro-primary) !important; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1) !important; }

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
.badge-warning { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
.badge-danger { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
.badge-info { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }

[v-cloak] { display: none; }
.fade-enter-active, .fade-leave-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(30px) scale(0.95); }
</style>

<div id="acro-app" class="acro-app pb-20" v-cloak>

  <!-- ══ HEADER ══ -->
  <header class="bg-white/90 backdrop-blur-md border-b sticky top-0 z-50 px-8 py-4">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-200">
           <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        </div>
        <div>
          <h1 class="font-black text-slate-900 leading-none text-xl">Acro Manager</h1>
          <span class="text-[9px] font-black uppercase text-indigo-600 tracking-[0.2em]">Management Suite</span>
        </div>
      </div>

      <nav class="flex bg-slate-100 p-1.5 rounded-2xl">
        <button @click="view='dashboard'" :class="view==='dashboard'?'bg-white shadow-sm text-indigo-600':'text-slate-500'" class="px-6 py-2 rounded-xl text-sm font-black transition-all">Geral</button>
        <button @click="view='clients'" :class="view==='clients'?'bg-white shadow-sm text-indigo-600':'text-slate-500'" class="px-6 py-2 rounded-xl text-sm font-black transition-all">Carteira</button>
        <button @click="view='reports'" :class="view==='reports'?'bg-white shadow-sm text-indigo-600':'text-slate-500'" class="px-6 py-2 rounded-xl text-sm font-black transition-all">Relatórios</button>
      </nav>

      <div class="flex items-center gap-5">
        <a :href="settingsUrl" class="w-10 h-10 rounded-2xl flex items-center justify-center text-slate-400 hover:bg-slate-50 transition-all"><i data-lucide="settings" class="w-5 h-5"></i></a>
        <button @click="openCreateModal" class="btn-primary">Novo Cliente</button>
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
            <span :class="asaasOk?'badge-success':'badge-warning'" class="badge">Gateway Asaas</span>
            <span :class="waOk?'badge-success':'badge-warning'" class="badge">WhatsApp Ativo</span>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
        <div class="card-glass p-8 border-b-4 border-b-sky-500">
            <span class="input-label">Saldo em Conta Asaas</span>
            <div v-if="loadingBalance" class="mt-2 text-sky-500"><i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i></div>
            <p v-else class="text-4xl font-black text-slate-900 tracking-tighter mt-2">R$ {{ formatMoney(asaasBalance) }}</p>
        </div>
        <div class="card-glass p-8 border-b-4 border-b-indigo-500">
            <span class="input-label">Receita Recorrente (MRR)</span>
            <p class="text-4xl font-black text-slate-900 tracking-tighter mt-2">R$ {{ totalMRR }}</p>
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

      <div class="card-glass overflow-hidden shadow-2xl shadow-indigo-100/20">
        <div class="px-8 py-6 bg-slate-50/50 border-b flex justify-between items-center">
            <h3 class="font-black text-slate-800 uppercase text-xs tracking-widest">Inclusões Recentes</h3>
        </div>
        <div v-if="loading" class="p-20 text-center"><i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto text-indigo-600"></i></div>
        <div v-else-if="!clients.length" class="p-20 text-center text-slate-400 font-black text-xs uppercase">Sem registros.</div>
        <div v-else class="divide-y divide-slate-100">
            <div v-for="c in clients.slice(0,5)" :key="c.id" class="px-8 py-6 flex items-center justify-between hover:bg-slate-50/50 transition-all">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-black text-xl shadow-lg shadow-indigo-100">{{ c.name.charAt(0) }}</div>
                    <div>
                        <p class="font-black text-slate-900 text-lg leading-none">{{ c.name }}</p>
                        <p class="text-xs text-slate-400 font-bold mt-2 uppercase tracking-wider">{{ formatPhone(c.phone) || 'Sem contato' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-black text-indigo-600 text-xl leading-none">R$ {{ formatMoney(c.mrr) }}</p>
                    <span :class="c.status==='ativo'?'badge-success':'badge-warning'" class="badge mt-2 uppercase">{{ c.status==='ativo'?'EM DIA':'PENDENTE' }}</span>
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
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            <!-- Gráfico de MRR Projetado -->
            <div class="lg:col-span-2 card-glass p-8">
                <h3 class="input-label mb-6">Crescimento Projetado (MRR) - 6 Meses</h3>
                <div class="h-[300px] w-full flex items-center justify-center relative">
                    <canvas id="mrrChart" class="absolute inset-0"></canvas>
                    <div v-if="loadingReports" class="text-indigo-600"><i data-lucide="loader-2" class="w-8 h-8 animate-spin"></i></div>
                </div>
            </div>
            
            <!-- Resumo Rápido -->
            <div class="flex flex-col gap-6">
                <div class="card-glass p-8 border-b-4 border-b-sky-500 bg-gradient-to-br from-white to-sky-50">
                    <span class="input-label text-sky-600">MRR Projetado Atual</span>
                    <p class="text-4xl font-black text-slate-900 tracking-tighter mt-2">R$ {{ formatMoney(metrics.projected_mrr) }}</p>
                </div>
                <div class="card-glass p-8 border-b-4 border-b-rose-500 bg-gradient-to-br from-white to-rose-50 flex-1">
                    <span class="input-label text-rose-600">Alerta de Churn e Risco</span>
                    <p class="text-4xl font-black text-slate-900 tracking-tighter mt-2">{{ metrics.churn_risk }} clientes</p>
                    <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-widest leading-relaxed">Em estado de inadimplência, com risco alto de cancelamento definitivo.</p>
                </div>
            </div>
        </div>
        
        <!-- Logs de Cobrança -->
        <div class="card-glass overflow-hidden shadow-2xl shadow-indigo-100/10">
            <div class="px-8 py-6 bg-slate-50/50 border-b flex justify-between items-center">
                <h3 class="font-black text-slate-800 uppercase text-xs tracking-widest flex items-center gap-2"><i data-lucide="message-square" class="w-4 h-4 text-emerald-500"></i> Auditoria de Disparos WhatsApp</h3>
            </div>
            <div v-if="loadingLogs" class="p-16 text-center text-slate-400"><i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto text-indigo-600 mb-4"></i></div>
            <div v-else-if="!logs.length" class="p-16 text-center text-slate-400 font-black text-xs uppercase tracking-widest">Nenhuma mensagem disparada recentemente.</div>
            <div v-else class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                <div v-for="log in logs" :key="log.id" class="px-8 py-6 flex flex-col md:flex-row md:items-center justify-between hover:bg-slate-50/80 transition-all gap-4">
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

    <!-- MONITORAMENTO DE CARTEIRA -->
    <div v-if="view==='clients'">
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-8">
            <div class="flex flex-col gap-5">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-4xl font-black text-slate-900 tracking-tighter">Monitoramento</h2>
                    <button v-if="asaasOk" @click="importAsaas" :disabled="importing" class="btn-primary !bg-sky-500 hover:!bg-sky-600 !py-2 !px-4 !text-[11px] !shadow-lg ml-2">
                        <i data-lucide="cloud-download" class="w-4 h-4" :class="{'animate-pulse': importing}"></i> {{ importing ? 'Buscando...' : 'Importar Asaas' }}
                    </button>
                    
                    <button v-if="waOk && overdueCount > 0" @click="massBillOverdue" :disabled="massBilling" class="btn-primary !bg-rose-500 hover:!bg-rose-600 !py-2 !px-4 !text-[11px] !shadow-lg shadow-rose-200">
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
            
            <div class="relative w-full md:w-80">
                <i data-lucide="search" class="input-icon"></i>
                <input v-model="search" type="text" placeholder="Localizar por nome..." class="modern-input">
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
                            <p class="text-xs text-slate-400 font-bold mt-1 uppercase">{{ formatPhone(c.phone) }}</p>
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
                                    <div class="absolute bottom-full mb-2 right-0 md:left-1/2 md:-translate-x-1/2 hidden group-hover:flex flex-col gap-1.5 bg-white shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] p-2.5 rounded-2xl border border-slate-100 z-50 min-w-[160px] animate-in fade-in zoom-in-95 duration-200">
                                        <button @click.prevent="sendWhatsApp($event, c, '5_days_before')" class="text-[10px] font-black text-slate-500 hover:text-indigo-600 text-left px-3 py-2 uppercase hover:bg-indigo-50 rounded-xl transition-colors whitespace-nowrap truncate"><i data-lucide="clock" class="w-3 h-3 inline-block mb-0.5 opacity-50 mr-1"></i> Lembrete Preventivo</button>
                                        <button @click.prevent="sendWhatsApp($event, c, '7_days_after')" class="text-[10px] font-black text-rose-500 hover:text-rose-600 text-left px-3 py-2 uppercase hover:bg-rose-50 rounded-xl transition-colors whitespace-nowrap truncate"><i data-lucide="alert-triangle" class="w-3 h-3 inline-block mb-0.5 opacity-50 mr-1"></i> Aviso (Atrasado 7 Dias)</button>
                                        <button @click.prevent="sendWhatsApp($event, c, '15_days_after')" class="text-[10px] font-black text-rose-700 hover:text-rose-800 text-left px-3 py-2 uppercase hover:bg-rose-100 rounded-xl transition-colors whitespace-nowrap truncate"><i data-lucide="ban" class="w-3 h-3 inline-block mb-0.5 opacity-50 mr-1"></i> Ameaça (15 Dias)</button>
                                    </div>
                                </div>

                                <button @click="syncAsaas($event, c)" class="p-2.5 rounded-xl border-2 border-indigo-100 text-indigo-600 hover:bg-indigo-50 transition-all flex items-center justify-center shrink-0" title="Sincronizar Asaas"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
                                <button @click="openEditModal(c)" class="p-2.5 rounded-xl border-2 border-blue-100 text-blue-600 hover:bg-blue-50 transition-all flex items-center justify-center shrink-0" title="Editar"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                                <button @click="deleteTarget=c" class="p-2.5 rounded-xl border-2 border-rose-100 text-rose-600 hover:bg-rose-50 transition-all flex items-center justify-center shrink-0" title="Remover"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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
                <h3 class="text-4xl font-black text-slate-900 tracking-tighter">{{ editTarget ? 'Editar Registro' : 'Onboarding' }}</h3>
                <p class="text-slate-500 font-bold mt-2">{{ editTarget ? 'Atualize as informações do contrato.' : 'Dê os primeiros passos com seu novo cliente.' }}</p>
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
                            <label class="input-label">Valor do Ciclo (MRR)</label>
                            <div class="relative">
                                <div class="input-icon font-black text-[12px] text-slate-400">R$</div>
                                <input :value="displayMRR" @input="handleMRRInput" type="text" class="modern-input" placeholder="R$ 0,00" required>
                            </div>
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

</div>

<script>
(function() {
  const { createApp, ref, computed, onMounted, nextTick, watch } = Vue;

  createApp({
    setup() {
      const view = ref('dashboard');
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
      const loadingReports = ref(false);
      let chartInstance = null;
      const deleteTarget = ref(null);
      const editTarget = ref(null);
      const asaasOk = ref(<?php echo $asaas_ok?'true':'false'; ?>);
      const waOk = ref(<?php echo $wa_ok?'true':'false'; ?>);
      const settingsUrl = '<?php echo esc_url($settings_url); ?>';
      
      const form = ref({ name: '', phone: '', mrr: null });

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

      // ── FORMATADORES DE UI ──
      const formatPhone = (v) => {
        if(!v) return "";
        let n = v.replace(/\D/g, "");
        if(n.length === 11) return "(" + n.substring(0,2) + ") " + n.substring(2,7) + "-" + n.substring(7);
        if(n.length === 10) return "(" + n.substring(0,2) + ") " + n.substring(2,6) + "-" + n.substring(6);
        return v;
      };

      const filteredClients = computed(() => {
        let list = clients.value;
        if (filterStatus.value !== 'todos') {
            list = list.filter(c => c.status === filterStatus.value);
        }
        if (!search.value) return list;
        const q = search.value.toLowerCase();
        return list.filter(c => c.name.toLowerCase().includes(q) || (c.phone && c.phone.includes(q)));
      });

      const totalMRR = computed(() => clients.value.reduce((a,c) => a + parseFloat(c.mrr||0), 0).toLocaleString('pt-BR',{minimumFractionDigits:2}));
      const activeCount = computed(() => clients.value.filter(c => c.status==='ativo').length);
      const overdueCount = computed(() => clients.value.filter(c => c.status==='inadimplente').length);

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

      const importAsaas = async () => {
        importing.value = true;
        try {
          const r = await fetch('<?php echo $rest_url; ?>/asaas/import', { method: 'POST', headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
          const d = await r.json();
          if(d.success) {
            alert(`Sincronização concluída: ${d.imported} novos vínculos estabelecidos com sucesso.`);
            await fetchClients();
          } else {
            alert('Falha na importação: ' + (d.error||'Desconhecida'));
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
        alert(`Cobrança em massa finalizada! ✅\n${successCount} de ${overdues.length} mensagens disparadas com sucesso.`);
      };

      const syncOverduesSilently = async () => {
        if(!asaasOk.value) return;
        syncingStatus.value = true;
        try {
            const r = await fetch(`<?php echo $rest_url; ?>/asaas/sync-overdue`, { method: 'POST', headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} });
            const res = await r.json();
            if(res.success && res.updated > 0) {
               await fetchClients();
            }
        } catch(e) {
            console.error('Erro no Auto-Sync de Inadimplência', e);
        } finally {
            syncingStatus.value = false;
            nextTick(lucide.createIcons);
        }
      };

      const fetchLogsAndMetrics = async () => {
          if (!asaasOk.value) return;
          loadingLogs.value = true;
          loadingReports.value = true;
          try {
              const [resMetrics, resLogs] = await Promise.all([
                  fetch('<?php echo $rest_url; ?>/metrics/chart', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} }).then(r=>r.json()),
                  fetch('<?php echo $rest_url; ?>/logs', { headers: {'X-WP-Nonce': '<?php echo $rest_nonce; ?>'} }).then(r=>r.json())
              ]);
              
              metrics.value = resMetrics;
              logs.value = resLogs;
              
              nextTick(renderChart);
          } finally {
              loadingLogs.value = false;
              loadingReports.value = false;
              nextTick(lucide.createIcons);
          }
      };

      const renderChart = () => {
          if(view.value !== 'reports') return;
          const ctx = document.getElementById('mrrChart');
          if(!ctx) return;
          
          if(chartInstance) chartInstance.destroy();
          
          chartInstance = new Chart(ctx, {
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
      };

      const openCreateModal = () => {
        editTarget.value = null;
        form.value = { name: '', phone: '', mrr: null, site_url: '' };
        showModal.value = true;
      };

      const openEditModal = (c) => {
        editTarget.value = c;
        form.value = { name: c.name, phone: c.phone || "", mrr: parseFloat(c.mrr || 0), site_url: c.site_url || "" };
        showModal.value = true;
      };

      const saveClient = async () => {
        saving.value = true;
        const isUpdate = !!editTarget.value;
        const method = isUpdate ? 'PUT' : 'POST';
        const path = isUpdate ? `/clients/${editTarget.value.id}` : '/clients';

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
                clients.value.push(d);             }
            showModal.value = false; 
            form.value = { name:'', phone:'', mrr:null, site_url:'' }; 
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
      };

      const formatMoney = v => parseFloat(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2});
      
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
                  nextTick(lucide.createIcons);
              }
          } catch(e) {
              console.error(e);
              alert("Erro técnico ao alterar bloqueio do site.");
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
            // Opcional: mostrar um feedback visual de sucesso
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

      onMounted(() => {
        fetchClients().then(() => {
            syncOverduesSilently();
        });
        fetchDashboardStats();
      });
      watch([view, showModal, showInvoicesModal, deleteTarget, filterStatus, syncingStatus], () => {
          if(view.value === 'reports') {
              if(logs.value.length === 0 && !loadingReports.value) fetchLogsAndMetrics();
              else nextTick(renderChart);
          }
          nextTick(lucide.createIcons);
      });

      return { view, clients, loading, search, filterStatus, showModal, showInvoicesModal, invoices, loadingInvoices, asaasBalance, loadingBalance, importing, massBilling, syncingStatus, saving, logs, loadingLogs, metrics, loadingReports, deleteTarget, editTarget, asaasOk, waOk, settingsUrl, form, filteredClients, totalMRR, activeCount, overdueCount, fetchClients, fetchDashboardStats, fetchLogsAndMetrics, importAsaas, massBillOverdue, syncOverduesSilently, saveClient, executeDelete, formatMoney, sendWhatsApp, toggleBlock, syncAsaas, openInvoicesModal, displayMRR, handleMRRInput, displayPhone, handlePhoneInput, formatPhone, openCreateModal, openEditModal };
    }
  }).mount('#acro-app');
})();
</script>
