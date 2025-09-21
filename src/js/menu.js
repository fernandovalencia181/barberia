// JavaScript para alternar la visibilidad del menú

// Menú usuario
const menuIconUser = document.getElementById("menu-icon-user");
const menuUser = document.getElementById("menu-user");

if(menuIconUser && menuUser){
    menuIconUser.onclick = function(event){
        event.stopPropagation();
        menuUser.classList.toggle("show-menu");
    }
    document.addEventListener("click", function(event){
        if(!menuUser.contains(event.target) && !menuIconUser.contains(event.target)){
            menuUser.classList.remove("show-menu");
        }
    });
}

// Menú admin
const menuIconAdmin = document.getElementById("menu-icon-admin");
const menuAdmin = document.getElementById("menu-admin");

if(menuIconAdmin && menuAdmin){
    menuIconAdmin.onclick = function(event){
        event.stopPropagation();
        menuAdmin.classList.toggle("show-menu");
    }
    document.addEventListener("click", function(event){
        if(!menuAdmin.contains(event.target) && !menuIconAdmin.contains(event.target)){
            menuAdmin.classList.remove("show-menu");
        }
    });
}

// Resaltar el enlace activo para ambos menús
document.addEventListener("DOMContentLoaded", () => {
    const menus = document.querySelectorAll(".menu");
    const pathActual = window.location.pathname;

    menus.forEach(menu => {
        const enlaces = menu.querySelectorAll("a");
        enlaces.forEach(enlace => {
            if (enlace.getAttribute("href") === pathActual) {
                enlace.classList.add("activo"); // Clase para resaltar
            }
        });
    });
});
