import './bootstrap';

import { createApp } from 'vue';
import AppStatus from './components/AppStatus.vue';

const appStatusElement = document.getElementById('app-status');

if (appStatusElement) {
    createApp(AppStatus, {
        appName: appStatusElement.dataset.appName || 'stechen-mmo',
    }).mount(appStatusElement);
}
