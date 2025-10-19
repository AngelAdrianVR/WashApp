<script setup>
import { ref, onMounted } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Link } from '@inertiajs/vue3';

// Se ejecuta después de que el componente se ha montado en el DOM.
onMounted(() => {
  // --- Lógica para crear el fondo de burbujas animadas ---
  const bubbleContainer = document.querySelector('.bubbles-container');
  if (bubbleContainer) {
    const numberOfBubbles = 25; // Cantidad de burbujas
    for (let i = 0; i < numberOfBubbles; i++) {
      const bubble = document.createElement('span');
      // Usamos una clase genérica para la animación y una específica para el estilo
      bubble.className = 'bubble'; 
      
      // Estilos aleatorios para cada burbuja
      const size = Math.random() * 80 + 10 + 'px'; // Tamaño entre 10px y 60px
      const animationDuration = Math.random() * 9 + 7 + 's'; // Duración entre 8s y 18s
      const animationDelay = Math.random() * 2 + 's'; // Retraso entre 0s y 2s

      bubble.style.width = size;
      bubble.style.height = size;
      bubble.style.left = Math.random() * 100 + '%'; // Posición horizontal aleatoria
      bubble.style.animationDuration = animationDuration;
      bubble.style.animationDelay = animationDelay;
      
      bubbleContainer.appendChild(bubble);
    }
  }

  // --- Lógica para las transiciones al hacer scroll ---
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('fade-in-visible');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.1
  });
  const elementsToAnimate = document.querySelectorAll('[data-scroll-fade]');
  elementsToAnimate.forEach(el => observer.observe(el));
  
  // --- Lógica para el slider de Antes y Después ---
  const slider = document.getElementById('image-slider');
  const beforeImage = document.getElementById('before-image');
  const sliderLine = document.querySelector('.slider-line');
  const sliderHandle = document.querySelector('.slider-handle');

  if (slider && beforeImage && sliderLine && sliderHandle) {
      const updateSlider = (value) => {
          beforeImage.style.clipPath = `polygon(0 0, ${value}% 0, ${value}% 100%, 0 100%)`;
          sliderLine.style.left = `${value}%`;
          sliderHandle.style.left = `${value}%`;
      };
      // Valor inicial
      updateSlider(slider.value);
      // Evento al mover el slider
      slider.addEventListener('input', (e) => {
          updateSlider(e.target.value);
      });
  }
});


