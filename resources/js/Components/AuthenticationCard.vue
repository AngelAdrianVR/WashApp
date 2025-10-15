<script setup>
import { ref } from 'vue';

// Reference to the card element to manipulate it in the script.
const cardRef = ref(null);

/**
 * Creates realistic bubbles at the click point.
 * @param {MouseEvent} event - The mouse click event.
 */
const createRealisticBubble = (event) => {
    const card = cardRef.value;
    if (!card) return;

    // Create 3 bubbles for a fuller effect.
    for (let i = 0; i < 3; i++) {
        // Create the 'span' element that will be our bubble.
        const bubble = document.createElement('span');
        const cardRect = card.getBoundingClientRect();

        // Calculate the click position RELATIVE to the card's top-left corner.
        const cardX = event.clientX - cardRect.left;
        const cardY = event.clientY - cardRect.top;

        // Apply the position to the bubble.
        bubble.style.left = `${cardX}px`;
        bubble.style.top = `${cardY}px`;
        
        // Give each bubble a random size for a more natural effect.
        const size = Math.random() * 30 + 20; // Size between 20px and 80px.
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;

        // Add a random horizontal drift to the animation via a CSS custom property.
        const drift = (Math.random() - 0.5) * 200;
        bubble.style.setProperty('--drift', `${drift}px`);

        // Add the CSS class containing the animation.
        bubble.classList.add('bubble');

        // Add the bubble to the card.
        card.appendChild(bubble);

        // Remove the bubble from the DOM after the animation finishes (3000ms = 3s).
        setTimeout(() => {
            bubble.remove();
        }, 3000);
    }
};
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-blue-200 to-blue-400 font-[Poppins]">
        <div>
            <slot name="logo" />
        </div>

        <!--
            We added:
            - ref="cardRef": To get a reference to the element in the script.
            - @click="createRealisticBubble": To trigger the effect on each click.
            - relative: So that bubbles are positioned INSIDE the card.
            - cursor-pointer: To visually indicate that the card is interactive.
        -->
        <div 
            ref="cardRef" 
            @click="createRealisticBubble"
            class="relative w-full sm:max-w-md mt-6 px-6 py-8 bg-gray-800/30 backdrop-blur-lg border border-gray-700 shadow-2xl shadow-blue-500/20 overflow-hidden sm:rounded-2xl cursor-pointer"
        >
            <slot />
        </div>
    </div>
</template>

<style>
/*
  Styles for the bubble.
  'position: absolute' is key for placing it according to the click coordinates.
  'transform: translate(-50%, -50%)' centers the bubble on the mouse pointer.
*/
.bubble {
    position: absolute;
    border-radius: 50%;
    /* Realistic bubble effect with a highlight */
    background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.1) 60%, rgba(255, 255, 255, 0) 100%);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: inset 0 0 10px rgba(255, 255, 255, 0.2);
    transform: translate(-50%, -50%);
    animation: float-and-pop 3s ease-in forwards;
    pointer-events: none; /* Prevents the bubble from interfering with clicks on buttons or inputs. */
}

/*
  The @keyframes animation that defines the effect.
  - Starts visible and at its original size.
  - Floats up, drifts sideways, grows, and fades over 3 seconds.
  - "Pops" at the end by expanding slightly more and disappearing completely.
*/
@keyframes float-and-pop {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.9;
    }
    99% {
        /* Float up and apply horizontal drift */
        transform: translate(calc(-50% + var(--drift)), -250px) scale(1.5);
        opacity: 0.1;
    }
    100% {
        /* Pop effect */
        transform: translate(calc(-50% + var(--drift)), -250px) scale(1.5);
        opacity: 0;
    }
}
</style>
