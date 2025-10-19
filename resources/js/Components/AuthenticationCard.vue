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
        bubble.classList.add('bubble');
        const cardRect = card.getBoundingClientRect();

        // Calculate the click position RELATIVE to the card's top-left corner.
        const cardX = event.clientX - cardRect.left;
        const cardY = event.clientY - cardRect.top;

        // Apply the position to the bubble.
        bubble.style.left = `${cardX}px`;
        bubble.style.top = `${cardY}px`;
        
        // Give each bubble a random size for a more natural effect.
        const size = Math.random() * 30 + 20; // Size between 20px and 50px.
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;

        // Add a random horizontal drift to the animation via a CSS custom property.
        const drift = (Math.random() - 0.5) * 150; // Reduced drift
        bubble.style.setProperty('--drift', `${drift}px`);

        // Add the bubble to the card.
        card.appendChild(bubble);

        // Remove the bubble from the DOM after the animation finishes.
        setTimeout(() => {
            bubble.remove();
        }, 3000); // The animation duration is 3s.
    }
};
</script>

<template>
    <!-- Main container with a dark blue background -->
    <div class="min-h-screen bg-gradient-to-br from-blue-900 to-cyan-700 flex flex-col items-center justify-center space-y-3 p-4">
        <!-- 
          The card itself. 
          - Changed to bg-white for a light theme.
          - Increased padding and rounded corners (rounded-2xl) for a softer, modern look.
          - Enhanced shadow (shadow-xl) to make it "pop" against the dark background.
          - Added a subtle border.
        -->
        <div>
            <slot name="logo" />
        </div>
        <div
            ref="cardRef"
            @click="createRealisticBubble"
            class="bg-gray-300 p-10 rounded-2xl shadow-xl w-full max-w-md relative overflow-hidden border border-gray-200"
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
    background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.2) 60%, rgba(255, 255, 255, 0) 100%);
    border: 1px solid rgba(255, 255, 255, 0.3);
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
    50% {
        transform: translate(calc(-50% + var(--drift)), -150px) scale(1.5);
        opacity: 0.5;
    }
    100% {
        transform: translate(calc(-50% + var(--drift) * 1.5), -300px) scale(2);
        opacity: 0;
    }
}
</style>
