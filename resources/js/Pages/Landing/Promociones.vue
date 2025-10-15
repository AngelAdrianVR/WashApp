<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const promotions = ref([
    {
        title: '¡Primer Lavado con 50% de Descuento!',
        description: 'Regístrate hoy y obtén la mitad de precio en tu primer servicio de Lavado Ecológico Premium. ¡La bienvenida perfecta para tu auto!',
        cta: 'Lo quiero',
        icon: '🎉'
    },
    {
        title: 'Plan Lealtad: Tu 6º Lavado es GRATIS',
        description: 'Valoramos tu preferencia. Después de 5 servicios de cualquier tipo, el sexto lavado ecológico corre por nuestra cuenta. Sin trucos, solo un gracias.',
        cta: 'Acumular puntos',
        icon: '🎁'
    },
    {
        title: 'Regalo de Cumpleaños',
        description: 'Tu auto también merece celebrar. Te regalamos un encerado cerámico express en cualquier servicio durante el mes de tu cumpleaños.',
        cta: 'Celebrar mi cumpleaños',
        icon: '🎂'
    },
    {
        title: 'Refiere y Gana',
        description: 'Invita a un amigo y ambos reciben un 25% de descuento en su próximo servicio cuando tu referido complete su primer lavado.',
        cta: 'Invitar amigos',
        icon: '🤝'
    }
]);

</script>

<template>
    <LandingLayout :title="'Promociones Exclusivas'">
        <main class="bg-slate-50 text-gray-800">
            <!-- =========== Hero Section STARTS =========== -->
            <section class="bg-gradient-to-br from-slate-900 to-slate-800 text-white pt-32 pb-20 text-center">
                <div class="container mx-auto px-6">
                    <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-4">
                        Más Beneficios, Más Brillo
                    </h1>
                    <p class="text-lg md:text-xl text-slate-300 max-w-3xl mx-auto">
                        Ser parte de WashApp tiene sus recompensas. Descubre todas las promociones exclusivas que tenemos para nuestros miembros.
                    </p>
                </div>
            </section>
            <!-- =========== Hero Section ENDS =========== -->

            <!-- =========== Promotions Section STARTS =========== -->
            <section class="py-20 lg:py-32">
                <div class="container mx-auto px-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                        <div v-for="(promo, index) in promotions" :key="index"
                             class="bg-white rounded-xl p-8 shadow-lg border border-slate-100 flex flex-col items-start transform hover:-translate-y-2 transition-transform duration-300">
                            <div class="text-4xl mb-4">{{ promo.icon }}</div>
                            <h3 class="text-2xl font-bold text-slate-800 mb-3">{{ promo.title }}</h3>
                            <p class="text-slate-600 mb-6 flex-grow">{{ promo.description }}</p>
                            <Link :href="route('register')" class="font-semibold text-sky-600 hover:text-sky-700 transition-colors">
                                {{ promo.cta }} &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
            <!-- =========== Promotions Section ENDS =========== -->

            <!-- =========== CTA Section STARTS =========== -->
            <section class="bg-sky-50 py-20 lg:py-24">
                <div class="container mx-auto px-6 text-center">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-slate-800 leading-tight mb-4">
                        ¿Aún no eres miembro?
                    </h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto mb-8">
                       Regístrate en menos de un minuto y empieza a disfrutar de todos estos beneficios hoy mismo. Es rápido, fácil y completamente gratis.
                    </p>
                    <Link :href="route('register')"
                          class="cta-button bg-gradient-to-r from-blue-500 to-cyan-400 hover:from-blue-600 hover:to-cyan-500 text-white font-bold text-lg py-4 px-8 rounded-full shadow-lg inline-block">
                        Crear mi Cuenta Gratis
                    </Link>
                </div>
            </section>
            <!-- =========== CTA Section ENDS =========== -->
        </main>
    </LandingLayout>
</template>

<style scoped>
.cta-button {
    transition: all 0.3s ease;
}

.cta-button:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 25px rgba(2, 132, 199, 0.2);
}
</style>
