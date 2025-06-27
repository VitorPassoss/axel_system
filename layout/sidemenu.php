<style>
  /* MENU LATERAL */
  #sideMenu {
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    width: 16.5%;
    font-size: 0.8rem;
    max-width: 100%;
    z-index: 30;
  }

  /* BOTÃO DE MENU (HAMBURGUER) */
  #toggleButton {
    position: fixed;
    top: 0px;
    left: 16.5%;
    z-index: 40;
    background-color: #fff;
    color: gray;
    border: none;
    padding: 6px;
    border-radius: 0 5px 5px 0;
    cursor: pointer;
    transition: left 0.1s ease;
  }

  /* CONTEÚDO DA PÁGINA */
  body {
    margin-left: 15.5%;
  }

  /* MOBILE - MENU ESCONDIDO */
  @media (max-width: 768px) {
    #sideMenu {
      display: none;
      width: 80%;
      box-shadow: 2px 0 5px rgba(0, 0, 0, 0.3);
      background-color: white;
      z-index: 1000;
    }

    #toggleButton {
      left: 10px;
    }

    body {
      margin-left: 0;
    }

    body.menu-open #sideMenu {
      display: block;
    }
  }
</style>

<style>
  li.group:hover>ul {
    display: block;
  }
</style>


<button id="toggleButton" class="shadow" onclick="toggleMenu()">
  <i class="fas fa-bars "></i>
</button>


<div id="sideMenu" class="  side-menu fixed top-0 left-[-100%] shadow-xl w-64 min-h-screen bg-[#FFFFFF] backdrop-blur-lg p-4 space-y-2 transition-all duration-300 ease-in-out transform rounded-lg z-30">
  <div class="flex justify-between items-center ">
    <div class="flex items-center ">
      <img class="w-12" src="../assets/logo/il_fullxfull.2974258879_pxm3.webp" alt="">
      <h2 class="text-[14px] text-[#2B3A4B] font-bold">Zion <span class="text-[10px]">Corporative</span></h2>
    </div>

  </div>

  <div id="menuContent"></div>

  <div class="absolute bottom-0 text-sm text-gray-700 leading-4 mb-2">

    <p class="text-[14px] truncate w-[200px] whitespace-nowrap overflow-hidden" id="userEmail"></p>
    <p class="text-[12px]" id="userCompany"></p>

    <button id="logoutButton" class="flex items-center py-4 mt-2  rounded transition-all   text-gray-800">
      <i class="fas fa-sign-out-alt mr-3"></i> Sair
    </button>
    
     <p class="text-[10px]">1.0.1</p>

  </div>
  
</div>
<script>
  document.getElementById('logoutButton').addEventListener('click', function() {
    // Limpar os dados do usuário (por exemplo, user_id) e redirecionar
    localStorage.removeItem('user_id'); // Supondo que você use localStorage
    localStorage.removeItem('user_email'); // Limpar outro dado se necessário
    localStorage.removeItem('user_company'); // Limpar outro dado se necessário


    // Redirecionar para a página de login ou outra página
    window.location.href = "../onboard/login.php"; // Troque pelo URL do login ou onde você deseja redirecionar
  });
</script>



<script>
  function getMenuWithDropdown(iconClass, title, items) {
    const submenuId = `submenu-${title.toLowerCase().replace(/\s+/g, '-')}`;
    return `
    <li class="relative">
      <div onclick="toggleSubmenu('${submenuId}')" class="flex items-center py-2 px-4 rounded cursor-pointer text-[#545D69] hover:bg-[#F3F5F7] hover:text-[#2B3A4B] transition-all">
        <i style="color:rgb(168, 168, 168) !important;" class=" mr-3 text-white ${iconClass}"></i> ${title}
        <i  class="fas fa-chevron-down ml-auto text-xs text-[#2B3A4B] group-hover:text-[#2B3A4B]"></i>
      </div>
      <ul id="${submenuId}" class="ml-6 mt-1 space-y-1 hidden">
        ${items.map(sub => `
          <li>
            <a href="${sub.href}" class="flex items-center px-4 py-2 rounded text-[#545D69] hover:bg-[#E9EBED] hover:text-[#2B3A4B] transition-all">
              <i class="${sub.icon}" style="color: #A5ABB3; margin-right: 0.75rem;"></i> ${sub.label}
            </a>
          </li>
        `).join('')}
      </ul>
    </li>
  `;
  }
</script>

<script>
  function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    if (submenu.classList.contains('hidden')) {
      submenu.classList.remove('hidden');
    } else {
      submenu.classList.add('hidden');
    }
  }
</script>


