<script setup>
import { Head, Link } from '@inertiajs/vue3';

// Define el título de la página para el SEO
const pageTitle = 'Página No Encontrada';
</script>

<template>
  <Head :title="pageTitle" />
  <div class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-blue-50 via-blue-100 to-blue-200 font-sans text-center p-4 overflow-hidden">
    <div class="relative w-full max-w-lg">
      <!-- Contenedor para las burbujas -->
      <div class="bubbles absolute inset-0">
        <div v-for="i in 15" :key="i" class="bubble"></div>
      </div>

      <!-- Icono del auto -->
      <div class="relative z-10 car-animation">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-48 h-48 mx-auto text-blue-500" viewBox="0 0 24 24" fill="currentColor">
          <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
        </svg>
      </div>
    </div>

    <h1 class="text-6xl md:text-8xl font-extrabold text-blue-800 mt-8 animate-fade-in-up">
      404
    </h1>
    <p class="text-xl md:text-2xl text-blue-600 mt-4 animate-fade-in-up animation-delay-200">
      ¡Ups! Parece que esta página se fue a lavar y no ha vuelto.
    </p>
    <p class="text-md text-gray-500 mt-2 animate-fade-in-up animation-delay-400">
      Quizás la dirección no existe o se movió a otro lugar más reluciente.
    </p>

    <Link
      :href="route('landing.index')"
      class="mt-10 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition-transform transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 animate-fade-in-up animation-delay-600"
    >
      Volver al Inicio
    </Link>
  </div>
</template>

<style scoped>
/* Agregamos una fuente más amigable si está disponible */
.font-sans {
  font-family: 'Inter', sans-serif;
}

/* Animación para el auto */
.car-animation {
  animation: drive 3s ease-in-out infinite alternate;
}

@keyframes drive {
  0% {
    transform: translateX(-10px) rotate(-2deg);
  }
  100% {
    transform: translateX(10px) rotate(2deg);
  }
}

/* Animaciones de entrada */
@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  animation: fade-in-up 0.6s both;
}

.animation-delay-200 { animation-delay: 0.2s; }
.animation-delay-400 { animation-delay: 0.4s; }
.animation-delay-600 { animation-delay: 0.6s; }

/* Sistema de burbujas */
.bubbles {
  pointer-events: none;
}

.bubble {
  position: absolute;
  background-color: rgba(147, 197, 253, 0.4); /* blue-300 con opacidad */
  border-radius: 50%;
  animation: floatUp 10s infinite;
  bottom: -200px;
}

@keyframes floatUp {
  0% {
    transform: translateY(0) scale(1);
    opacity: 0.8;
  }
  100% {
    transform: translateY(-120vh) scale(0.8);
    opacity: 0;
  }
}

/* Generamos burbujas con diferentes tamaños y animaciones */
.bubble:nth-child(1) { left: 10%; width: 40px; height: 40px; animation-duration: 8s; animation-delay: 0s; }
.bubble:nth-child(2) { left: 20%; width: 20px; height: 20px; animation-duration: 5s; animation-delay: 1s; }
.bubble:nth-child(3) { left: 25%; width: 50px; height: 50px; animation-duration: 12s; animation-delay: 2s; }
.bubble:nth-child(4) { left: 40%; width: 80px; height: 80px; animation-duration: 15s; animation-delay: 0s; }
.bubble:nth-child(5) { left: 55%; width: 35px; height: 35px; animation-duration: 7s; animation-delay: 1.5s; }
.bubble:nth-child(6) { left: 65%; width: 45px; height: 45px; animation-duration: 9s; animation-delay: 3s; }
.bubble:nth-child(7) { left: 75%; width: 60px; height: 60px; animation-duration: 11s; animation-delay: 0.5s; }
.bubble:nth-child(8) { left: 90%; width: 25px; height: 25px; animation-duration: 6s; animation-delay: 2.5s; }
.bubble:nth-child(9) { left: 50%; width: 15px; height: 15px; animation-duration: 10s; animation-delay: 4s; }
.bubble:nth-child(10) { left: 80%; width: 55px; height: 55px; animation-duration: 13s; animation-delay: 1s; }
/* Añadimos más para llenar el espacio */
.bubble:nth-child(11) { left: 5%; width: 30px; height: 30px; animation-duration: 9s; animation-delay: 5s; }
.bubble:nth-child(12) { left: 35%; width: 22px; height: 22px; animation-duration: 6s; animation-delay: 6s; }
.bubble:nth-child(13) { left: 45%; width: 48px; height: 48px; animation-duration: 14s; animation-delay: 3s; }
.bubble:nth-child(14) { left: 60%; width: 70px; height: 70px; animation-duration: 16s; animation-delay: 2s; }
.bubble:nth-child(15) { left: 85%; width: 28px; height: 28px; animation-duration: 7s; animation-delay: 5.5s; }
</style>

