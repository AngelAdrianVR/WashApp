<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, Head } from '@inertiajs/vue3';

// --- Props ---
const props = defineProps({
    title: String,
});

// --- SEO Metadata ---
const siteName = "WashApp";
const pageTitle = props.title ? `${props.title} | ${siteName}` : siteName;
const description = "El cuidado premium que tu auto merece. Servicio de lavado de autos a domicilio, ecológico y de alta calidad en Zapopan, Jalisco.";

// --- Navigation Links ---
const navigation = ref([
    { name: 'Inicio', routeName: 'landing.index' },
    { name: 'Servicios', routeName: 'landing.servicios' },
    { name: 'Agendar', routeName: 'landing.agendar' },
    { name: 'Promociones', routeName: 'landing.promociones' },
]);

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
  <Head :title="pageTitle">
      <meta name="description" :content="description">
      <meta name="keywords" content="lavado de autos, detallado automotriz, lavado a domicilio, car wash, zapopan, guadalajara, ecológico">
      <meta name="author" content="WashApp">
      
      <!-- Open Graph / Facebook -->
      <meta property="og:type" content="website">
      <meta property="og:title" :content="pageTitle">
      <meta property="og:description" :content="description">
      <!-- <meta property="og:image" content="URL_A_TU_IMAGEN_DE_PREVIEW"> -->

      <!-- Twitter -->
      <meta property="twitter:card" content="summary_large_image">
      <meta property="twitter:title" :content="pageTitle">
      <meta property="twitter:description" :content="description">
      <!-- <meta property="twitter:image" content="URL_A_TU_IMAGEN_DE_PREVIEW"> -->
  </Head>

  <div class="bg-slate-50 text-gray-800 font-sans">
    <!-- =========== Header / Navigation STARTS =========== -->
    <header 
      id="navbar" 
      class="fixed top-0 left-0 w-full z-50 transition-all duration-300"
      :class="{ 'bg-slate-900/80 backdrop-blur-sm shadow-lg': isScrolled, 'bg-slate-900': !isScrolled }"
    >
      <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
        <!-- Logo -->
        <Link :href="route('landing.index')" class="flex items-center space-x-2 text-2xl font-bold text-white">
          <!-- Asegúrate que la ruta a tu logo sea correcta en producción -->
          <img src="/images/isologo.png" alt="WashApp Logo" class="h-12 w-auto">
          <p>Wash<span class="text-cyan-400">App</span></p>
        </Link>

        <!-- Desktop Menu -->
        <div class="hidden lg:flex items-center space-x-8">
          <Link
            v-for="item in navigation"
            :key="item.name"
            :href="route(item.routeName)"
            class="nav-link text-white"
            :class="{ 'active-nav-link': route().current(item.routeName) }"
          >
            {{ item.name }}
          </Link>
        </div>
        
        <div class="hidden lg:flex items-center space-x-4">
          <Link :href="route('login')" class="text-white hover:text-sky-400 transition">Iniciar sesión</Link>
          <Link :href="route('register')" class="bg-sky-500 hover:bg-sky-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Regístrate</Link>
        </div>

        <!-- Mobile Menu Button -->
        <button @click="toggleMobileMenu" class="lg:hidden text-white focus:outline-none">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
          </svg>
        </button>
      </nav>
      
      <!-- Mobile Menu -->
      <div v-show="isMobileMenuOpen" class="lg:hidden bg-slate-800/95 backdrop-blur-sm">
        <Link
          v-for="item in navigation"
          :key="item.name"
          :href="route(item.routeName)"
          @click="toggleMobileMenu" 
          class="block py-3 px-6 text-center text-white hover:bg-sky-500/50"
          :class="{ 'bg-sky-500/70 font-semibold': route().current(item.routeName) }"
        >
          {{ item.name }}
        </Link>
        <div class="border-t border-slate-700 my-2"></div>
        <Link :href="route('login')" class="block py-3 px-6 text-center text-white hover:bg-sky-500/50">Iniciar sesión</Link>
        <Link :href="route('register')" class="block m-4 bg-sky-500 hover:bg-sky-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 text-center">Regístrate</Link>
      </div>
    </header>
    <!-- =========== Header / Navigation ENDS =========== -->

    <!-- El slot principal no necesita el padding-top porque las vistas ya lo tienen -->
    <main>
        <slot />
    </main>

    <!-- =========== Footer STARTS =========== -->
    <footer class="bg-slate-900">
        <div class="container mx-auto px-6 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center text-center md:text-left">
                <div class="flex items-center space-x-2 text-xl font-bold text-white mb-4 md:mb-0">
                    <img src="/images/isologo.png" alt="WashApp Footer Logo" class="h-7 w-auto">
                    <p>Wash<span class="text-cyan-400">App</span></p>
                </div>
                <p class="text-slate-400 text-sm">
                    &copy; {{ new Date().getFullYear() }} WashApp. Todos los derechos reservados.
                </p>
                 <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="#" aria-label="Facebook" class="text-slate-400 hover:text-sky-400 transition"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg></a>
                    <a href="#" aria-label="Twitter" class="text-slate-400 hover:text-sky-400 transition"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.71v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" /></svg></a>
                    <a href="#" aria-label="Instagram" class="text-slate-400 hover:text-sky-400 transition"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.024.06 1.378.06 3.808s-.012 2.784-.06 3.808c-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.024.048-1.378.06-3.808.06s-2.784-.013-3.808-.06c-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.048-1.024-.06-1.378-.06-3.808s.012-2.784.06-3.808c.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 016.345 4.52c.636-.247 1.363-.416 2.427-.465C9.793 4.013 10.147 4 12.573 4h-.01a12.04 12.04 0 01-.248 0zm0 1.442c-2.376 0-2.71.01-3.654.052-1.002.046-1.57.2-2.02.385a3.48 3.48 0 00-1.24 1.24c-.184.45-.338 1.018-.385 2.02-.043.944-.052 1.278-.052 3.654s.01 2.71.052 3.654c.046 1.002.2 1.57.385 2.02a3.48 3.48 0 001.24 1.24c.45.184 1.018.338 2.02.385.944.043 1.278.052 3.654.052s2.71-.01 3.654-.052c1.002-.046 1.57-.2 2.02-.385a3.48 3.48 0 001.24-1.24c.184-.45.338-1.018.385-2.02.043-.944.052-1.278.052-3.654s-.01-2.71-.052-3.654c-.046-1.002-.2-1.57-.385-2.02a3.48 3.48 0 00-1.24-1.24c-.45-.184-1.018-.338-2.02-.385C15.283 3.452 14.95 3.442 12.573 3.442h.001zm0 2.923a4.634 4.634 0 100 9.268 4.634 4.634 0 000-9.268zm0 7.822a3.188 3.188 0 110-6.376 3.188 3.188 0 010 6.376z" clip-rule="evenodd" /></svg></a>
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
    padding-bottom: 8px; /* Espacio para la línea */
}

.nav-link:hover {
    color: #38bdf8; /* text-sky-400 */
}

.nav-link::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: 0;
    left: 50%;
    background-color: #38bdf8; /* text-sky-400 */
    transition: all 0.3s ease-out;
    transform: translateX(-50%);
}

.nav-link:hover::after,
.active-nav-link::after {
    width: 100%;
}

.active-nav-link {
  color: #0ea5e9; /* text-sky-500 */
  font-weight: 600;
}
</style>
