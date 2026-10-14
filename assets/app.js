import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
// import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

import '@tabler/core';
import '@tabler/core/dist/css/tabler.min.css';

import 'highlight.js/styles/github.min.css';

import hljs from 'highlight.js';

// Initialize highlight.js when the page loads
document.addEventListener('DOMContentLoaded', () => {
    // Highlight all code blocks
    hljs.highlightAll();

    // Or highlight specific elements
    // document.querySelectorAll('pre code').forEach((block) => {
    //     hljs.highlightElement(block);
    // });
});

// asciinema demo: only load the player on pages that have a #demo element
const demo = document.getElementById('demo');
if (demo) {
    import('asciinema-player/dist/bundle/asciinema-player.css');
    import('asciinema-player').then((AsciinemaPlayer) => {
        AsciinemaPlayer.create('/test.cast', demo, {
            // options here
        });
    });
}
