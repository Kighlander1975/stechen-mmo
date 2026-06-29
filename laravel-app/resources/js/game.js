import './bootstrap';

import { createApp } from 'vue';
import GameApp from './apps/game/GameApp.vue';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

if (csrfToken && window.axios) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

const element = document.getElementById('game-app');

function parseProps(target) {
    const rawProps = target?.dataset?.props || '{}';

    try {
        return JSON.parse(rawProps);
    } catch (error) {
        console.error('Invalid game app props:', error, target);
        return {};
    }
}

if (element) {
    createApp(GameApp, parseProps(element)).mount(element);
}