// Lista de servicios actualizada y más detallada
const services = ref([
  {
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" /></svg>`,
    title: 'Lavado Ecológico Premium',
    description: 'Utilizamos una técnica avanzada con polímeros y cera de carnauba que encapsula la suciedad, levantándola sin rayar. Ahorramos más de 150 litros de agua por lavado.'
  },
  {
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>`,
    title: 'Lavado a Detalle (Showroom)',
    description: 'Limpieza exhaustiva con agua a presión controlada, shampoo con pH neutro y guantes de microfibra. Ideal para una limpieza profunda y segura para la pintura.'
  },
  {
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>`,
    title: 'Pulido y Encerado Cerámico',
    description: 'Eliminamos micro-rayones y devolvemos el brillo original. Aplicamos una capa de cera con polímeros cerámicos para una protección duradera contra el sol, lluvia y polvo.'
  },
  {
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>`,
    title: 'Limpieza Profunda de Interiores',
    description: 'Aspirado completo, limpieza de tapicería (tela o piel), acondicionamiento de plásticos y eliminación de olores. Dejamos tu cabina fresca y como nueva.'
  }
]);

const whyChooseUs = ref([
    {
        icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
        title: 'Ahorra Tiempo Valioso',
        description: 'No más filas ni esperas. Nos adaptamos a tu horario y vamos a donde estés: tu casa, tu oficina o donde nos necesites.'
    },
    {
        icon: `<i class="fa-solid text-3xl fa-tree"></i>`,
        title: 'Productos Eco-Amigables',
        description: 'Usamos solo productos biodegradables de alta gama que son seguros para tu auto, para ti y para el medio ambiente.'
    },
    {
        icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
        title: 'Calidad Profesional Garantizada',
        description: 'Nuestro equipo está capacitado para ofrecer un acabado impecable. Tu satisfacción es nuestra máxima prioridad.'
    }
]);

// Beneficios para miembros
const memberBenefits = ref([
    {
        icon: `<i class="fa-solid fa-percent text-3xl"></i>`,
        title: 'Descuentos Exclusivos',
        description: 'Accede a precios especiales y ofertas que no encontrarás en ningún otro lado, solo por ser parte de la comunidad WashApp.'
    },
    {
        icon: `<i class="fa-solid fa-circle-dollar-to-slot text-3xl"></i>`,
        title: 'Servicios Gratis',
        description: 'Acumula puntos con cada lavado y canjéalos por servicios gratuitos, como encerado express o limpieza de tapetes.'
    },
    {
        icon: `<i class="fa-solid fa-gift text-3xl"></i>`,
        title: 'Regalo de Cumpleaños',
        description: 'Celebramos contigo. Recibe un lavado básico completamente gratis durante el mes de tu cumpleaños.'
    }
]);

// (NOVEDAD) - Lista de testimonios
const testimonials = ref([
    {
        quote: "El servicio fue increíble. Llegaron puntuales, fueron súper profesionales y mi auto quedó más limpio que cuando lo saqué de la agencia. ¡Totalmente recomendado!",
        author: "Ana L., Zapopan",
        rating: 5
    },
    {
        quote: "Contraté el lavado a detalle y el resultado superó mis expectativas. Cuidan cada rincón del coche. El personal es muy amable y el servicio es de primera.",
        author: "Carlos G., Guadalajara",
        rating: 5
    },
    {
        quote: "Muy buen servicio en general. El auto quedó muy limpio por dentro y por fuera. Solo tardaron unos 15 minutos más de lo estimado, pero valió la pena la espera.",
        author: "Laura M., Tlaquepaque",
        rating: 4
    },
    {
        quote: "La comodidad de que vengan a tu casa no tiene precio. El lavado ecológico es una maravilla y el coche brilla como nuevo. Excelente atención al cliente.",
        author: "Javier R., Zapopan",
        rating: 5
    }
]);

// (NOVEDAD) - Planes para Agencias
const agencyPlans = ref([
    {
        name: 'Flotilla Bronce',
        price: '$1,500',
        period: '/mes',
        carCount: '5-10 Autos',
        features: [
            'Lavado Ecológico Exterior',
            'Aspirado Básico de Interiores',
            'Limpieza de Cristales',
            'Servicio Semanal'
        ]
    },
    {
        name: 'Flotilla Plata',
        price: '$2,800',
        period: '/mes',
        carCount: '11-20 Autos',
        features: [
            'Todo en Bronce',
            'Lavado a Detalle Exterior',
            'Acondicionamiento de Plásticos',
            'Prioridad de Agenda'
        ],
        popular: true
    },
    {
        name: 'Flotilla Oro',
        price: 'Personalizado',
        period: '',
        carCount: '20+ Autos',
        features: [
            'Todo en Plata',
            'Limpieza Profunda de Interiores',
            'Encerado Cerámico Básico',
            'Ejecutivo de Cuenta Dedicado'
        ]
    }
]);
</script>

<template>
    <LandingLayout :title="'Inicio'">
        <main class="bg-slate-50 text-gray-800">
            <!-- =========== Hero Section STARTS =========== -->
            <section id="inicio" class="relative min-h-screen flex items-center justify-center pt-20 overflow-hidden">
                <!-- Contenedor de burbujas -->
                <div class="bubbles-container" style="z-index: 999;" aria-hidden="true"></div>

                 <!-- Fondo de olas -->
                <div class="absolute inset-0 z-0 opacity-50">
                    <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-white to-transparent"></div>
                    <svg class="w-full h-auto absolute bottom-0 left-0" viewBox="0 0 1440 320" xmlns="http://www.w3.org/2000/svg"><path fill="#e0f2fe" fill-opacity="1" d="M0,192L48,176C96,160,192,128,288,133.3C384,139,480,181,576,202.7C672,224,768,224,864,197.3C960,171,1056,117,1152,106.7C1248,96,1344,128,1392,144L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
                    <svg class="w-full h-auto absolute bottom-0 left-0" viewBox="0 0 1440 320" xmlns="http://www.w3.org/2000/svg"><path fill="#bae6fd" fill-opacity="0.5" d="M0,256L48,240C96,224,192,192,288,176C384,160,480,160,576,181.3C672,203,768,245,864,250.7C960,256,1056,224,1152,197.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
                </div>
                <div class="container mx-auto px-6 text-center relative z-10">
                    <h1 data-scroll-fade class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-slate-800 leading-tight mb-4">
                        El Cuidado Premium que tu Auto
                        <span class="block bg-clip-text text-transparent bg-gradient-to-r from-blue-500 to-cyan-400">
                            Merece y Agradece.
                        </span>
                    </h1>
                    <p data-scroll-fade :style="{'transition-delay': '150ms'}" class="text-lg md:text-xl text-slate-600 max-w-3xl mx-auto mb-8">
                        Experimenta la máxima conveniencia con nuestro servicio de lavado de autos a domicilio. Calidad, ecología y el mejor trato, todo en la puerta de tu casa.
                    </p>
                    <a data-scroll-fade :style="{'transition-delay': '300ms'}" href="#agendar" class="cta-button bg-gradient-to-r from-blue-500 to-cyan-400 hover:from-blue-600 hover:to-cyan-500 text-white font-bold text-lg py-4 px-8 rounded-full shadow-lg">
                        Agendar Mi Lavado Ahora
                    </a>
                </div>
            </section>
            <!-- =========== Hero Section ENDS =========== -->

            <!-- =========== Services Section STARTS =========== -->
            <section id="servicios" class="py-20 lg:py-32 bg-sky-50">
                <div class="container mx-auto px-6">
                    <div class="text-center mb-16">
                         <h2 data-scroll-fade class="text-3xl md:text-4xl font-bold text-slate-800">Un Servicio para Cada Necesidad</h2>
                        <p data-scroll-fade :style="{'transition-delay': '150ms'}" class="text-slate-600 mt-2 max-w-2xl mx-auto">
                           Desde una limpieza ecológica rápida hasta un detallado profundo. Tenemos el plan perfecto para que tu auto luzca impecable.
                        </p>
                        <!-- (NOVEDAD) - Botón agregado -->
                        <div class="mt-8" data-scroll-fade :style="{'transition-delay': '300ms'}">
                            <a :href="route('landing.servicios')" class="bg-blue-500 text-white font-bold text-lg py-3 px-8 rounded-full hover:bg-blue-600 transition-colors duration-300 shadow-md hover:shadow-lg">
                                Ver Servicios Disponibles
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div v-for="(service, index) in services" :key="index" 
                             data-scroll-fade 
                             :style="{ 'transition-delay': `${index * 100}ms` }"
                             class="glass-card bg-white rounded-xl p-6 flex flex-col items-center text-center transform hover:-translate-y-2 hover:shadow-2xl transition-all duration-300">
                            <div class="text-blue-500 mb-4" v-html="service.icon"></div>
                            <h3 class="text-xl font-semibold text-slate-800 mb-2">{{ service.title }}</h3>
                            <p class="text-slate-600 text-sm">{{ service.description }}</p>
                        </div>
                    </div>
                </div>
            </section>
            <!-- =========== Services Section ENDS =========== -->
            
            <!-- =========== Why Choose Us Section STARTS =========== -->
            <section id="por-que-elegirnos" class="py-20 lg:py-32">
                <div class="container mx-auto px-6">
                    <div class="text-center mb-16">
                        <h2 data-scroll-fade class="text-3xl md:text-4xl font-bold text-slate-800">La Diferencia está en los Detalles</h2>
                        <p data-scroll-fade :style="{'transition-delay': '150ms'}" class="text-slate-600 mt-2 max-w-2xl mx-auto">
                            No solo lavamos autos, los cuidamos. Descubre por qué nuestros clientes nos prefieren.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-center">
                        <div v-for="(feature, index) in whyChooseUs" :key="index" 
                             data-scroll-fade 
                             :style="{ 'transition-delay': `${index * 150}ms` }"
                             class="flex flex-col items-center">
                            <div class="flex items-center justify-center h-16 w-16 rounded-full bg-cyan-100 text-cyan-500 mb-4" v-html="feature.icon"></div>
                            <h3 class="text-xl font-semibold text-slate-800 mb-2">{{ feature.title }}</h3>
                            <p class="text-slate-600">{{ feature.description }}</p>
                        </div>
                    </div>
                </div>
            </section>
            <!-- =========== Why Choose Us Section ENDS =========== -->

            <!-- =========== Testimonial Section STARTS (MODIFICADO) =========== -->
            <section id="testimonios" class="py-20 lg:py-32 bg-sky-50">
                <div class="container mx-auto px-6">
                    <div class="text-center mb-16">
                        <h2 data-scroll-fade class="text-3xl md:text-4xl font-bold text-slate-800">Lo que dicen nuestros clientes</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8 max-w-5xl mx-auto">
                        <div v-for="(testimonial, index) in testimonials" :key="index"
                             data-scroll-fade
                             :style="{ 'transition-delay': `${index * 100}ms` }"
                             class="glass-card bg-white rounded-xl p-8 shadow-lg flex flex-col justify-between text-center">
                            <div>
                                <p class="text-slate-600 text-lg italic mb-6">"{{ testimonial.quote }}"</p>
                            </div>
                            <div>
                                <p class="font-bold text-slate-800">- {{ testimonial.author }}</p>
                                <div class="flex justify-center mt-4 text-yellow-400">
                                    <!-- Estrellas llenas -->
                                    <svg v-for="i in testimonial.rating" :key="`filled-${i}`" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                    <!-- Estrellas vacías -->
                                    <svg v-for="i in (5 - testimonial.rating)" :key="`empty-${i}`" class="w-6 h-6 text-yellow-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1.049-3.292l-2.8-2.034c-.784-.57-1.838.197-1.539 1.118l1.07 3.292a1 1 0 00.364 1.118l2.8 2.034c.784.57 1.838-.197 1.539-1.118l-1.07-3.292a1 1 0 00-.364-1.118zM11.95 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" clip-rule="evenodd" /></svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- =========== Testimonial Section ENDS =========== -->

            <!-- =========== (NOVEDAD) Agency Pricing Section STARTS =========== -->
            <section id="agencias" class="py-20 lg:py-32">
                <div class="container mx-auto px-6">
                    <div class="text-center mb-16">
                        <h2 data-scroll-fade class="text-3xl md:text-4xl font-bold text-slate-800">Planes Especiales para Agencias y Flotillas</h2>
                        <p data-scroll-fade :style="{'transition-delay': '150ms'}" class="text-slate-600 mt-2 max-w-2xl mx-auto">
                            Soluciones a la medida para mantener tu flotilla de vehículos siempre impecable. Ahorra tiempo y dinero con nuestros paquetes.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto items-stretch">
                        <div v-for="(plan, index) in agencyPlans" :key="index"
                             data-scroll-fade
                             :style="{ 'transition-delay': `${index * 150}ms` }"
                             :class="[
                                'rounded-xl p-8 border flex flex-col relative',
                                plan.popular ? 'bg-slate-800 text-white border-blue-500 ring-2 ring-blue-500 transform md:scale-105' : 'bg-white text-slate-800 border-slate-200'
                             ]">
                            <div v-if="plan.popular" class="absolute top-0 -translate-y-1/2 left-1/2 -translate-x-1/2">
                                <span class="bg-blue-500 text-white text-xs font-semibold px-3 py-1 rounded-full uppercase">Más Popular</span>
                            </div>
                            <h3 class="text-2xl font-bold mb-1">{{ plan.name }}</h3>
                            <p :class="['mb-6', plan.popular ? 'text-slate-300' : 'text-slate-500']">{{ plan.carCount }}</p>
                            <div class="mb-6">
                                <span class="text-5xl font-extrabold">{{ plan.price }}</span>
                                <span v-if="plan.period" :class="['text-lg', plan.popular ? 'text-slate-400' : 'text-slate-500']">{{ plan.period }}</span>
                            </div>
                            <ul class="space-y-4 mb-8 flex-grow">
                                <li v-for="feature in plan.features" :key="feature" class="flex items-start">
                                    <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>
                            <a href="#agendar" 
                               :class="[
                                    'w-full text-center font-bold py-3 rounded-lg transition-colors duration-300 mt-auto',
                                    plan.popular ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-slate-100 text-slate-800 hover:bg-slate-200'
                               ]">
                                Contactar para Contratar
                            </a>
                        </div>
                    </div>
                </div>
            </section>
            <!-- =========== Agency Pricing Section ENDS =========== -->
            
            <!-- =========== (NOVEDAD) Gallery Section STARTS =========== -->
            <section id="galeria" class="py-20 lg:py-32 bg-slate-50">
                <div class="container mx-auto px-6">
                    <div class="text-center mb-16">
                        <h2 data-scroll-fade class="text-3xl md:text-4xl font-bold text-slate-800">Resultados que Hablan por Sí Mismos</h2>
                        <p data-scroll-fade :style="{'transition-delay': '150ms'}" class="text-slate-600 mt-2 max-w-2xl mx-auto">
                            Desliza para ver la transformación. Así es como devolvemos el brillo y la vida a cada vehículo que tratamos.
                        </p>
                    </div>

                    <div data-scroll-fade :style="{'transition-delay': '300ms'}" class="max-w-4xl mx-auto">
                        <div class="relative w-full aspect-video rounded-xl overflow-hidden shadow-2xl comparison-slider">
                            <!-- Imagen Después (fondo) -->
                            <img id="after-image" src="@/../../public/images/auto-limpio-1.png" alt="Coche limpio y brillante" class="absolute inset-0 w-full h-full object-cover select-none">
                            
                            <!-- Imagen Antes (frente, se recorta) -->
                            <div id="before-image" class="absolute inset-0 w-full h-full overflow-hidden select-none">
                                <img src="@/../../public/images/auto-sucio-1.png" alt="Coche sucio antes del lavado" class="h-full w-full object-cover">
                            </div>
                            
                            <!-- Slider -->
                            <input type="range" id="image-slider" min="0" max="100" value="50" class="absolute inset-0 w-full h-full cursor-pointer opacity-0">
                            
                            <!-- Barra divisoria -->
                            <div class="slider-line"></div>
                            <!-- Círculo del slider -->
                            <div class="slider-handle">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- =========== Gallery Section ENDS =========== -->

            <!-- =========== Member Benefits Section STARTS =========== -->
            <section id="beneficios" class="py-20 lg:py-32">
                <div class="container mx-auto px-6">
                    <div class="text-center mb-16">
                        <h2 data-scroll-fade class="text-3xl md:text-4xl font-bold text-slate-800">Beneficios Exclusivos al Registrarte</h2>
                        <p data-scroll-fade :style="{'transition-delay': '150ms'}" class="text-slate-600 mt-2 max-w-2xl mx-auto">
                            Crear una cuenta es gratis y te da acceso a un mundo de ventajas pensadas para ti y tu auto.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-center">
                        <div v-for="(benefit, index) in memberBenefits" :key="index" 
                             data-scroll-fade 
                             :style="{ 'transition-delay': `${index * 150}ms` }"
                             class="flex flex-col items-center">
                            <div class="flex items-center justify-center h-16 w-16 rounded-full bg-sky-100 text-sky-500 mb-4" v-html="benefit.icon"></div>
                            <h3 class="text-xl font-semibold text-slate-800 mb-2">{{ benefit.title }}</h3>
                            <p class="text-slate-600">{{ benefit.description }}</p>
                        </div>
                    </div>
                    <div class="text-center mt-16">
                        <Link :href="route('landing.promociones')" 
                              data-scroll-fade 
                              class="cta-button bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white font-bold text-lg py-4 px-8 rounded-full shadow-lg">
                            Ver Todas las Promociones
                        </Link>
                    </div>
                </div>
            </section>
            <!-- =========== Member Benefits Section ENDS =========== -->

            <!-- =========== CTA Section STARTS =========== -->
            <section id="agendar" class="py-20 lg:py-32 bg-slate-800 text-white">
                <div class="container mx-auto px-6 text-center">
                    <h2 data-scroll-fade class="text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight mb-4">
                        ¿Listo para un Auto Reluciente sin Esfuerzo?
                    </h2>
                    <p data-scroll-fade :style="{'transition-delay': '150ms'}" class="text-lg text-slate-300 max-w-3xl mx-auto mb-8">
                       Dale a tu auto el cuidado que se merece con la comodidad que tú necesitas. Agenda en menos de 2 minutos.
                    </p>
                    <Link :href="route('landing.agendar')" 
                          data-scroll-fade 
                          :style="{'transition-delay': '300ms'}"
                          class="cta-button bg-gradient-to-r from-blue-500 to-cyan-400 hover:from-blue-600 hover:to-cyan-500 text-white font-bold text-lg py-4 px-8 rounded-full shadow-lg">
                        Ver Planes y Agendar
                    </Link>
                </div>
            </section>
            <!-- =========== CTA Section ENDS =========== -->
        </main>
    </LandingLayout>
</template>

<style scoped>
/* --- Estilos para la animación de burbujas --- */
.bubbles-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 1; 
    pointer-events: none;
}

:deep(.bubble) {
    position: absolute;
    bottom: -100px;
    background-color: rgba(74, 138, 241, 0.301); 
    border-radius: 50%;
    animation-name: bubble-float;
    animation-timing-function: ease-in;
    animation-iteration-count: infinite;
}

@keyframes bubble-float {
    0% {
        transform: translateY(0);
        opacity: 0.9;
    }
    100% {
        transform: translateY(-120vh);
        opacity: 0;
    }
}


/* --- Estilos para la animación de entrada al hacer scroll --- */
[data-scroll-fade] {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}

.fade-in-visible {
  opacity: 1;
  transform: translateY(0);
}


/* --- Estilos existentes --- */
.glass-card {
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
}

.cta-button {
    transition: all 0.3s ease;
}

.cta-button:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 25px rgba(2, 132, 199, 0.2);
}

/* (NOVEDAD) --- Estilos para el slider de comparación de imágenes --- */
#before-image {
    clip-path: polygon(0 0, 50% 0, 50% 100%, 0 100%);
}

.slider-line {
    position: absolute;
    top: 0;
    left: 50%; /* Posición inicial */
    width: 4px;
    height: 100%;
    background: white;
    transform: translateX(-50%);
    pointer-events: none;
    box-shadow: 0 0 10px rgba(0,0,0,0.5);
    transition: left 0.05s ease-out;
}

.slider-handle {
    position: absolute;
    top: 50%;
    left: 50%; /* Posición inicial */
    width: 50px;
    height: 50px;
    background: white;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 15px rgba(0,0,0,0.5);
    pointer-events: none;
    color: #0ea5e9; /* Color de las flechas */
    transition: left 0.05s ease-out;
}
</style>
