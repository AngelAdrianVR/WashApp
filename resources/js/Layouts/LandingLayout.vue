<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, Head } from '@inertiajs/vue3';

// --- Props ---
const props = defineProps({
    title: String,
});

// Ref para controlar la visibilidad del menú móvil
const isMobileMenuOpen = ref(false);

// Ref para controlar el estado del navbar al hacer scroll
const isScrolled = ref(false);

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

// Función para actualizar el estado del navbar
const handleScroll = () => {
  isScrolled.value = window.scrollY > 10;
};

// Agregar y remover el event listener del scroll
onMounted(() => {
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
<Head :title="title" />
  <div class="bg-slate-900 text-gray-200 font-sans">
    <!-- =========== Header / Navigation STARTS =========== -->
    <header 
      id="navbar" 
      class="fixed top-0 left-0 w-full z-50 transition-all duration-300"
      :class="{ 'bg-slate-900/80 backdrop-blur-sm shadow-lg': isScrolled, 'bg-transparent': !isScrolled }"
    >
      <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
        <!-- Logo -->
        <a href="#" class="flex items-center space-x-2 text-2xl font-bold text-white">
          <img src="@/../../public/images/isologo.png" alt="washApp">
          <p>Wash<span class="text-cyan-500">App</span></p>
        </a>

        <!-- Desktop Menu -->
        <div class="hidden lg:flex items-center space-x-8">
          <a href="#inicio" class="nav-link">Inicio</a>
          <a href="#servicios" class="nav-link">Servicios</a>
          <a href="#agendar" class="nav-link">Agendar</a>
        </div>
        
        <div class="hidden lg:flex items-center space-x-4">
          <a :href="route('login')" class="text-white hover:text-sky-400 transition">Iniciar sesión</a>
          <a :href="route('register')" class="bg-sky-500 hover:bg-sky-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Regístrate</a>
        </div>

        <!-- Mobile Menu Button -->
        <button @click="toggleMobileMenu" class="lg:hidden text-white focus:outline-none">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
          </svg>
        </button>
      </nav>
      
      <!-- Mobile Menu -->
      <div v-show="isMobileMenuOpen" class="lg:hidden bg-slate-800/80 backdrop-blur-sm">
        <a href="#inicio" @click="toggleMobileMenu" class="block py-3 px-6 text-center text-white hover:bg-sky-500/50">Inicio</a>
        <a href="#servicios" @click="toggleMobileMenu" class="block py-3 px-6 text-center text-white hover:bg-sky-500/50">Servicios</a>
        <a href="#agendar" @click="toggleMobileMenu" class="block py-3 px-6 text-center text-white hover:bg-sky-500/50">Agendar</a>
        <div class="border-t border-slate-700 my-2"></div>
        <a href="#" class="block py-3 px-6 text-center text-white hover:bg-sky-500/50">Iniciar sesión</a>
        <a href="#" class="block m-4 bg-sky-500 hover:bg-sky-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 text-center">Regístrate</a>
      </div>
    </header>
    <!-- =========== Header / Navigation ENDS =========== -->

    <main class="pt-20">
        <slot />
    </main>

    <!-- =========== Footer STARTS =========== -->
    <footer class="bg-slate-900/50 border-t border-slate-800">
        <div class="container mx-auto px-6 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-2 text-xl font-bold text-white mb-4 md:mb-0">
                    <img src="@/../../public/images/isologo.png" alt="washApp">
                    <p>Wash<span class="text-cyan-500">App</span></p>
                </div>
                <p class="text-slate-400 text-sm">
                    &copy; {{ new Date().getFullYear() }} WashApp. Todos los derechos reservados.
                </p>
                 <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="#" class="text-slate-400 hover:text-sky-400 transition"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg></a>
                    <a href="#" class="text-slate-400 hover:text-sky-400 transition"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.71v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" /></svg></a>
                    <a href="#" class="text-slate-400 hover:text-sky-400 transition"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.207 14.207a1 1 0 01-1.414 0L12 13.414l-2.793 2.793a1 1 0 01-1.414-1.414L10.586 12 7.793 9.207a1 1 0 011.414-1.414L12 10.586l2.793-2.793a1 1 0 011.414 1.414L13.414 12l2.793 2.793a1 1 0 010 1.414z" clip-rule="evenodd" /></svg></a>
                 </div>
            </div>
        </div>
    </footer>
    <!-- =========== Footer ENDS =========== -->

  </div>
</template>

<style scoped>
.nav-link {
    position: relative;
    transition: color 0.3s ease;
}

.nav-link:hover {
    color: #38bdf8; /* text-sky-400 */
}

.nav-link::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: -6px;
    left: 50%;
    background-color: #38bdf8;
    transition: all 0.3s ease-out;
    transform: translateX(-50%);
}

.nav-link:hover::after {
    width: 100%;
}
</style>
