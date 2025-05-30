<style>
  #sideMenu i {
    color: #171717;
    transition: color 0.3s;
  }

  #sideMenu a:hover i,
  #sideMenu .active i {
    color: #2B3A4B;
  }

  #sideMenu {
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 30;
    width: 16.5%;
    min-width: 200px;
    max-width: 280px;
    font-size: 0.8rem;
    /* base responsivo */
  }


  #sideMenu li {
    margin-top: 5px;
  }

  @media (min-width: 768px) {
    .side-menu {
      transform: translateX(0);
      left: 0;
      position: fixed;
      height: 100vh;
      backdrop-filter: none;
    }


  }

  #sideMenu a:hover i {
    color: #2B3A4B !important;
  }




  body {
    margin-left: 16%;
  }


  @media (max-width: 768px) {
    .side-menu {
      transform: translateX(0);
      left: 0;
      position: fixed;
      height: 100vh;
      backdrop-filter: none;
      display: none;
    }

    body {
      margin-left: 0%;
    }

  }
</style>

<style>
  li.group:hover>ul {
    display: block;
  }
</style>


<div id="sideMenu" class="  side-menu fixed top-0 left-[-100%] shadow-xl w-64 min-h-screen bg-[#FFFFFF] backdrop-blur-lg p-4 space-y-2 transition-all duration-300 ease-in-out transform rounded-lg z-30">
  <div class="flex justify-between items-center ">
    <div class="flex items-center ">
      <img class="w-12" src="../assets/logo/il_fullxfull.2974258879_pxm3.webp" alt="">
      <h2 class="text-[12px] text-[#2B3A4B] font-bold">Zion Managment</h2>
    </div>

  </div>

  <div id="menuContent"></div>

  <div class="absolute bottom-0 text-sm text-gray-700 leading-4 mb-2">

    <p class="text-[14px]" id="userEmail"></p>
    <p class="text-[12px]" id="userCompany"></p>

    <button id="logoutButton" class="flex items-center py-4 mt-2  rounded transition-all   text-gray-800">
      <i class="fas fa-sign-out-alt mr-3"></i> Sair
    </button>
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
            ${getMenuItem('../home', 'fas fa-home', 'Início')}
          `;

          if (['gestão', 'tecnologia'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
              ${getMenuItem('../empresas', 'fas fa-diagram-project', 'Matriz e Filiais')}
              ${getMenuItem('../contratos', 'fas fa-diagram-project', 'Contratos')}

            `;
          }



          if (['operacional'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
              ${getMenuItem('../contratos', 'fas fa-diagram-project', 'Contratos')}
            `;
          }

          if (['projetos', 'gestão', 'tecnologia', 'operacional'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
              ${getMenuItem('../projetos', 'fa-solid fa-ruler-combined', 'Projetos')}
              ${getMenuItem('../Obras', 'fa-solid fa-trowel-bricks', 'Obras')}
              ${getMenuItem('../os', 'fas fa-clipboard-list', 'Ordens de Serviço')}
            `;
          }




          if (['gestão', 'tecnologia'].includes(setor_nome.toLowerCase())) {
            menuHTML += `
          ${getMenuItem('../recursos', 'fas fa-shopping-cart', 'Fluxo de Compras')}
          ${getMenuItem('../cotacoes', 'fas fa-shopping-cart', 'Cotações')}
          ${getMenuWithDropdown('fas fa-money-check-alt', 'Centro de Custo', [
            { href: '../transacoes', label: 'Entradas', icon: 'fas fa-arrow-circle-down' },
            { href: '../transacoes/saidas.php', label: 'Saídas', icon: 'fas fa-arrow-circle-up' },
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

        const setorSalvo = localStorage.getItem('setor_override');
        const setorParaRenderizar = setorSalvo || usuario.setor_nome;
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