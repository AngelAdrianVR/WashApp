<script setup>
import { ref } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Link } from '@inertiajs/vue3';

// Lista de servicios actualizada con precios por tamaño y más detalles
const services = ref([
  {
    id: 1,
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 15.5l3.5-3.5 3.5 3.5 3.5-3.5 3.5 3.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 2a10 10 0 100 20 10 10 0 000-20z" /></svg>`,
    title: 'Lavado Ecológico en Seco',
    description: 'Nuestra opción más ecológica. Limpieza y encerado en un solo paso sin usar una gota de agua.',
    prices: [
        { size: 'Auto Chico', value: 220 },
        { size: 'Auto Mediano', value: 250 },
        { size: 'Auto Grande', value: 290 }
    ],
    features: [
      'Limpieza exterior con polímeros y cera carnauba',
      'Ahorro total de agua',
      'Brillo y repelencia al polvo',
      'Aspirado de interiores y tapetes',
      'Ideal para mantenimiento semanal'
    ]
  },
  {
    id: 2,
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" /></svg>`,
    title: 'Lavado Básico Exterior',
    description: 'Perfecto para el mantenimiento regular. Un lavado exterior completo que deja tu auto brillante y protegido.',
    prices: [
        { size: 'Auto Chico', value: 250 },
        { size: 'Auto Mediano', value: 280 },
        { size: 'Auto Grande', value: 320 }
    ],
    features: [
      'Lavado de carrocería con shampoo pH neutro',
      'Lavado de llantas y rines por fuera',
      'Abrillantado de llantas',
      'Abrillantado de plásticos exteriores',
      'Aspirado a profundidad de interiores y cajuela',
      'Lavado de tapetes'
    ]
  },
  {
    id: 3,
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>`,
    title: 'Lavado Premium Completo',
    description: 'El servicio ideal para una limpieza profunda tanto exterior como interior, con un acabado superior.',
    prices: [
        { size: 'Auto Chico', value: 380 },
        { size: 'Auto Mediano', value: 420 },
        { size: 'Auto Grande', value: 480 }
    ],
    features: [
      'Todo lo del Lavado Básico',
      'Abrillantado de tolvas',
      'Cera líquida para brillo extra',
      'Acondicionador de plásticos interiores',
      'Suministro de agua y luz para el servicio'
    ]
  },
  {
    id: 4,
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>`,
    title: 'Detallado de Interiores',
    description: 'Renueva por completo el interior de tu auto, eliminando manchas, olores y bacterias de cada rincón.',
    prices: [
        { size: 'Auto Chico', value: 1500 },
        { size: 'Auto Mediano', value: 1800 },
        { size: 'Auto Grande', value: 2000 }
    ],
    features: [
        'Lavado de carrocería de cortesía',
        'Limpieza de puertas y consola central',
        'Limpieza detallada de tablero y portavasos',
        'Acondicionamiento de plásticos interiores',
        'Lavado de asientos con máquina de inyección-succión'
    ]
  },
  {
    id: 5,
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>`,
    title: 'Pulido y Encerado Cerámico',
    description: 'Devuelve el brillo de agencia, elimina rayones superficiales y protege la pintura hasta por 6 meses.',
     prices: [
        { size: 'Auto Chico', value: 1300 },
        { size: 'Auto Mediano', value: 1600 },
        { size: 'Auto Grande', value: 1900 }
    ],
    features: [
      'Lavado a detalle de carrocería',
      'Descontaminación química y mecánica de pintura',
      'Corrección de pintura de un paso (60-70% de swirls)',
      'Aplicación de cera con polímeros cerámicos',
      'Protección UV y alta repelencia al agua'
    ]
  }
]);
</script>

<template>
    <LandingLayout :title="'Nuestros Servicios'">
        <main class="bg-slate-50 text-gray-800">
            <!-- =========== Services Section STARTS =========== -->
            <section id="servicios" class="py-24 lg:py-32 pt-36">
                <div class="container mx-auto px-6">
                    <div class="text-center mb-16">
                        <h1 class="text-4xl md:text-5xl font-extrabold text-slate-800 leading-tight">
                            El Plan Perfecto para tu Auto
                        </h1>
                        <p class="text-slate-600 mt-4 max-w-2xl mx-auto text-lg">
                           Ofrecemos una gama completa de servicios de detallado automotriz a domicilio. Elige el que mejor se adapte a tus necesidades y agenda hoy mismo.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
                        <div v-for="service in services" :key="service.id" class="glass-card bg-white rounded-xl p-8 flex flex-col transform hover:-translate-y-2 hover:shadow-2xl transition-all duration-300">
                            <div class="flex items-center mb-4">
                                <div class="text-blue-500 mr-4 flex-shrink-0" v-html="service.icon"></div>
                                <h2 class="text-2xl font-bold text-slate-800">{{ service.title }}</h2>
                            </div>
                            <p class="text-slate-600 mb-6 flex-grow">{{ service.description }}</p>
                            
                            <h3 class="font-semibold text-slate-800 mb-3">¿Qué incluye?</h3>
                            <ul class="text-left mb-6 space-y-2">
                                <li v-for="feature in service.features" :key="feature" class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-cyan-500 mr-2 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-slate-700">{{ feature }}</span>
                                </li>
                            </ul>
                            
                            <div class="mt-auto">
                                <div class="bg-slate-100 rounded-lg p-4 mb-6">
                                    <h3 class="font-semibold text-center text-slate-800 mb-3">Precios</h3>
                                    <div class="flex justify-around text-center">
                                        <div v-for="price in service.prices" :key="price.size">
                                            <p class="text-sm text-slate-600">{{ price.size }}</p>
                                            <p class="font-bold text-slate-800 text-lg">${{ price.value }}</p>
                                        </div>
                                    </div>
                                </div>
                                 <Link :href="route('landing.agendar', { service: service.id })" class="cta-button w-full block bg-gradient-to-r from-blue-500 to-cyan-400 hover:from-blue-600 hover:to-cyan-500 text-white font-bold text-lg py-3 px-8 rounded-full shadow-lg text-center">
                                    Agendar Este Servicio
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- =========== Services Section ENDS =========== -->
        </main>
    </LandingLayout>
</template>

<style scoped>
.glass-card {
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
}
.cta-button {
    transition: all 0.3s ease;
}
.cta-button:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 20px rgba(2, 132, 199, 0.2);
}
</style>

