<script setup>
import { ref } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';

// Lista de servicios para renderizar dinámicamente
const services = ref([
  {
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.24a2 2 0 00-1.806.547a2 2 0 00-.547 1.806l.477 2.387a6 6 0 00.517 3.86l.158.318a6 6 0 00.517 3.86l2.387.477a2 2 0 001.806-.547a2 2 0 00.547-1.806l-.477-2.387a6 6 0 00-.517-3.86l-.158-.318a6 6 0 00-.517-3.86l-2.387-.477z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01" /></svg>`,
    title: 'Lavado en Seco Ecológico',
    description: 'Limpieza profunda sin usar agua, ideal para cuidar el planeta y la pintura de tu auto.'
  },
  {
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>`,
    title: 'Lavado a Detalle con Agua',
    description: 'Utilizamos productos de alta calidad para un acabado impecable y brillante.'
  },
  {
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>`,
    title: 'Encerado y Pulido',
    description: 'Devolvemos el brillo original a tu auto, protegiendo la pintura contra los elementos.'
  },
  {
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>`,
    title: 'Aspirado y Limpieza Interior',
    description: 'Dejamos el interior de tu auto como nuevo, libre de polvo y suciedad.'
  }
]);
</script>

<template>
    <LandingLayout :title="'Inicio'">
        <main>
            <!-- =========== Hero Section STARTS =========== -->
            <section id="inicio" class="relative min-h-screen flex items-center justify-center gradient-bg-hero pt-20">
                <div class="absolute inset-0 bg-slate-900/50"></div>
                <div class="container mx-auto px-6 text-center relative z-10">
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white leading-tight mb-4">
                        El brillo que tu auto merece,
                        <span class="block bg-clip-text text-transparent bg-gradient-to-r from-sky-400 to-cyan-300">
                            donde tú estés.
                        </span>
                    </h1>
                    <p class="text-lg md:text-xl text-slate-300 max-w-3xl mx-auto mb-8">
                        Olvídate de las filas y el tiempo perdido. Llevamos el mejor servicio de carwash profesional directamente a tu casa u oficina.
                    </p>
                    <a href="#agendar" class="cta-button bg-gradient-to-r from-sky-500 to-cyan-500 hover:from-sky-600 hover:to-cyan-600 text-white font-bold text-lg py-4 px-8 rounded-full shadow-lg">
                        Agendar mi Lavado Ahora
                    </a>
                </div>
            </section>
            <!-- =========== Hero Section ENDS =========== -->

            <!-- =========== Services Section STARTS =========== -->
            <section id="servicios" class="py-20 lg:py-32">
                <div class="container mx-auto px-6">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl md:text-4xl font-bold text-white">Nuestros Servicios Premium</h2>
                        <p class="text-slate-400 mt-2 max-w-2xl mx-auto">
                            Ofrecemos una gama completa de servicios para que tu auto luzca siempre como nuevo.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div v-for="(service, index) in services" :key="index" class="glass-card rounded-xl p-6 flex flex-col items-center text-center transform hover:scale-105 hover:border-sky-400 transition-all duration-300">
                            <div class="text-sky-400 mb-4" v-html="service.icon"></div>
                            <h3 class="text-xl font-semibold text-white mb-2">{{ service.title }}</h3>
                            <p class="text-slate-400">{{ service.description }}</p>
                        </div>
                    </div>
                </div>
            </section>
            <!-- =========== Services Section ENDS =========== -->
            
            <!-- =========== How It Works Section STARTS =========== -->
            <section id="como-funciona" class="py-20 lg:py-32 bg-slate-900/50 gradient-bg-hero">
                <div class="container mx-auto px-6">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl md:text-4xl font-bold text-white">Así de fácil es tener tu auto reluciente</h2>
                        <p class="text-slate-400 mt-2">En solo 3 simples pasos.</p>
                    </div>
                    <div class="flex flex-col md:flex-row justify-center items-center gap-8 lg:gap-16">
                        <!-- Step 1 -->
                        <div class="flex items-center flex-col text-center">
                            <div class="flex items-center justify-center w-20 h-20 rounded-full bg-sky-500/20 border-2 border-sky-500 text-sky-400 text-3xl font-bold mb-4">1</div>
                            <h3 class="text-xl font-semibold text-white mb-2">Agenda tu Servicio</h3>
                            <p class="text-slate-400 max-w-xs">Elige el día, la hora y el lugar que más te convenga desde nuestra web.</p>
                        </div>
                        <!-- Step 2 -->
                        <div class="flex items-center flex-col text-center">
                            <div class="flex items-center justify-center w-20 h-20 rounded-full bg-sky-500/20 border-2 border-sky-500 text-sky-400 text-3xl font-bold mb-4">2</div>
                            <h3 class="text-xl font-semibold text-white mb-2">Vamos hacia Ti</h3>
                            <p class="text-slate-400 max-w-xs">Nuestro equipo de profesionales llegará puntualmente con todo lo necesario.</p>
                        </div>
                        <!-- Step 3 -->
                        <div class="flex items-center flex-col text-center">
                            <div class="flex items-center justify-center w-20 h-20 rounded-full bg-sky-500/20 border-2 border-sky-500 text-sky-400 text-3xl font-bold mb-4">3</div>
                            <h3 class="text-xl font-semibold text-white mb-2">Disfruta tu Auto</h3>
                            <p class="text-slate-400 max-w-xs">Relájate mientras trabajamos. Recibirás tu auto impecable sin moverte.</p>
                        </div>
                    </div>
                </div>
            </section>
            <!-- =========== How It Works Section ENDS =========== -->

            <!-- =========== CTA Section STARTS =========== -->
            <section id="agendar" class="py-20 lg:py-32">
                <div class="container mx-auto px-6 text-center">
                    <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-white leading-tight mb-4">
                        ¿Listo para darle a tu auto el trato que se merece?
                    </h2>
                    <p class="text-lg text-slate-300 max-w-3xl mx-auto mb-8">
                        No esperes más. Agenda tu lavado a domicilio hoy mismo y disfruta el placer de conducir un auto impecable.
                    </p>
                    <a href="#" class="cta-button bg-gradient-to-r from-sky-500 to-cyan-500 hover:from-sky-600 hover:to-cyan-600 text-white font-bold text-lg py-4 px-8 rounded-full shadow-lg">
                        ¡Lo Quiero Impecable!
                    </a>
                </div>
            </section>
            <!-- =========== CTA Section ENDS =========== -->
        </main>
    </LandingLayout>
</template>

<style scoped>
/* Estilos adicionales que no son fácilmente configurables con Tailwind */
.gradient-bg-hero {
    background-color: #0f172a; /* bg-slate-900 */
    background-image: 
        radial-gradient(at 15% 25%, hsla(204, 80%, 35%, 0.3) 0, transparent 50%),
        radial-gradient(at 85% 75%, hsla(190, 70%, 40%, 0.2) 0, transparent 50%),
        radial-gradient(at 50% 95%, hsla(210, 80%, 25%, 0.2) 0, transparent 50%);
}

.glass-card {
    background: rgba(30, 41, 59, 0.5); /* bg-slate-800 con opacidad */
    backdrop-filter: blur(12px);
    border: 1px solid rgba(51, 65, 85, 0.5); /* border-slate-700 con opacidad */
}

.cta-button {
    transition: all 0.3s ease;
}

.cta-button:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 25px rgba(2, 132, 199, 0.3); /* Sombra con color sky-500 */
}
</style>