<script>
  fetch('../backend/profiles.php')
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        console.error(data.error);
      } else {
        const usuario = data.usuario;
        const setores = data.setores;
        const menuContent = document.getElementById('menuContent');

        function renderMenu(setor_nome) {
          const currentFolder = location.pathname.split('/').filter(Boolean).pop().toLowerCase().replace('.php', '');

          function getMenuItem(href, iconClass, text) {
            const targetFolder = href.replace('../', '').toLowerCase();
            const isActive = currentFolder === targetFolder;
            const bgClass = isActive ? 'bg-[#F3F5F7] text-[#2B3A4B]' : 'text-[#545D69] hover:bg-[#F3F5F7] hover:text-[#2B3A4B]';
            const iconClassExtra = isActive ? 'text-[#2B3A4B]' : 'text-[#A5ABB3]';
            const iconClassStyle = isActive ? '#2B3A4B' : '#A5ABB3';

            return `<li>
            <a href="${href}" class="flex items-center py-2 px-4 rounded transition-all ${bgClass}">
              <i style="color: ${iconClassStyle}; "  class="${iconClass} mr-3 transition-colors duration-300 ${iconClassExtra}"></i>${text}
            </a>
          </li>`;
          }


          let menuHTML = `<ul>
          `;

          if (setor_nome.toLowerCase() !== 'contratante') {
            menuHTML += `
            ${getMenuItem('../home', 'fas fa-home', 'Início')}

            `;
          }

          if (['gestão', 'tecnologia'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
              ${getMenuItem('../empresas', 'fas fa-diagram-project', 'Matriz e Filiais')}
              ${getMenuItem('../contratos', 'fas fa-diagram-project', 'Contratos')}
              ${getMenuItem('../licitacoes', 'fas fa-diagram-project', 'Licitações')}

            `;
          }

          if (setor_nome.toLowerCase() === 'contratante') {
            menuHTML += `
              ${getMenuItem('../os', 'fas fa-clipboard-list', 'Ordens de Serviço')}
            `;
          }

          if (['operacional'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
              ${getMenuItem('../contratos', 'fas fa-diagram-project', 'Contratos')}
            `;
          }

          if (['projetos', 'gestão', 'tecnologia', 'operacional'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
              ${getMenuItem('../Obras', 'fa-solid fa-trowel-bricks', 'Obras')}
              ${getMenuItem('../os', 'fas fa-clipboard-list', 'Ordens de Serviço')}
              ${getMenuItem('../recursos', 'fas fa-shopping-cart', 'Solicitações')}
              ${getMenuItem('../fornecedores', 'fas fa-boxes-stacked', 'Fornecedores')}
              ${getMenuItem('../cotacoes', 'fas fa-shopping-cart', 'Cotações')}


            `;
          }

          if (['gestão', 'tecnologia'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
          ${getMenuWithDropdown('fas fa-money-check-alt', 'Centro de Custo', [
            { href: '../transacoes/balance.php', label: 'Resumo', icon: 'fas fa-clipboard-list' },
            { href: '../transacoes', label: 'Entradas', icon: 'fas fa-arrow-circle-down' },
            { href: '../transacoes/saidas.php', label: 'Saidas', icon: 'fas fa-arrow-circle-up' },
          ])}
          ${getMenuItem('../profissionais', 'fas fa-clipboard-list', 'Profissionais')}

        `;
          }

          if (['compras'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
          ${getMenuItem('../recursos', 'fas fa-shopping-cart', 'Solicitações')}
          ${getMenuItem('../cotacoes', 'fas fa-shopping-cart', 'Cotações')}
          ${getMenuItem('../fornecedores', 'fas fa-boxes-stacked', 'Fornecedores')}

        `;
          }


          if (['th'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
          ${getMenuItem('../profissionais', 'fas fa-clipboard-list', 'Profissionais')}

        `;
          }

          if (['licitação'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
             ${getMenuItem('../contratos', 'fas fa-diagram-project', 'Contratos')}
             ${getMenuItem('../licitacoes', 'fas fa-diagram-project', 'Licitações')}
             ${getMenuItem('../Obras', 'fa-solid fa-trowel-bricks', 'Obras')}
             ${getMenuItem('../notas', 'fas fa-arrow-circle-down', 'Notas')}
        `;
          }

          if (['financeiro'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
            ${getMenuItem('../recursos', 'fas fa-shopping-cart', 'Solicitações')}
            ${getMenuItem('../cotacoes', 'fas fa-shopping-cart', 'Cotações')}
            ${getMenuItem('../fornecedores', 'fas fa-boxes-stacked', 'Fornecedores')}
            ${getMenuWithDropdown('fas fa-money-check-alt', 'Centro de Custo', [
              { href: '../transacoes/balance.php', label: 'Resumo', icon: 'fas fa-clipboard-list' },
              { href: '../transacoes', label: 'Entradas', icon: 'fas fa-arrow-circle-down' },
              { href: '../transacoes/saidas.php', label: 'Saidas', icon: 'fas fa-arrow-circle-up' },
            ])}
        `;
          }

          menuHTML += `</ul>`;
          document.getElementById('menuContainer').innerHTML = menuHTML;
        }

        // Campo de seleção ou input fixo
        let setorSelectHTML = '';
        if (['gestão', 'tecnologia'].includes(usuario.setor_nome.toLowerCase())) {
          setorSelectHTML += `<select name="setor" id="setor" class=" block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">`;
          const setorSalvo = localStorage.getItem('setor_override');

          setores.forEach(setor => {
            const nomeSetor = setor.nome;
            const isSelected = setorSalvo ?
              nomeSetor.toLowerCase() === setorSalvo.toLowerCase() :
              setor.id === usuario.setor_id;

            setorSelectHTML += `<option value="${nomeSetor}" ${isSelected ? 'selected' : ''}>${nomeSetor}</option>`;
          });
          setorSelectHTML += '</select>';
        } else {
          setorSelectHTML += `<input type="text" id="setor" name="setor" value="${usuario.setor_nome}" readonly class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">`;
        }

        menuContent.innerHTML += setorSelectHTML;
        menuContent.innerHTML += `<div id="menuContainer"></div>`;

        let setorParaRenderizar;

        if (['gestão', 'tecnologia'].includes(usuario.setor_nome.toLowerCase())) {
          const setorSalvo = localStorage.getItem('setor_override');
          setorParaRenderizar = setorSalvo || usuario.setor_nome;
        } else {
          setorParaRenderizar = usuario.setor_nome;
          localStorage.removeItem('setor_override'); // limpar override se não permitido
        }

        renderMenu(setorParaRenderizar);



        const setorDropdown = document.getElementById('setor');
        if (setorDropdown.tagName === 'SELECT') {
          setorDropdown.addEventListener('change', (e) => {
            const setorSelecionado = e.target.value;
            localStorage.setItem('setor_override', setorSelecionado);
            renderMenu(setorSelecionado);
          });
        }

        document.getElementById('userEmail').innerText = usuario.email;
        document.getElementById('userCompany').innerText = usuario.empresa_nome;
      }
    })
    .catch(error => console.error('Erro:', error));
</script>

<script>
  function isMobileView() {
    return window.innerWidth <= 768;
  }

  function applyMenuStyles({
    display,
    width,
    bodyMarginLeft,
    toggleButtonLeft
  }) {
    const sideMenu = document.getElementById('sideMenu');
    const toggleButton = document.getElementById('toggleButton');
    const body = document.body;

    sideMenu.style.display = display;
    sideMenu.style.width = width;
    body.style.marginLeft = bodyMarginLeft;
    toggleButton.style.left = toggleButtonLeft;
  }

  function toggleMenu() {
    const sideMenu = document.getElementById('sideMenu');
    const isHidden = sideMenu.style.display === 'none';
    const mobile = isMobileView();

    if (isHidden) {
      if (mobile) {
        applyMenuStyles({
          display: 'block',
          width: '80%',
          bodyMarginLeft: '0',
          toggleButtonLeft: '80%'
        });
      } else {
        applyMenuStyles({
          display: 'block',
          width: '16.5%',
          bodyMarginLeft: '15.5%',
          toggleButtonLeft: '16.5%'
        });
      }
      localStorage.setItem('menuOpen', 'true');
    } else {
      applyMenuStyles({
        display: 'none',
        width: '',
        bodyMarginLeft: '0',
        toggleButtonLeft: '0'
      });
      localStorage.setItem('menuOpen', 'false');
    }
  }

  window.onload = function() {
    const savedState = localStorage.getItem('menuOpen');
    const mobile = isMobileView();

    if (savedState === null) {
      // Default state
      if (mobile) {
        applyMenuStyles({
          display: 'none',
          width: '',
          bodyMarginLeft: '0',
          toggleButtonLeft: '0'
        });
      } else {
        applyMenuStyles({
          display: 'block',
          width: '16.5%',
          bodyMarginLeft: '15.5%',
          toggleButtonLeft: '16.5%'
        });
      }
    } else if (savedState === 'true') {
      if (mobile) {
        applyMenuStyles({
          display: 'block',
          width: '80%',
          bodyMarginLeft: '0',
          toggleButtonLeft: '80%'
        });
      } else {
        applyMenuStyles({
          display: 'block',
          width: '16.5%',
          bodyMarginLeft: '15.5%',
          toggleButtonLeft: '16.5%'
        });
      }
    } else {
      applyMenuStyles({
        display: 'none',
        width: '',
        bodyMarginLeft: '0',
        toggleButtonLeft: '0'
      });
    }
  };
</script>